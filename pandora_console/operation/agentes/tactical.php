<?php
/**
 * Tactical View.
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
global $config;

require_once 'include/functions_events.php';
require_once 'include/functions_servers.php';
require_once 'include/functions_reporting.php';
require_once 'include/functions_tactical.php';
require_once $config['homedir'].'/include/functions_graph.php';

check_login();

if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent view (Grouped)'
    );
    include 'general/noaccess.php';
    return;
}

ui_require_css_file('tactical');

$is_admin = check_acl($config['id_user'], 0, 'PM');
$user_strict = (bool) db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$force_refresh = get_parameter('force_refresh', '');
$refresh = get_parameter('refr', 0);
if ($force_refresh == 1) {
    db_process_sql('UPDATE tgroup_stat SET utimestamp = 0');
}

$updated_time = '';
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
    __('Tactical view'),
    '',
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

// Currently this function makes loading this page is impossible. Change
// and create new function.
$all_data = tactical_status_modules_agents($config['id_user'], false, 'AR');

$data = [];

$data['monitor_not_init'] = (int) $all_data['_monitors_not_init_'];
$data['monitor_unknown'] = (int) $all_data['_monitors_unknown_'];
$data['monitor_ok'] = (int) $all_data['_monitors_ok_'];
$data['monitor_warning'] = (int) $all_data['_monitors_warning_'];
$data['monitor_critical'] = (int) $all_data['_monitors_critical_'];
$data['monitor_not_normal'] = (int) $all_data['_monitor_not_normal_'];
$data['monitor_alerts'] = (int) $all_data['_monitors_alerts_'];
$data['monitor_alerts_fired'] = (int) $all_data['_monitors_alerts_fired_'];
$data['monitor_total'] = (int) $all_data['_monitor_total_'];

$data['total_agents'] = (int) $all_data['_total_agents_'];

$data['monitor_checks'] = (int) $all_data['_monitor_checks_'];


// Percentages
if (!empty($all_data)) {
    if ($data['monitor_not_normal'] > 0 && $data['monitor_checks'] > 0) {
        $data['monitor_health'] = format_numeric((100 - ($data['monitor_not_normal'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['monitor_health'] = 100;
    }

    if ($data['monitor_not_init'] > 0 && $data['monitor_checks'] > 0) {
        $data['module_sanity'] = format_numeric((100 - ($data['monitor_not_init'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['module_sanity'] = 100;
    }

    if (isset($data['alerts'])) {
        if ($data['monitor_alerts_fired'] > 0 && $data['alerts'] > 0) {
            $data['alert_level'] = format_numeric((100 - ($data['monitor_alerts_fired'] / ($data['alerts'] / 100))), 1);
        } else {
            $data['alert_level'] = 100;
        }
    } else {
        $data['alert_level'] = 100;
        $data['alerts'] = 0;
    }

    $data['monitor_bad'] = ($data['monitor_critical'] + $data['monitor_warning']);

    if ($data['monitor_bad'] > 0 && $data['monitor_checks'] > 0) {
        $data['global_health'] = format_numeric((100 - ($data['monitor_bad'] / ($data['monitor_checks'] / 100))), 1);
    } else {
        $data['global_health'] = 100;
    }

    $data['server_sanity'] = format_numeric((100 - $data['module_sanity']), 1);
}

echo '<table border=0 class="w100p"><tr>';
echo '<td class="tactical_left_column" id="leftcolumn">';
// ---------------------------------------------------------------------
// The status horizontal bars (Global health, Monitor sanity...
// ---------------------------------------------------------------------
$bg_color = 'background-color: #222';
if ($config['style'] !== 'pandora_black' && !is_metaconsole()) {
    $bg_color = 'background-color: #fff';
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table no-td-borders';
$table->cellpadding = 2;
$table->cellspacing = 2;
$table->border = 0;
$table->head = [];
$table->data = [];
$table->style = [$bg_color];

$stats = reporting_get_stats_indicators($data, 120, 10, false);

$statusTacticalTable = new stdClass();
$statusTacticalTable->width = '100%';
$statusTacticalTable->id = 'statusTacticalTable';
$statusTacticalTable->class = 'status_tactical tactical_table bg_white';
$statusTacticalTable->data = [];

foreach ($stats as $key => $stat) {
    $statusTacticalTable->cellstyle['line_'.$key][0] = 'width: 40%;';
    $statusTacticalTable->style['line_'.$key][1] = 'width: 60%;';
    $statusTacticalTable->data['line_'.$key][0] = '<span>'.$stat['title'].'</span>';
    $statusTacticalTable->data['line_'.$key][1] = $stat['graph'];
}

$status = html_print_table($statusTacticalTable, true);

$table->rowclass = [];
$table->rowclass[0] = 'w100p';
$table->rowclass[1] = 'w100p';
$table->rowclass[2] = 'w100p';
$table->rowclass[3] = 'w100p';
$table->rowclass[4] = 'w100p';
$table->data[0][0] = $status;


// ---------------------------------------------------------------------
// Monitor checks
// ---------------------------------------------------------------------
$data_agents = [
    __('Critical') => $data['monitor_critical'],
    __('Warning')  => $data['monitor_warning'],
    __('Normal')   => $data['monitor_ok'],
    __('Unknown')  => $data['monitor_unknown'],
    __('Not init') => $data['monitor_not_init'],
];

$table->data[1][0] = reporting_get_stats_alerts($data);
$table->rowclass[1] = 'w100p';
$table->data[2][0] = reporting_get_stats_modules_status($data, 180, 100, false, $data_agents);
$table->data[3][0] = reporting_get_stats_agents_monitors($data);

$table->rowclass[] = '';

// ---------------------------------------------------------------------
// Server performance
// ---------------------------------------------------------------------
if ($is_admin) {
    $table->data[4][0] = reporting_get_stats_servers();
    $table->rowclass[] = '';
}

ui_toggle(
    html_print_table($table, true),
    __('Report of State'),
    '',
    '',
    false
);

echo '</td>';
// Left column
echo '<td class="w75p pdd_t_0px" id="rightcolumn">';

// ---------------------------------------------------------------------
// Last events information
// ---------------------------------------------------------------------
if (check_acl($config['id_user'], 0, 'ER')) {
    $tags_condition = tags_get_acl_tags(false, 0, 'ER', 'event_condition');
    $event_filter = 'estado<>1';
    if (!empty($tags_condition)) {
        $event_filter .= " AND ($tags_condition)";
    }

    if ($config['event_view_hr']) {
        $event_filter .= ' AND utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
    }

    $events = events_print_event_table($event_filter, 10, '100%', true, 0, true);
    ui_toggle(
        $events,
        __('Latest events'),
        '',
        '',
        false
    );
}

// ---------------------------------------------------------------------
// Server information
// ---------------------------------------------------------------------
if ($is_admin) {
    $tiny = true;
    include $config['homedir'].'/godmode/servers/servers.build_table.php';
}

$out = '<table cellpadding=0 cellspacing=0 class="databox pies" width=100%><tr><td style="width:50%;">';
$out .= '<fieldset class="databox tactical_set" id="total_event_graph">';
$out .= '<legend>'.__('Event graph').'</legend>';
$out .= html_print_image('images/spinner.gif', true, ['id' => 'spinner_total_event_graph']);
$out .= '</fieldset>';
$out .= '</td><td style="width:50%;">';
$out .= '<fieldset class="databox tactical_set" id="graphic_event_group">
        <legend>'.__('Event graph by agent').'</legend>'.html_print_image('images/spinner.gif', true, ['id' => 'spinner_graphic_event_group']).'</fieldset>';
$out .= '</td></tr></table>';


ui_toggle(
    $out,
    __('Event graphs'),
    '',
    '',
    false
);

echo '</td>';
echo '</tr></table>';
?>
<script type="text/javascript">
    $(document).ready(function () {
        var parameters = {};
        parameters["page"] = "include/ajax/events";
        parameters["total_event_graph"] = 1;

        $.ajax({type: "GET",url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",data: parameters,
            success: function(data) {
                $("#spinner_total_event_graph").hide();
                $("#total_event_graph").append(data);
            }
        });

        delete parameters["total_event_graph"];
        parameters["graphic_event_group"] = 1;

        $.ajax({type: "GET",url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",data: parameters,
            success: function(data) {
                $("#spinner_graphic_event_group").hide();
                $("#graphic_event_group").append(data);
            }
        });
    });
</script>
