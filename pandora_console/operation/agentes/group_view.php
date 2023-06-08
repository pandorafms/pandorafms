<?php
/**
 * Group View.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Monitoring.
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
require_once 'include/config.php';
require_once 'include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once 'include/functions_groupview.php';

check_login();
// ACL Check
$agent_a = check_acl($config['id_user'], 0, 'AR');
$agent_w = check_acl($config['id_user'], 0, 'AW');

if (!$agent_a && !$agent_w) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent view (Grouped)'
    );
    include 'general/noaccess.php';
    exit;
}

// Update network modules for this group
// Check for Network FLAG change request
// Made it a subquery, much faster on both the database and server side
if (isset($_GET['update_netgroup'])) {
    $group = get_parameter_get('update_netgroup', 0);

    if (check_acl($config['id_user'], $group, 'AW')) {
        if ($group == 0) {
            db_process_sql_update('tagente_modulo', ['flag' => 1]);
        } else {
            db_process_sql(
                'UPDATE `tagente_modulo`
				SET `flag` = 1
				WHERE `id_agente` = ANY(SELECT id_agente
					FROM tagente
					WHERE id_grupo = '.$group.')'
            );
        }
    } else {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to set flag for groups'
        );
        include 'general/noaccess.php';
        exit;
    }
}

if ($config['realtimestats'] == 0) {
    $updated_time = "<a href='index.php?sec=estado&sec2=operation/agentes/tactical&force_refresh=1'>";
    $updated_time .= __('Last update').' : '.ui_print_timestamp(db_get_sql('SELECT min(utimestamp) FROM tgroup_stat'), true);
    $updated_time .= '</a>';
} else {
    // $updated_info = __("Updated at realtime");
    $updated_info = '';
}

// Header.
ui_print_standard_header(
    __('Group view'),
    'images/group.png',
    false,
    '',
    false,
    (array) $updated_time,
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

$total_agentes = 0;
$monitor_ok = 0;
$monitor_warning = 0;
$monitor_critical = 0;
$monitor_unknown = 0;
$monitor_not_init = 0;
$agents_unknown = 0;
$agents_critical = 0;
$agents_notinit = 0;
$agents_ok = 0;
$agents_warning = 0;
$all_alerts_fired = 0;

// Groups and tags
$result_groups_info = groupview_get_groups_list(
    $config['id_user'],
    ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR')
);
$result_groups = $result_groups_info['groups'];
$count = $result_groups_info['counter'];

if ($result_groups[0]['_id_'] == 0) {
    $total_agentes = $result_groups[0]['_total_agents_'];
    $monitor_ok = $result_groups[0]['_monitors_ok_'];
    $monitor_warning = $result_groups[0]['_monitors_warning_'];
    $monitor_critical = $result_groups[0]['_monitors_critical_'];
    $monitor_unknown = $result_groups[0]['_monitors_unknown_'];
    $monitor_not_init = $result_groups[0]['_monitors_not_init_'];

    $agents_unknown = $result_groups[0]['_agents_unknown_'];
    $agents_notinit = $result_groups[0]['_agents_not_init_'];
    $agents_critical = $result_groups[0]['_agents_critical_'];
    $agents_warning = $result_groups[0]['_agents_warning_'];
    $agents_ok = $result_groups[0]['_agents_ok_'];

    $all_alerts_fired = $result_groups[0]['_monitors_alerts_fired_'];
}

$total = ($monitor_ok + $monitor_warning + $monitor_critical + $monitor_unknown + $monitor_not_init);

// Modules
$total_ok = 0;
$total_warning = 0;
$total_critical = 0;
$total_unknown = 0;
$total_monitor_not_init = 0;
// Agents
$total_agent_unknown = 0;
$total_agent_critical = 0;
$total_not_init = 0;
$total_agent_warning = 0;
$total_agent_ok = 0;

if ($total > 0) {
    // Modules
    $total_ok = format_numeric((($monitor_ok * 100) / $total), 2);
    $total_warning = format_numeric((($monitor_warning * 100) / $total), 2);
    $total_critical = format_numeric((($monitor_critical * 100) / $total), 2);
    $total_unknown = format_numeric((($monitor_unknown * 100) / $total), 2);
    $total_monitor_not_init = format_numeric((($monitor_not_init * 100) / $total), 2);
}

if ($total_agentes > 0) {
    // Agents.
    $total_agent_unknown = format_numeric((($agents_unknown * 100) / $total_agentes), 2);
    $total_agent_critical = format_numeric((($agents_critical * 100) / $total_agentes), 2);
    $total_agent_warning = format_numeric((($agents_warning * 100) / $total_agentes), 2);
    $total_agent_ok = format_numeric((($agents_ok * 100) / $total_agentes), 2);
    $total_not_init = format_numeric((($agents_notinit * 100) / $total_agentes), 2);
}

echo '<table width="100%" class="info_table">';
    echo '<thead>';
    echo '<tr>';
        echo "<th colspan=2 class='center'>".__('Summary of the status groups').'</th>';
    echo '</tr>';
    echo '<tr>';
        echo "<th class='center'>".__('Agents').'</th>';
        echo "<th class='center'>".__('Modules').'</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    echo "<tr height=70px'>";
        echo "<td align='center'>";
            echo "<span id='sumary' class='red_background'>".$total_agent_critical.'%</span>';
            echo "<span id='sumary' class='yellow_background'>".$total_agent_warning.'%</span>';
            echo "<span id='sumary' class='green_background'>".$total_agent_ok.'%</span>';
            echo "<span id='sumary' class='bg_B2B2B2'>".$total_agent_unknown.'%</span>';
            echo "<span id='sumary' class='bg_4a83f3'>".$total_not_init.'%</span>';
        echo '</td>';
        echo "<td align='center'>";
            echo "<span id='sumary' class='red_background'>".$total_critical.'%</span>';
            echo "<span id='sumary' class='yellow_background'>".$total_warning.'%</span>';
            echo "<span id='sumary' class='green_background'>".$total_ok.'%</span>';
            echo "<span id='sumary' class='bg_B2B2B2'>".$total_unknown.'%</span>';
            echo "<span id='sumary' class='bg_4a83f3'>".$total_monitor_not_init.'%</span>';
        echo '</td>';
    echo '</tr>';
    echo '</tbody>';
echo '</table>';

if ($count == 1) {
    if ($result_groups[0]['_id_'] == 0) {
        unset($result_groups[0]);
    }
}

if (empty($result_groups) === false) {
    $pagination = ui_pagination(
        $count,
        false,
        $offset,
        0,
        true,
        'offset',
        false
    );

    html_print_action_buttons(
        '',
        [ 'right_content' => $pagination ]
    );

    echo '<table class="info_table mrgn_top_10px" border="0" width="100%">';
        echo '<thead>';
        echo '<tr>';
            echo '<th colspan=14>'.__('Total items').': '.$count.'</th>';
        echo '</tr>';
        echo '<tr>';
            echo '<th colspan=2 ></th>';
            echo '<th colspan=6>'.__('Agents').'</th>';
            echo '<th colspan=6>'.__('Modules').'</th>';
        echo '</tr>';

        echo '<tr>';
            echo "<th class='w26px'>".__('Force').'</th>';
            echo "<th width='30%' class='mw60px' style='text-align: justify'>".__('Group').'/'.__('Tags').'</th>';
            echo "<th class='center'>".__('Total').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Unknown').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Not init').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Normal').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Warning').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Critical').'</th>';
            echo "<th class='center'>".__('Unknown').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Not init').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Normal').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Warning').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Critical').'</th>';
            echo "<th width='10%' class='mw60px center'>".__('Alert fired').'</th>';
        echo '</tr>';
        echo '</thead>';

    foreach ($result_groups as $data) {
        if ((bool) $config['show_empty_groups'] === false
            && $data['_total_agents_'] === 0
            && $data['_monitor_checks_'] === 0
        ) {
            continue;
        }

        $groups_id = $data['_id_'];

        // Calculate entire row color.
        if ($groups_id !== '0') {
            if ($data['_monitors_alerts_fired_'] > 0) {
                $color_class = 'group_view_alrm';
                $status_image = ui_print_status_image('agent_alertsfired_ball.png', '', true);
            } else if ($data['_monitors_critical_'] > 0) {
                $color_class = 'group_view_crit';
                $status_image = ui_print_status_image('agent_critical_ball.png', '', true);
            } else if ($data['_monitors_warning_'] > 0) {
                $color_class = 'group_view_warn';
                $status_image = ui_print_status_image('agent_warning_ball.png', '', true);
            } else if (($data['_monitors_unknown_'] > 0) || ($data['_agents_unknown_'] > 0)) {
                $color_class = 'group_view_unk';
                $status_image = ui_print_status_image('agent_no_monitors_ball.png', '', true);
            } else if ($data['_monitors_ok_'] > 0) {
                $color_class = 'group_view_ok';
                $status_image = ui_print_status_image('agent_ok_ball.png', '', true);
            } else {
                $color_class = '';
                $status_image = ui_print_status_image('agent_no_data_ball.png', '', true);
            }
        } else {
            $color_class = '';
            $status_image = ui_print_status_image('agent_no_data_ball.png', '', true);
        }

        echo "<tr class='height_35px'>";

        // Force.
        echo "<td class='group_view_data center vertical_middle'>";
        if (!isset($data['_is_tag_']) && check_acl($config['id_user'], $data['_id_'], 'AW')) {
            echo '<a href="index.php?sec=estado&sec2=operation/agentes/group_view&update_netgroup='.$data['_id_'].'">'.html_print_image(
                'images/force@svg.svg',
                true,
                [
                    'border' => '0',
                    'title'  => __('Force'),
                    'class'  => 'main_menu_icon invert_filter',
                ]
            ).'</a>';
        }

        echo '</td>';

        $prefix = '';
        if (!isset($data['_is_tag_'])) {
            if ($data['_id_'] != 0) {
                $prefix = '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
        }

        // Groupname and Tags
        echo '<td>';
        if (isset($data['_is_tag_'])) {
            $deep = '';
            $link = "<a href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']."'>";
        } else {
            $deep = groups_get_group_deep($data['_id_']);
            if ($data['_id_'] === '0') {
                $link = "<a href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
            } else {
                $link = "<a href='index.php?sec=view&sec2=godmode/groups/tactical&id_group=".$data['_id_']."'>";
            }
        }

        $group_name = '<b><span>'.ui_print_truncate_text($data['_name_'], 50).'</span></b>';

        $item_icon = '';
        if (isset($data['_iconImg_']) && !empty($data['_iconImg_'])) {
            $item_icon = $data['_iconImg_'];
        }

        if ($data['_name_'] != 'All') {
            echo $deep.$link.$group_name.'</a>';
        } else {
            $hint = '';
            if (enterprise_hook('agents_is_using_secondary_groups')) {
                $hint = ui_print_help_tip(__('This %s installation are using the secondary groups feature. For this reason, an agent can be counted several times.', get_product_name()));
            }

            echo $link.$group_name.'</a>'.$hint;
        }

        if (isset($data['_is_tag_'])) {
            echo '<a>'.html_print_image('images/tag.png', true, ['border' => '0', 'style' => 'width:18px;margin-left:5px', 'title' => __('Tag')]).'</a>';
        }

        echo '</td>';

        // Total agents
        echo "<td align='center' class='$color_class bolder font_18pt'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_']."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder font_18px center' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_']."'>";
        }

        if ($data['_id_'] == 0) {
            echo $link.$total_agentes.'</a>';
        }

        if ($data['_total_agents_'] > 0 && $data['_id_'] != 0) {
            echo $link.$data['_total_agents_'].'</a>';
        }

        echo '</td>';

        // Agents unknown
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder font_18px center'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_'].'&status='.AGENT_STATUS_UNKNOWN."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder font_18px center' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_'].'&status='.AGENT_STATUS_UNKNOWN."'>";
        }

        if (($data['_id_'] == 0) && ($agents_unknown != 0)) {
            echo $link.$agents_unknown.'</a>';
        }

        if ($data['_agents_unknown_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_agents_unknown_'].'</a>';
        }

        echo '</td>';

        // Agents not init
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder font_18px center'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_'].'&status='.AGENT_STATUS_NOT_INIT."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder font_18px center' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_'].'&status='.AGENT_STATUS_NOT_INIT."'>";
        }

        if (($data['_id_'] == 0) && ($agents_notinit != 0)) {
            echo $link.$agents_notinit.'</a>';
        }

        if ($data['_agents_not_init_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_agents_not_init_'].'</a>';
        }

        echo '</td>';

        // Agents Normal
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder font_18px center'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_'].'&status='.AGENT_STATUS_NORMAL."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder font_18px center' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_'].'&status='.AGENT_STATUS_NORMAL."'>";
        }

        if (($data['_id_'] == 0) && ($agents_ok != 0)) {
            echo $link.$agents_ok.'</a>';
        }

        if ($data['_agents_ok_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_agents_ok_'].'</a>';
        }

        echo '</td>';

        // Agents warning
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_'].'&status='.AGENT_STATUS_WARNING."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_'].'&status='.AGENT_STATUS_WARNING."'>";
        }

        if (($data['_id_'] == 0) && ($agents_warning != 0)) {
            echo $link.$agents_warning.'</a>';
        }

        if ($data['_agents_warning_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_agents_warning_'].'</a>';
        }

        echo '</td>';

        // Agents critical
        echo "<td class='group_view_data group_view_data_unk $color_class bolder center font_18px'>";
        if (isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px'
				href='index.php?sec=monitoring&sec2=operation/tree&tag_id=".$data['_id_'].'&status='.AGENT_STATUS_CRITICAL."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/estado_agente&group_id=".$data['_id_'].'&status='.AGENT_STATUS_CRITICAL."'>";
        }

        if (($data['_id_'] == 0) && ($agents_critical != 0)) {
            echo $link.$agents_critical.'</a>';
        }

        if ($data['_agents_critical_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_agents_critical_'].'</a>';
        }

        echo '</td>';

        // Monitors unknown
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_UNKNOWN."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_UNKNOWN."'>";
        }

        if (($data['_id_'] == 0) && ($monitor_unknown != 0)) {
            echo $link.$monitor_unknown.'</a>';
        }

        if ($data['_monitors_unknown_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_unknown_'].'</a>';
        }

        echo '</td>';

        // Monitors not init
        echo "<td class='group_view_data group_view_data_unk $color_class bolder font_18px center'>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_NOT_INIT."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_NOT_INIT."'>";
        }

        if (($data['_id_'] == 0) && ($monitor_not_init != 0)) {
            echo $link.$monitor_not_init.'</a>';
        }

        if ($data['_monitors_not_init_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_not_init_'].'</a>';
        }

        echo '</td>';

        // Monitors OK
        echo "<td class='group_view_data group_view_data_ok $color_class bolder center font_18px'>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_NORMAL."'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_NORMAL."'>";
        }

        if (($data['_id_'] == 0) && ($monitor_ok != 0)) {
            echo $link.$monitor_ok.'</a>';
        }

        if ($data['_monitors_ok_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_ok_'].'</a>';
        }

        echo '</td>';

        // Monitors Warning
        echo "<td class='group_view_data group_view_data_warn $color_class bolder center font_18px'>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data group_view_data_warn $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_WARNING."'>";
        } else {
            $link = "<a class='group_view_data group_view_data_warn $color_class bolder center font_18px' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_WARNING."'>";
        }

        if (($data['_id_'] == 0) && ($monitor_warning != 0)) {
            echo $link.$monitor_warning.'</a>';
        }

        if ($data['_monitors_warning_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_warning_'].'</a>';
        }

        echo '</td>';

        // Monitors Critical
        echo "<td class='group_view_data group_view_data_crit $color_class bolder center font_18px'>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class font_18px bolder center' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&ag_group=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_CRITICAL_BAD."'>";
        } else {
            $link = "<a class='group_view_data $color_class font_18px bolder center' 
				href='index.php?sec=view&sec2=operation/agentes/status_monitor&tag_filter=".$data['_id_'].'&status='.AGENT_MODULE_STATUS_CRITICAL_BAD."'>";
        }

        if (($data['_id_'] == 0) && ($monitor_critical != 0)) {
            echo $link.$monitor_critical.'</a>';
        }

        if ($data['_monitors_critical_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_critical_'].'</a>';
        }

        echo '</td>';

        // Alerts fired
        echo "<td class='group_view_data group_view_data_alrm $color_class bolder center font_18px's>";
        if (!isset($data['_is_tag_'])) {
            $link = "<a class='group_view_data $color_class bolder center font_18px's 
				href='index.php?sec=estado&sec2=operation/agentes/alerts_status&ag_group=".$data['_id_']."&filter=fired'>";
        } else {
            $link = "<a class='group_view_data $color_class bolder center font_18px' 
				href='index.php?sec=estado&sec2=operation/agentes/alerts_status&tag_filter=".$data['_id_']."&filter=fired'>";
        }

        if (($data['_id_'] == 0) && ($all_alerts_fired != 0)) {
            echo $link.$all_alerts_fired.'</a>';
        }

        if ($data['_monitors_alerts_fired_'] > 0 && ($data['_id_'] != 0)) {
            echo $link.$data['_monitors_alerts_fired_'].'</a>';
        }

        echo '</td>';

        echo '</tr>';
    }

    echo '</table>';
} else {
    ui_print_info_message(__('There are no defined agents'));
}
