<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Reporting
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

/**
 * Include the usual functions
 */
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_events.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('include/functions_reporting.php');
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_inventory.php');
require_once $config['homedir'].'/include/functions_forecast.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_netflow.php';
require_once $config['homedir'].'/include/functions_os.php';
require_once $config['homedir'].'/include/functions_network.php';

// CONSTANTS DEFINITIONS.
// Priority modes.
define('REPORT_PRIORITY_MODE_OK', 1);
define('REPORT_PRIORITY_MODE_UNKNOWN', 2);

// Failover type.
define('REPORT_FAILOVER_TYPE_NORMAL', 1);
define('REPORT_FAILOVER_TYPE_SIMPLE', 2);

// Status.
define('REPORT_STATUS_ERR', 0);
define('REPORT_STATUS_OK', 1);
define('REPORT_STATUS_UNKNOWN', 2);
define('REPORT_STATUS_NOT_INIT', 3);
define('REPORT_STATUS_DOWNTIME', 4);
define('REPORT_STATUS_IGNORED', 5);


function reporting_user_can_see_report($id_report, $id_user=null)
{
    global $config;

    if (empty($id_user)) {
        $id_user = $config['id_user'];
    }

    // Get Report record (to get id_group).
    $report = db_get_row('treport', 'id_report', $id_report);

    // Check ACL on the report to see if user has access to the report.
    if (empty($report) || !check_acl($config['id_user'], $report['id_group'], 'RR')) {
        return false;
    }

    return true;
}


function reporting_get_type($content)
{
    switch ($content['type']) {
        case REPORT_OLD_TYPE_SIMPLE_GRAPH:
            $content['type'] = 'simple_graph';
        break;

        case REPORT_OLD_TYPE_CUSTOM_GRAPH:
            $content['type'] = 'custom_graph';
        break;

        case REPORT_OLD_TYPE_MONITOR_REPORT:
            $content['type'] = 'monitor_report';
        break;

        case REPORT_OLD_TYPE_SLA:
            $content['type'] = 'SLA';
        break;

        case REPORT_OLD_TYPE_AVG_VALUE:
            $content['type'] = 'avg_value';
        break;

        case REPORT_OLD_TYPE_MAX_VALUE:
            $content['type'] = 'max_value';
        break;

        case REPORT_OLD_TYPE_MIN_VALUE:
            $content['type'] = 'min_value';
        break;

        case REPORT_OLD_TYPE_SUMATORY:
            $content['type'] = 'sumatory';
        break;

        default:
            // Default.
        break;
    }

    return $content['type'];
}


function reporting_get_description($id_report)
{
    return db_get_value('description', 'treport', 'id_report', $id_report);
}


function reporting_get_name($id_report)
{
    return db_get_value('name', 'treport', 'id_report', $id_report);
}


function reporting_make_reporting_data(
    $report=null,
    $id_report,
    $date,
    $time,
    $period=null,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null,
    $pdf=false,
    $from_template=false
) {
    global $config;

    enterprise_include_once('include/functions_metaconsole.php');

    $return = [];
    if (!empty($report)) {
        $contents = $report['contents'];
    } else {
        $report = io_safe_output(db_get_row('treport', 'id_report', $id_report));
        $contents = io_safe_output(
            db_get_all_rows_field_filter(
                'treport_content',
                'id_report',
                $id_report,
                db_escape_key_identifier('order')
            )
        );
    }

    $datetime = strtotime($date.' '.$time);
    $report['datetime'] = $datetime;
    $report['group'] = $report['id_group'];
    $report['group_name'] = groups_get_name($report['id_group']);
    $report['contents'] = [];

    if (empty($contents)) {
        return reporting_check_structure_report($report);
    }

    $metaconsole_on = is_metaconsole();
    $index_content = 0;
    foreach ($contents as $content) {
        if (!empty($content['id_agent_module']) && !empty($content['id_agent'])
            && tags_has_user_acl_tags($config['id_user'])
        ) {
            $where_tags = tags_get_acl_tags(
                $config['id_user'],
                $id_groups,
                'AR',
                'module_condition',
                'AND',
                'tagente_modulo',
                false,
                [],
                true
            );

            $sql_tags_join = 'INNER JOIN tagente ON tagente.id_agente = t1.id_agente
                INNER JOIN ttag_module ON ttag_module.id_agente_modulo = t1.id_agente_modulo
                LEFT JOIN tagent_secondary_group tasg ON tagente.id_agente = tasg.id_agent';

            $sql = sprintf(
                'SELECT count(*) FROM tagente_modulo t1
                %s WHERE t1.delete_pending = 0 AND t1.id_agente_modulo = '.$content['id_agent_module'].'
                AND t1.id_agente = '.$content['id_agent'].' %s',
                $sql_tags_join,
                $where_tags
            );

            $result_tags = db_get_value_sql($sql);

            if (!$result_tags) {
                continue;
            }
        }

        $server_name = $content['server_name'];

        // General reports with 0 period means last value
        // Avoid to overwrite it by template value.
        if (!empty($period) && ($content['type'] !== 'general' && $content['period'] != 0)) {
            $content['period'] = $period;
        }

        $content['style'] = json_decode(io_safe_output($content['style']), true);

        $graphs_to_macro = db_get_all_rows_field_filter(
            'tgraph_source',
            'id_graph',
            $content['id_gs']
        );

        if ($graphs_to_macro === false) {
            $graphs_to_macro = [];
        }

        $modules_to_macro = 0;
        $agents_to_macro = [];
        foreach ($graphs_to_macro as $graph_item) {
            $modules_to_macro++;

            if (in_array('label', $content['style'])) {
                if ($content['id_agent'] == 0) {
                    // Metaconsole connection.
                    if ($metaconsole_on && $server_name != '') {
                        $connection = metaconsole_get_connection($server_name);
                        if (!metaconsole_load_external_db($connection)) {
                            continue;
                        }
                    }

                    array_push(
                        $agents_to_macro,
                        modules_get_agentmodule_agent(
                            $graph_item['id_agent_module']
                        )
                    );
                    if ($metaconsole_on) {
                        // Restore db connection.
                        metaconsole_restore_db();
                    }
                }
            }
        }

        $agents_to_macro_aux = [];
        foreach ($agents_to_macro as $ag) {
            if (!in_array($ag, $agents_to_macro_aux)) {
                $agents_to_macro_aux[$ag] = $ag;
            }
        }

        $agents_to_macro = $agents_to_macro_aux;

        if (!empty($report) && $from_template) {
            $agents_to_macro = $content['id_agent'];
        }

        if (isset($content['style']['name_label'])) {
            // Add macros name.
            $items_label = [];
            $items_label['type'] = $content['type'];
            $items_label['id_agent'] = $content['id_agent'];
            $items_label['id_agent_module'] = $content['id_agent_module'];
            $items_label['modules'] = $modules_to_macro;
            $items_label['agents'] = $agents_to_macro;
            $items_label['visual_format'] = $visual_format;
            $metaconsole_on = is_metaconsole();
            $server_name = $content['server_name'];

            // Metaconsole connection.
            if ($metaconsole_on && $server_name != '') {
                $connection = metaconsole_get_connection($server_name);
                if (!metaconsole_load_external_db($connection)) {
                    continue;
                }
            }

            $items_label['agent_description'] = agents_get_description(
                $content['id_agent']
            );
            $items_label['agent_group'] = agents_get_agent_group(
                $content['id_agent']
            );
            $items_label['agent_address'] = agents_get_address(
                $content['id_agent']
            );
            $items_label['agent_alias'] = agents_get_alias(
                $content['id_agent']
            );

            $modules = agents_get_modules(
                $agent_value,
                [
                    'id_agente_modulo',
                    'nombre',
                    'descripcion',
                ],
                [
                    'id_agente_modulo' => $content['id_agent_module'],
                ]
            );

            $items_label['module_name'] = $modules[$content['id_agent_module']]['nombre'];
            $items_label['module_description'] = $modules[$content['id_agent_module']]['descripcion'];

            if (is_array($content['id_agent'])
                && count($content['id_agent']) != 1
            ) {
                $content['style']['name_label'] = str_replace(
                    '_agent_',
                    count($content['id_agent']).__(' agents'),
                    $content['style']['name_label']
                );
            }

            if (is_array($content['id_agent_module'])
                && count($content['id_agent_module']) != 1
            ) {
                $content['style']['name_label'] = str_replace(
                    '_module_',
                    count($content['id_agent_module']).__(' modules'),
                    $content['style']['name_label']
                );
            }

            if ($metaconsole_on) {
                // Restore db connection.
                metaconsole_restore_db();
            }

            $content['name'] = reporting_label_macro(
                $items_label,
                $content['style']['name_label']
            );
        }

        switch (reporting_get_type($content)) {
            case 'simple_graph':
                $report['contents'][] = reporting_simple_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
            break;

            case 'event_report_log':
                $report['contents'][] = reporting_log(
                    $report,
                    $content
                );
            break;

            case 'increment':
                $report['contents'][] = reporting_increment(
                    $report,
                    $content
                );
            break;

            case 'general':
                $report['contents'][] = io_safe_output(
                    reporting_general(
                        $report,
                        $content
                    )
                );
            break;

            case 'availability':
                $report['contents'][] = io_safe_output(
                    reporting_availability(
                        $report,
                        $content,
                        $date,
                        $time
                    )
                );
            break;

            case 'availability_graph':
                $report['contents'][] = io_safe_output(
                    reporting_availability_graph(
                        $report,
                        $content,
                        $pdf
                    )
                );
            break;

            case 'sql':
                $report['contents'][] = reporting_sql(
                    $report,
                    $content
                );
            break;

            case 'custom_graph':
                $report['contents'][] = reporting_custom_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'custom_graph',
                    $pdf
                );
            break;

            case 'automatic_graph':
                $report['contents'][] = reporting_custom_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'automatic_graph',
                    $pdf
                );
            break;

            case 'text':
                $report['contents'][] = reporting_text(
                    $report,
                    $content
                );
            break;

            case 'url':
                $report['contents'][] = reporting_url(
                    $report,
                    $content,
                    $type
                );
            break;

            case 'max_value':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'max',
                    $pdf
                );
            break;

            case 'avg_value':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'avg',
                    $pdf
                );
            break;

            case 'min_value':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'min',
                    $pdf
                );
            break;

            case 'sumatory':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'sum',
                    $pdf
                );
            break;

            case 'historical_data':
                $report['contents'][] = reporting_historical_data(
                    $report,
                    $content
                );
            break;

            /*
                case 'MTTR':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'MTTR',
                    $pdf
                );
                break;

                case 'MTBF':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'MTBF',
                    $pdf
                );
                break;

                case 'TTO':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'TTO',
                    $pdf
                );
                break;

                case 'TTRT':
                $report['contents'][] = reporting_value(
                    $report,
                    $content,
                    'TTRT',
                    $pdf
                );
                break;
            */
            case 'agent_configuration':
                $report['contents'][] = io_safe_output(
                    reporting_agent_configuration(
                        $report,
                        $content
                    )
                );
            break;

            case 'projection_graph':
                $report['contents'][] = reporting_projection_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    $pdf
                );
            break;

            case 'prediction_date':
                $report['contents'][] = reporting_prediction_date(
                    $report,
                    $content
                );
            break;

            case 'simple_baseline_graph':
                $report['contents'][] = reporting_simple_baseline_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
            break;

            case 'netflow_area':
                $report['contents'][] = reporting_netflow(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'netflow_area',
                    $pdf
                );
            break;

            break;

            case 'netflow_data':
                $report['contents'][] = reporting_netflow(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'netflow_data',
                    $pdf
                );
            break;

            case 'netflow_summary':
                $report['contents'][] = reporting_netflow(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'netflow_summary',
                    $pdf
                );
            break;

            case 'monitor_report':
                $report['contents'][] = reporting_monitor_report(
                    $report,
                    $content
                );
            break;

            case 'sql_graph_vbar':
                $report['contents'][] = reporting_sql_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'sql_graph_vbar'
                );
            break;

            case 'sql_graph_hbar':
                $report['contents'][] = reporting_sql_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'sql_graph_hbar'
                );
            break;

            case 'sql_graph_pie':
                $report['contents'][] = reporting_sql_graph(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    'sql_graph_pie'
                );
            break;

            case 'alert_report_module':
                $report['contents'][] = reporting_alert_report_module(
                    $report,
                    $content
                );
            break;

            case 'alert_report_agent':
                $report['contents'][] = reporting_alert_report_agent(
                    $report,
                    $content
                );
            break;

            case 'alert_report_group':
                $report['contents'][] = reporting_alert_report_group(
                    $report,
                    $content
                );
            break;

            case 'network_interfaces_report':
                $report['contents'][] = reporting_network_interfaces_report(
                    $report,
                    $content,
                    $type,
                    $pdf
                );
            break;

            case 'group_configuration':
                $report['contents'][] = reporting_group_configuration(
                    $report,
                    $content
                );
            break;

            case 'database_serialized':
                $report['contents'][] = reporting_database_serialized(
                    $report,
                    $content
                );
            break;

            case 'group_report':
                $report['contents'][] = reporting_group_report(
                    $report,
                    $content
                );
            break;

            case 'exception':
                $report['contents'][] = reporting_exception(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
            break;

            case 'agent_module':
                $report['contents'][] = reporting_agent_module(
                    $report,
                    $content
                );
            break;

            case 'inventory':
                $report['contents'][] = reporting_inventory(
                    $report,
                    $content,
                    $type
                );
            break;

            case 'inventory_changes':
                $report['contents'][] = reporting_inventory_changes(
                    $report,
                    $content,
                    $type
                );
            break;

            case 'agent_detailed_event':
            case 'event_report_agent':
                $report_control = io_safe_output(
                    reporting_event_report_agent(
                        $report,
                        $content,
                        $type,
                        $force_width_chart,
                        $force_height_chart
                    )
                );
                if ($report_control['total_events'] == 0 && $content['hide_no_data'] == 1) {
                    continue;
                }

                    $report['contents'][] = $report_control;
            break;

            case 'event_report_module':
                $report_control = reporting_event_report_module(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    $pdf
                );
                if ($report_control['total_events'] == 0 && $content['hide_no_data'] == 1) {
                    continue;
                }

                    $report['contents'][] = $report_control;
            break;

            case 'event_report_group':
                $report_control = reporting_event_report_group(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
                if ($report_control['total_events'] == 0 && $content['hide_no_data'] == 1) {
                    continue;
                }

                    $report['contents'][] = $report_control;
            break;

            case 'top_n':
                $report['contents'][] = reporting_event_top_n(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
            break;

            case 'SLA':
                $report['contents'][] = reporting_SLA(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart
                );
            break;

            case 'SLA_monthly':
                $report['contents'][] = reporting_enterprise_sla_monthly_refactoriced(
                    $report,
                    $content
                );
            break;

            case 'SLA_weekly':
                $report['contents'][] = reporting_enterprise_sla_weekly(
                    $report,
                    $content
                );
            break;

            case 'SLA_hourly':
                $report['contents'][] = reporting_enterprise_sla_hourly(
                    $report,
                    $content
                );
            break;

            case 'SLA_services':
                $report['contents'][] = reporting_enterprise_sla_services_refactoriced(
                    $report,
                    $content,
                    $type,
                    $force_width_chart,
                    $force_height_chart,
                    $pdf
                );
            break;

            case 'module_histogram_graph':
                $report['contents'][] = reporting_enterprise_module_histogram_graph(
                    $report,
                    $content,
                    $pdf
                );
            break;

            case 'nt_top_n':
                $report['contents'][] = reporting_nt_top_n_report(
                    $report,
                    $content,
                    $pdf
                );
            break;

            default:
                // Default.
            break;
        }

        $index_content++;
    }

    return reporting_check_structure_report($report);
}


function reporting_SLA(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;
    $return = [];
    $return['type'] = 'SLA';

    if (empty($content['name'])) {
        $content['name'] = __('S.L.A.');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    // Get chart.
    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    $return['id_rc'] = $content['id_rc'];

    $edge_interval = 10;

    if (empty($content['subitems'])) {
        $slas = db_get_all_rows_field_filter(
            'treport_content_sla_combined',
            'id_report_content',
            $content['id_rc']
        );
    } else {
        $slas = $content['subitems'];
    }

    if (empty($slas)) {
        $return['failed'] = __('There are no SLAs defined');
    } else {
        include_once $config['homedir'].'/include/functions_planned_downtimes.php';
        $metaconsole_on = is_metaconsole();

        // checking if needed to show graph or table.
        if ($content['show_graph'] == 0 || $content['show_graph'] == 1) {
            $show_table = 1;
        } else {
            $show_table = 0;
        }

        if ($content['show_graph'] == 1 || $content['show_graph'] == 2) {
            $show_graphs = 1;
        } else {
            $show_graphs = 0;
        }

        $urlImage = ui_get_full_url(false, true, false, false);

        $sla_failed = false;
        $total_SLA = 0;
        $total_result_SLA = 'ok';
        $sla_showed = [];
        $sla_showed_values = [];

        foreach ($slas as $sla) {
            $server_name = $sla['server_name'];
            // Metaconsole connection.
            if ($metaconsole_on && $server_name != '') {
                $connection = metaconsole_get_connection($server_name);
                if (!metaconsole_load_external_db($connection)) {
                    continue;
                }
            }

            if (modules_is_disable_agent($sla['id_agent_module'])
                || modules_is_not_init($sla['id_agent_module'])
            ) {
                if ($metaconsole_on) {
                    // Restore db connection.
                    metaconsole_restore_db();
                }

                continue;
            }

            // Controller min and max == 0 then dinamic min and max critical.
            $dinamic_text = 0;
            if ($sla['sla_min'] == 0 && $sla['sla_max'] == 0) {
                $sla['sla_min'] = null;
                $sla['sla_max'] = null;
                $dinamic_text = __('Dynamic');
            }

            // Controller inverse interval.
            $inverse_interval = 0;
            if ((isset($sla['sla_max'])) && (isset($sla['sla_min']))) {
                if ($sla['sla_max'] < $sla['sla_min']) {
                    $content_sla_max  = $sla['sla_max'];
                    $sla['sla_max']   = $sla['sla_min'];
                    $sla['sla_min']   = $content_sla_max;
                    $inverse_interval = 1;
                    $dinamic_text = __('Inverse');
                }
            }

            // For graph slice for module-interval, if not slice=0.
            if ($show_graphs) {
                $module_interval = modules_get_interval(
                    $sla['id_agent_module']
                );
                $slice = ($content['period'] / $module_interval);
            } else {
                $slice = 1;
            }

            // Call functions sla.
            $sla_array = [];
            $sla_array = reporting_advanced_sla(
                $sla['id_agent_module'],
                ($report['datetime'] - $content['period']),
                $report['datetime'],
                $sla['sla_min'],
                $sla['sla_max'],
                $inverse_interval,
                [
                    '1' => $content['sunday'],
                    '2' => $content['monday'],
                    '3' => $content['tuesday'],
                    '4' => $content['wednesday'],
                    '5' => $content['thursday'],
                    '6' => $content['friday'],
                    '7' => $content['saturday'],
                ],
                $content['time_from'],
                $content['time_to'],
                $slice
            );

            if ($metaconsole_on) {
                // Restore db connection.
                metaconsole_restore_db();
            }

            $server_name = $sla['server_name'];
            // Metaconsole connection.
            if ($metaconsole_on && $server_name != '') {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_connect($connection) != NOERR) {
                    continue;
                }
            }

            if ($show_graphs) {
                $planned_downtimes = reporting_get_planned_downtimes_intervals(
                    $sla['id_agent_module'],
                    ($report['datetime'] - $content['period']),
                    $report['datetime']
                );

                if ((is_array($planned_downtimes))
                    && (count($planned_downtimes) > 0)
                ) {
                    // Sort retrieved planned downtimes.
                    usort(
                        $planned_downtimes,
                        function ($a, $b) {
                            $a = intval($a['date_from']);
                            $b = intval($b['date_from']);
                            if ($a == $b) {
                                return 0;
                            }

                            return ($a < $b) ? (-1) : 1;
                        }
                    );

                    // Compress (overlapped) planned downtimes.
                    $npd = count($planned_downtimes);
                    for ($i = 0; $i < $npd; $i++) {
                        if (isset($planned_downtimes[($i + 1)])) {
                            if ($planned_downtimes[$i]['date_to'] >= $planned_downtimes[($i + 1)]['date_from']) {
                                // Merge.
                                $planned_downtimes[$i]['date_to'] = $planned_downtimes[($i + 1)]['date_to'];
                                array_splice($planned_downtimes, ($i + 1), 1);
                                $npd--;
                            }
                        }
                    }
                } else {
                    $planned_downtimes = null;
                }
            }

            $data = [];
            $data['agent'] = io_safe_output(
                modules_get_agentmodule_agent_alias(
                    $sla['id_agent_module']
                )
            );
            $data['module'] = io_safe_output(
                modules_get_agentmodule_name(
                    $sla['id_agent_module']
                )
            );

            $data['max']          = $sla['sla_max'];
            $data['min']          = $sla['sla_min'];
            $data['sla_limit']    = $sla['sla_limit'];
            $data['dinamic_text'] = $dinamic_text;

            if (isset($sla_array[0])) {
                $data['time_total']      = 0;
                $data['time_ok']         = 0;
                $data['time_error']      = 0;
                $data['time_unknown']    = 0;
                $data['time_not_init']   = 0;
                $data['time_downtime']   = 0;
                $data['checks_total']    = 0;
                $data['checks_ok']       = 0;
                $data['checks_error']    = 0;
                $data['checks_unknown']  = 0;
                $data['checks_not_init'] = 0;

                $raw_graph = [];
                $i = 0;
                foreach ($sla_array as $value_sla) {
                    $data['time_total']      += $value_sla['time_total'];
                    $data['time_ok']         += $value_sla['time_ok'];
                    $data['time_error']      += $value_sla['time_error'];
                    $data['time_unknown']    += $value_sla['time_unknown'];
                    $data['time_downtime']   += $value_sla['time_downtime'];
                    $data['time_not_init']   += $value_sla['time_not_init'];
                    $data['checks_total']    += $value_sla['checks_total'];
                    $data['checks_ok']       += $value_sla['checks_ok'];
                    $data['checks_error']    += $value_sla['checks_error'];
                    $data['checks_unknown']  += $value_sla['checks_unknown'];
                    $data['checks_not_init'] += $value_sla['checks_not_init'];

                    // Generate raw data for graph.
                    if ($value_sla['time_total'] != 0) {
                        if ($value_sla['time_error'] > 0) {
                            // ERR.
                            $raw_graph[$i]['data'] = 3;
                        } else if ($value_sla['time_unknown'] > 0) {
                            // UNKNOWN.
                            $raw_graph[$i]['data'] = 4;
                        } else if ($value_sla['time_not_init'] == $value_sla['time_total']
                        ) {
                            // NOT INIT.
                            $raw_graph[$i]['data'] = 6;
                        } else {
                            $raw_graph[$i]['data'] = 1;
                        }
                    } else {
                        $raw_graph[$i]['data'] = 7;
                    }

                    $raw_graph[$i]['utimestamp'] = (
                        $value_sla['date_to'] - $value_sla['date_from']);

                    if (isset($planned_downtimes)) {
                        foreach ($planned_downtimes as $pd) {
                            if (($value_sla['date_from'] >= $pd['date_from'])
                                && ($value_sla['date_to'] <= $pd['date_to'])
                            ) {
                                $raw_graph[$i]['data'] = 5;
                                // In scheduled downtime.
                                break;
                            }
                        }
                    }

                    $i++;
                }

                $data['sla_value'] = reporting_sla_get_compliance_from_array(
                    $data
                );
                $data['sla_fixed'] = sla_truncate(
                    $data['sla_value'],
                    $config['graph_precision']
                );
            } else {
                // Show only table not divider in slice for defect slice=1.
                $data['time_total']      = $sla_array['time_total'];
                $data['time_ok']         = $sla_array['time_ok'];
                $data['time_error']      = $sla_array['time_error'];
                $data['time_unknown']    = $sla_array['time_unknown'];
                $data['time_downtime']   = $sla_array['time_downtime'];
                $data['time_not_init']   = $sla_array['time_not_init'];
                $data['checks_total']    = $sla_array['checks_total'];
                $data['checks_ok']       = $sla_array['checks_ok'];
                $data['checks_error']    = $sla_array['checks_error'];
                $data['checks_unknown']  = $sla_array['checks_unknown'];
                $data['checks_not_init'] = $sla_array['checks_not_init'];
                $data['sla_value']       = $sla_array['SLA'];
                $data['sla_fixed']       = $sla_array['sla_fixed'];
            }

            // Checks whether or not it meets the SLA.
            if ($data['sla_value'] >= $sla['sla_limit']) {
                $data['sla_status'] = 1;
                $sla_failed = false;
            } else {
                $sla_failed = true;
                $data['sla_status'] = 0;
            }

            // Do not show right modules if 'only_display_wrong' is active.
            if ($content['only_display_wrong'] && $sla_failed == false) {
                continue;
            }

            // Find order.
            $data['order'] = $data['sla_value'];

            if ($show_table) {
                $return['data'][] = $data;
            }

            // Slice graphs calculation.
            if ($show_graphs) {
                $data_init = -1;
                $acum = 0;
                $sum = 0;
                $array_result = [];
                $i = 0;
                foreach ($raw_graph as $key => $value) {
                    if ($data_init == -1) {
                        $data_init = $value['data'];
                        $acum      = $value['utimestamp'];
                        $sum       = $value['data'];
                    } else {
                        if ($data_init == $value['data']) {
                            $acum = ($acum + $value['utimestamp']);
                            $sum  = ($sum + $value['real_data']);
                        } else {
                            $array_result[$i]['data'] = $data_init;
                            $array_result[$i]['utimestamp'] = $acum;
                            $array_result[$i]['real_data'] = $sum;
                            $i++;
                            $data_init = $value['data'];
                            $acum = $value['utimestamp'];
                            $sum = $value['real_data'];
                        }
                    }
                }

                $array_result[$i]['data'] = $data_init;
                $array_result[$i]['utimestamp'] = $acum;
                $array_result[$i]['real_data'] = $sum;

                $dataslice = [];
                $dataslice['agent'] = io_safe_output(
                    modules_get_agentmodule_agent_alias(
                        $sla['id_agent_module']
                    )
                );
                $dataslice['module'] = io_safe_output(
                    modules_get_agentmodule_name(
                        $sla['id_agent_module']
                    )
                );
                $dataslice['sla_value'] = $data['sla_value'];
                $dataslice['order'] = $data['sla_value'];

                $dataslice['chart'] = graph_sla_slicebar(
                    $sla['id_agent_module'],
                    $content['period'],
                    $sla['sla_min'],
                    $sla['sla_max'],
                    $report['datetime'],
                    $content,
                    $content['time_from'],
                    $content['time_to'],
                    100,
                    70,
                    $urlImage,
                    $ttl,
                    $array_result,
                    false
                );

                $return['charts'][] = $dataslice;
            }

            if ($metaconsole_on) {
                // Restore db connection.
                metaconsole_restore_db();
            }
        }

        if ($content['top_n'] == 2) {
            // SLA items sorted descending.
            arsort($return['data']['']);
        } else if ($content['top_n'] == 1) {
            // SLA items sorted ascending.
            asort($sla_showed_values);
        }

        // Order data for ascending or descending.
        if ($content['top_n'] != 0) {
            switch ($content['top_n']) {
                case 1:
                    // Order tables.
                    $temp = [];
                    foreach ($return['data'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] < $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['data'] = $temp;

                    // Order graphs.
                    $temp = [];
                    foreach ($return['charts'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] < $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['charts'] = $temp;
                break;

                case 2:
                    // Order tables.
                    $temp = [];
                    foreach ($return['data'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] > $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['data'] = $temp;

                    // Order graph.
                    $temp = [];
                    foreach ($return['charts'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] > $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['charts'] = $temp;
                break;

                default:
                    // Default.
                break;
            }
        }
    }

    return reporting_check_structure_content($return);
}


function reporting_event_top_n(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;

    $return['type'] = 'top_n';

    if (empty($content['name'])) {
        $content['name'] = __('Top N');
    }

    $return['title'] = $content['name'];
    $top_n = $content['top_n'];

    switch ($top_n) {
        case REPORT_TOP_N_MAX:
            $type_top_n = __('Max');
        break;

        case REPORT_TOP_N_MIN:
            $type_top_n = __('Min');
        break;

        case REPORT_TOP_N_AVG:
        default:
            // If nothing is selected then it will be shown the average data.
            $type_top_n = __('Avg');
        break;
    }

    $return['subtitle'] = __('Top %d', $content['top_n_value']).' - '.$type_top_n;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $order_uptodown = $content['order_uptodown'];

    $top_n_value = $content['top_n_value'];
    $show_graph = $content['show_graph'];

    $return['top_n'] = $content['top_n_value'];

    if (empty($content['subitems'])) {
        // Get all the related data.
        $sql = sprintf(
            'SELECT id_agent_module, server_name
            FROM treport_content_item
            WHERE id_report_content = %d',
            $content['id_rc']
        );

        $tops = db_process_sql($sql);
    } else {
        $tops = $content['subitems'];
    }

    // Get chart.
    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    if (empty($tops)) {
        $return['failed'] = __('There are no Agent/Modules defined');
    } else {
        $data_top = [];

        foreach ($tops as $key => $row) {
            // Metaconsole connection.
            $server_name = $row['server_name'];
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    continue;
                }
            }

            $ag_name = modules_get_agentmodule_agent_alias($row['id_agent_module']);
            $mod_name = modules_get_agentmodule_name($row['id_agent_module']);
            $unit = db_get_value(
                'unit',
                'tagente_modulo',
                'id_agente_modulo',
                $row['id_agent_module']
            );

            switch ($top_n) {
                case REPORT_TOP_N_MAX:
                    $value = reporting_get_agentmodule_data_max($row['id_agent_module'], $content['period']);
                break;

                case REPORT_TOP_N_MIN:
                    $value = reporting_get_agentmodule_data_min($row['id_agent_module'], $content['period']);
                break;

                case REPORT_TOP_N_AVG:
                default:
                    // If nothing is selected then it will be shown the average data.
                    $value = reporting_get_agentmodule_data_average($row['id_agent_module'], $content['period']);
                break;
            }

            // If the returned value from modules_get_agentmodule_data_max/min/avg is false it won't be stored.
            if ($value !== false) {
                $data_top[$key] = $value;
                $id_agent_module[$key] = $row['id_agent_module'];
                $agent_name[$key] = $ag_name;
                $module_name[$key] = $mod_name;
                $units[$key] = $unit;
            }

            // Restore dbconnection.
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                metaconsole_restore_db();
            }
        }

        if (empty($data_top)) {
            $return['failed'] = __('Insuficient data');
        } else {
            $data_return = [];

            // Order to show.
            switch ($order_uptodown) {
                // Descending.
                case 1:
                    array_multisort($data_top, SORT_DESC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
                break;

                // Ascending.
                case 2:
                    array_multisort($data_top, SORT_ASC, $agent_name, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
                break;

                // By agent name or without selection.
                case 0:
                case 3:
                    array_multisort($agent_name, SORT_ASC, $data_top, SORT_ASC, $module_name, SORT_ASC, $id_agent_module, SORT_ASC, $units, SORT_ASC);
                break;

                default:
                    // Default.
                break;
            }

            array_splice($data_top, $top_n_value);
            array_splice($agent_name, $top_n_value);
            array_splice($module_name, $top_n_value);
            array_splice($id_agent_module, $top_n_value);
            array_splice($units, $top_n_value);

            $data_top_values = [];
            $data_top_values['data_top'] = $data_top;
            $data_top_values['agent_name'] = $agent_name;
            $data_top_values['module_name'] = $module_name;
            $data_top_values['id_agent_module'] = $id_agent_module;
            $data_top_values['units'] = $units;

            // Define truncate size depends the graph width.
            $truncate_size = ($width / (4 * ($config['font_size'])) - 1);

            if ($order_uptodown == 1 || $order_uptodown == 2) {
                $i = 0;
                $data_pie_graph = [];
                $data_hbar = [];
                foreach ($data_top as $dt) {
                    $item_name = '';
                    $item_name = ui_print_truncate_text($agent_name[$i], $truncate_size, false, true, false, '...').' - '.ui_print_truncate_text($module_name[$i], $truncate_size, false, true, false, '...');

                    $item_name_key_pie = $item_name;
                    $exist_key = true;
                    while ($exist_key) {
                        if (isset($data_pie_graph[$item_name_key_pie])) {
                            $item_name_key_pie .= ' ';
                        } else {
                            $exist_key = false;
                        }
                    }

                    $item_name_key_hbar = $item_name;
                    $exist_key = true;
                    while ($exist_key) {
                        if (isset($data_hbar[$item_name_key_hbar])) {
                            $item_name_key_hbar = ' '.$item_name_key_hbar;
                        } else {
                            $exist_key = false;
                        }
                    }

                    $data_hbar[$item_name]['g'] = $dt;
                    $data_pie_graph[$item_name] = $dt;

                    if ($show_graph == 0 || $show_graph == 1) {
                        $data = [];
                        $data['agent'] = $agent_name[$i];
                        $data['module'] = $module_name[$i];

                        $data['value'] = $dt;
                        $data['formated_value'] = format_for_graph($dt, 2).' '.$units[$i];
                        $data_return[] = $data;
                    }

                    $i++;
                    if ($i >= $top_n_value) {
                        break;
                    }
                }
            } else if ($order_uptodown == 0 || $order_uptodown == 3) {
                $i = 0;
                $data_pie_graph = [];
                $data_hbar = [];
                foreach ($agent_name as $an) {
                    $item_name = '';
                    $item_name = ui_print_truncate_text(
                        $agent_name[$i],
                        $truncate_size,
                        false,
                        true,
                        false,
                        '...'
                    ).' - '.ui_print_truncate_text(
                        $module_name[$i],
                        $truncate_size,
                        false,
                        true,
                        false,
                        '...'
                    );

                    $item_name_key_pie = $item_name;
                    $exist_key = true;
                    while ($exist_key) {
                        if (isset($data_pie_graph[$item_name_key_pie])) {
                            $item_name_key_pie .= ' ';
                        } else {
                            $exist_key = false;
                        }
                    }

                    $item_name_key_hbar = $item_name;
                    $exist_key = true;
                    while ($exist_key) {
                        if (isset($data_hbar[$item_name_key_hbar])) {
                            $item_name_key_hbar = ' '.$item_name_key_hbar;
                        } else {
                            $exist_key = false;
                        }
                    }

                    $data_pie_graph[$item_name] = $data_top[$i];
                    $data_hbar[$item_name]['g'] = $data_top[$i];
                    if ($show_graph == 0 || $show_graph == 1) {
                        $data = [];
                        $data['agent'] = $an;
                        $data['module'] = $module_name[$i];
                        $data['value'] = $data_top[$i];
                        $data['formated_value'] = format_for_graph($data_top[$i], 2).' '.$units[$i];
                        $data_return[] = $data;
                    }

                    $i++;
                    if ($i >= $top_n_value) {
                        break;
                    }
                }
            }

            $return['charts']['bars'] = null;
            $return['charts']['pie'] = null;

            if ($show_graph != REPORT_TOP_N_ONLY_TABLE) {
                arsort($data_pie_graph);
                $return['charts']['pie'] = pie_graph(
                    $data_pie_graph,
                    $width,
                    $height,
                    __('other'),
                    ui_get_full_url(false, true, false, false).'/',
                    ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                    $config['fontpath'],
                    $config['font_size'],
                    $ttl
                );

                // Display bars graph.
                $return['charts']['bars'] = hbar_graph(
                    $data_hbar,
                    $width,
                    (count($data_hbar) * 50),
                    [],
                    [],
                    '',
                    '',
                    false,
                    false,
                    $config['homedir'].'/images/logo_vertical_water.png',
                    $config['fontpath'],
                    $config['font_size'],
                    true,
                    $ttl,
                    $config['homeurl'],
                    'white',
                    'black'
                );
            }

            $return['resume'] = null;

            if ($content['show_resume'] && count($data_top_values) > 0) {
                // Get the very first not null value.
                $i = 0;
                do {
                    $min = $data_top_values['data_top'][$i];
                    $i++;
                } while ($min === false && $i < count($data_top_values));
                $max = $min;
                $avg = 0;

                $i = 0;
                foreach ($data_top_values['data_top'] as $key => $dtv) {
                    if ($dtv < $min) {
                        $min = $dtv;
                    }

                    if ($dtv > $max) {
                        $max = $dtv;
                    }

                    $avg += $dtv;
                    $i++;
                }

                $avg = ($avg / $i);

                $return['resume']['min']['value'] = $min;
                $return['resume']['min']['formated_value'] = format_for_graph($min, 2);
                $return['resume']['avg']['value'] = $avg;
                $return['resume']['avg']['formated_value'] = format_for_graph($avg, 2);
                $return['resume']['max']['value'] = $max;
                $return['resume']['max']['formated_value'] = format_for_graph($max, 2);
            }

            $return['data'] = $data_return;
        }
    }

    return reporting_check_structure_content($return);
}


function reporting_event_report_group(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;

    $return['type'] = 'event_report_group';

    if (empty($content['name'])) {
        $content['name'] = __('Event Report Group');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $history = false;
    if ($config['history_event_enabled']) {
        $history = true;
    }

    $return['title'] = $content['name'];
    $return['subtitle'] = groups_get_name($content['id_group'], true);
    if (!empty($content['style']['event_filter_search'])) {
        $return['subtitle'] .= ' ('.$content['style']['event_filter_search'].')';
    }

    $return['description'] = $content['description'];
    $return['show_extended_events'] = $content['show_extended_events'];
    $return['date'] = reporting_get_date_text($report, $content);

    $event_filter = $content['style'];
    $return['show_summary_group'] = $event_filter['show_summary_group'];
    // Filter.
    $show_summary_group         = $event_filter['show_summary_group'];
    $filter_event_severity      = json_decode($event_filter['filter_event_severity'], true);
    $filter_event_type          = json_decode($event_filter['filter_event_type'], true);
    $filter_event_status        = json_decode($event_filter['filter_event_status'], true);
    $filter_event_filter_search = $event_filter['event_filter_search'];

    // Graphs.
    $event_graph_by_agent                 = $event_filter['event_graph_by_agent'];
    $event_graph_by_user_validator        = $event_filter['event_graph_by_user_validator'];
    $event_graph_by_criticity             = $event_filter['event_graph_by_criticity'];
    $event_graph_validated_vs_unvalidated = $event_filter['event_graph_validated_vs_unvalidated'];

    $data = events_get_agent(
        false,
        $content['period'],
        $report['datetime'],
        $history,
        $show_summary_group,
        $filter_event_severity,
        $filter_event_type,
        $filter_event_status,
        $filter_event_filter_search,
        $content['id_group'],
        true
    );

    if (empty($data)) {
        $return['failed'] = __('No events');
    } else {
        $return['data'] = array_reverse($data);
    }

    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    $return['chart']['by_agent'] = null;
    $return['chart']['by_user_validator'] = null;
    $return['chart']['by_criticity'] = null;
    $return['chart']['validated_vs_unvalidated'] = null;
    $server_name = $content['server_name'];
    if (is_metaconsole() && $server_name != '') {
        $metaconsole_dbtable = true;
    } else {
        $metaconsole_dbtable = false;
    }

    if ($event_graph_by_agent) {
        $data_graph = events_get_count_events_by_agent(
            $content['id_group'],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $return['chart']['by_agent'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl
        );
    }

    if ($event_graph_by_user_validator) {
        $data_graph = events_get_count_events_validated_by_user(
            ['id_group' => $content['id_group']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $return['chart']['by_user_validator'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl
        );
    }

    if ($event_graph_by_criticity) {
        $data_graph = events_get_count_events_by_criticity(
            ['id_group' => $content['id_group']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $colors = get_criticity_pie_colors($data_graph);

        $return['chart']['by_criticity'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl,
            false,
            $colors
        );
    }

    if ($event_graph_validated_vs_unvalidated) {
        $data_graph = events_get_count_events_validated(
            ['id_group' => $content['id_group']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $return['chart']['validated_vs_unvalidated'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl
        );
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    // total_events.
    if ($return['data'] != '') {
        $return['total_events'] = count($return['data']);
    } else {
        $return['total_events'] = 0;
    }

    return reporting_check_structure_content($return);
}


/**
 * Events for module reports.
 *
 * @param array   $report             Report info.
 * @param array   $content            Content info.
 * @param string  $type               Type retun report.
 * @param integer $force_width_chart  Width chart.
 * @param integer $force_height_chart Height chart.
 * @param integer $pdf                If pdf report.
 *
 * @return array
 */
function reporting_event_report_module(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null,
    $pdf=0
) {
    global $config;

    if ($pdf) {
        $ttl = 2;
    } else {
        $ttl = 1;
    }

    $return['type'] = 'event_report_module';

    if (empty($content['name'])) {
        $content['name'] = __('Event Report Module');
    }

    $id_server = false;
    if (is_metaconsole()) {
        $id_server = metaconsole_get_id_server($content['server_name']);
        metaconsole_connect(null, $id_server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );
    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $content['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.io_safe_output($module_name);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';

    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    if (is_metaconsole()) {
        metaconsole_restore_db();
    }

    $return['description'] = $content['description'];
    $return['show_extended_events'] = $content['show_extended_events'];
    $return['date'] = reporting_get_date_text($report, $content);

    $event_filter = $content['style'];
    $return['show_summary_group'] = $event_filter['show_summary_group'];
    // Filter.
    $show_summary_group = $event_filter['show_summary_group'];
    $filter_event_severity = json_decode(
        $event_filter['filter_event_severity'],
        true
    );
    $filter_event_type = json_decode(
        $event_filter['filter_event_type'],
        true
    );
    $filter_event_status = json_decode(
        $event_filter['filter_event_status'],
        true
    );
    $filter_event_filter_search = $event_filter['event_filter_search'];

    // Graphs.
    $event_graph_by_user_validator = $event_filter['event_graph_by_user_validator'];
    $event_graph_by_criticity = $event_filter['event_graph_by_criticity'];
    $event_graph_validated_vs_unvalidated = $event_filter['event_graph_validated_vs_unvalidated'];

    $server_name = $content['server_name'];
    if (is_metaconsole() && $server_name != '') {
        $metaconsole_dbtable = true;
    } else {
        $metaconsole_dbtable = false;
    }

    // Data events.
    $data = reporting_get_module_detailed_event(
        $content['id_agent_module'],
        $content['period'],
        $report['datetime'],
        $show_summary_group,
        $filter_event_severity,
        $filter_event_type,
        $filter_event_status,
        $filter_event_filter_search,
        $force_width_chart,
        $event_graph_by_user_validator,
        $event_graph_by_criticity,
        $event_graph_validated_vs_unvalidated,
        $ttl,
        $id_server,
        $metaconsole_dbtable
    );

    if (empty($data)) {
        $return['failed'] = __('No events');
    } else {
        $return['data'] = array_reverse($data);
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    // Total_events.
    if ($return['data'][0]['data'] != '') {
        $return['total_events'] = count($return['data'][0]['data']);
    } else {
        $return['total_events'] = 0;
    }

    return reporting_check_structure_content($return);
}


function reporting_inventory_changes($report, $content, $type)
{
    global $config;

    $return['type'] = 'inventory_changes';

    if (empty($content['name'])) {
        $content['name'] = __('Inventory Changes');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $return['title'] = $content['name'];
    $return['subtitle'] = agents_get_alias($content['id_agent']);
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $es = json_decode($content['external_source'], true);

    $id_agent = $es['id_agents'];
    $module_name = $es['inventory_modules'];

    switch ($type) {
        case 'data':
            $inventory_changes = inventory_get_changes(
                $id_agent,
                $module_name,
                ($report['datetime'] - $content['period']),
                $report['datetime'],
                'csv'
            );
        break;

        default:
            $inventory_changes = inventory_get_changes(
                $id_agent,
                $module_name,
                ($report['datetime'] - $content['period']),
                $report['datetime'],
                'array'
            );
        break;
    }

    $return['data'] = [];

    if ($inventory_changes == ERR_NODATA) {
        $return['failed'] = __('No changes found.');
    } else {
        $return['data'] = $inventory_changes;
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_inventory($report, $content, $type)
{
    global $config;

    $es = json_decode($content['external_source'], true);

    $return['type'] = 'inventory';

    if (empty($content['name'])) {
        $content['name'] = __('Inventory');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $es = json_decode($content['external_source'], true);

    $id_agent = $es['id_agents'];
    $module_name = $es['inventory_modules'];
    if (empty($module_name)) {
        $module_name = [0 => 0];
    }

    $date = $es['date'];
    $description = $content['description'];

    switch ($type) {
        case 'data':
            $inventory_data = inventory_get_data(
                (array) $id_agent,
                (array) $module_name,
                $date,
                '',
                false,
                'csv'
            );
        break;

        default:
            $inventory_data = inventory_get_data(
                (array) $id_agent,
                (array) $module_name,
                $date,
                '',
                false,
                'hash'
            );
        break;
    }

    if ($inventory_data == ERR_NODATA) {
        $return['failed'] = __('No data found.');
    } else {
        $return['data'] = $inventory_data;
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_agent_module($report, $content)
{
    global $config;
    $agents_and_modules = json_decode($content['external_source'], true);
    $agents = [];
    $agents = $agents_and_modules['id_agents'];
    $modules = $agents_and_modules['module'];
    $id_group = $content['id_group'];
    $id_module_group = $content['id_module_group'];

    $return['type'] = 'agent_module';

    if (empty($content['name'])) {
        $content['name'] = __('Agent/Modules');
    }

    $return['title'] = $content['name'];
    $group_name = groups_get_name($content['id_group'], true);
    if ($content['id_module_group'] == 0) {
        $module_group_name = __('All');
    } else {
        $module_group_name = db_get_value(
            'name',
            'tmodule_group',
            'id_mg',
            $content['id_module_group']
        );
    }

    $return['subtitle'] = $group_name.' - '.$module_group_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';

    $return['data'] = [];

    $modules_by_name = [];
    $cont = 0;

    foreach ($modules as $modul_id) {
        $modules_by_name[$cont]['name'] = io_safe_output(modules_get_agentmodule_name($modul_id));
        $modules_by_name[$cont]['id'] = $modul_id;
        $cont ++;
    }

    if ($modules_by_name == false || $agents == false) {
        $return['failed'] = __('There are no agents with modules');
    } else {
        foreach ($agents as $agent) {
            $row = [];
            $row['agent_status'][$agent] = agents_get_status($agent);
            $row['agent_name'] = io_safe_output(agents_get_alias($agent));
            $agent_modules = agents_get_modules($agent);

            $row['modules'] = [];
            foreach ($modules_by_name as $module) {
                if (array_key_exists($module['id'], $agent_modules)) {
                    $row['modules'][$module['name']] = modules_get_agentmodule_status($module['id']);
                } else {
                    if (!array_key_exists($module['name'], $row['modules'])) {
                        $row['modules'][$module['name']] = null;
                    }
                }
            }

            $return['data'][] = $row;
        }
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_exception(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;

    $return['type'] = 'exception';

    if (empty($content['name'])) {
        $content['name'] = __('Exception');
    }

    $order_uptodown = $content['order_uptodown'];
    $exception_condition_value = $content['exception_condition_value'];
    $show_graph = $content['show_graph'];

    $formated_exception_value = $exception_condition_value;
    if (is_numeric($exception_condition_value)) {
        $formated_exception_value = format_for_graph(
            $exception_condition_value,
            2
        );
    }

    $return['title'] = $content['name'];
    $exception_condition = $content['exception_condition'];
    switch ($exception_condition) {
        case REPORT_EXCEPTION_CONDITION_EVERYTHING:
            $return['subtitle'] = __('Exception - Everything');
            $return['subtype'] = __('Everything');
        break;

        case REPORT_EXCEPTION_CONDITION_GE:
            $return['subtitle'] = sprintf(
                __('Exception - Modules over or equal to %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Modules over or equal to %s');
        break;

        case REPORT_EXCEPTION_CONDITION_LE:
            $return['subtitle'] = sprintf(
                __('Exception - Modules under or equal to %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Modules under or equal to %s');
        break;

        case REPORT_EXCEPTION_CONDITION_L:
            $return['subtitle'] = sprintf(
                __('Exception - Modules under %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Modules under %s');
        break;

        case REPORT_EXCEPTION_CONDITION_G:
            $return['subtitle'] = sprintf(
                __('Exception - Modules over %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Modules over %s');
        break;

        case REPORT_EXCEPTION_CONDITION_E:
            $return['subtitle'] = sprintf(
                __('Exception - Equal to %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Equal to %s');
        break;

        case REPORT_EXCEPTION_CONDITION_NE:
            $return['subtitle'] = sprintf(
                __('Exception - Not equal to %s'),
                $formated_exception_value
            );
            $return['subtype'] = __('Not equal to %s');
        break;

        case REPORT_EXCEPTION_CONDITION_OK:
            $return['subtitle'] = __('Exception - Modules at normal status');
            $return['subtype'] = __('Modules at normal status');
        break;

        case REPORT_EXCEPTION_CONDITION_NOT_OK:
            $return['subtitle'] = __('Exception - Modules at critical or warning status');
            $return['subtype'] = __('Modules at critical or warning status');
        break;

        default:
            // Default.
        break;
    }

    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['data'] = [];
    $return['chart'] = [];
    $return['resume'] = [];

    if (empty($content['subitems'])) {
        // Get all the related data.
        $sql = sprintf(
            '
            SELECT id_agent_module, server_name, operation
            FROM treport_content_item
            WHERE id_report_content = %d',
            $content['id_rc']
        );

        $exceptions = db_process_sql($sql);
    } else {
        $exceptions = $content['subitems'];
    }

    if ($exceptions === false) {
        $return['failed'] = __('There are no Agent/Modules defined');
    } else {
        // Get the very first not null value.
        $i = 0;
        do {
            // Metaconsole connection.
            $server_name = $exceptions[$i]['server_name'];
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    continue;
                }
            }

            if ($content['period'] == 0) {
                $min = modules_get_last_value($exceptions[$i]['id_agent_module']);
            } else {
                switch ($exceptions[$i]['operation']) {
                    case 'avg':
                        $min = reporting_get_agentmodule_data_average(
                            $exceptions[$i]['id_agent_module'],
                            $content['period']
                        );
                    break;

                    case 'max':
                        $min = reporting_get_agentmodule_data_max(
                            $exceptions[$i]['id_agent_module'],
                            $content['period']
                        );
                    break;

                    case 'min':
                        $min = reporting_get_agentmodule_data_min(
                            $exceptions[$i]['id_agent_module'],
                            $content['period']
                        );
                    break;

                    default:
                        // Default.
                    break;
                }
            }

            $i++;

            // Restore dbconnection.
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                metaconsole_restore_db();
            }
        } while ($min === false && $i < count($exceptions));
        $max = $min;
        $avg = 0;

        $items = [];

        $i = 0;
        foreach ($exceptions as $exc) {
            // Metaconsole connection.
            $server_name = $exc['server_name'];
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    continue;
                }
            }

            $ag_name = modules_get_agentmodule_agent_name($exc['id_agent_module']);
            $ag_alias = modules_get_agentmodule_agent_alias($exc['id_agent_module']);
            $mod_name = modules_get_agentmodule_name($exc['id_agent_module']);
            $unit = db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', $exc['id_agent_module']);

            if ($content['period'] == 0) {
                $value = modules_get_last_value($exceptions[$i]['id_agent_module']);
            } else {
                switch ($exc['operation']) {
                    case 'avg':
                        $value = reporting_get_agentmodule_data_average($exc['id_agent_module'], $content['period']);
                    break;

                    case 'max':
                        $value = reporting_get_agentmodule_data_max($exc['id_agent_module'], $content['period']);
                    break;

                    case 'min':
                        $value = reporting_get_agentmodule_data_min($exc['id_agent_module'], $content['period']);
                    break;
                }
            }

            if ($value !== false) {
                if ($value > $max) {
                    $max = $value;
                }

                if ($value < $min) {
                    $min = $value;
                }

                $avg += $value;

                // Skips
                switch ($exception_condition) {
                    case REPORT_EXCEPTION_CONDITION_EVERYTHING:
                    break;

                    case REPORT_EXCEPTION_CONDITION_GE:
                        if ($value < $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_LE:
                        if ($value > $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_L:
                        if ($value > $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_G:
                        if ($value < $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_E:
                        if ($value != $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_NE:
                        if ($value == $exception_condition_value) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_OK:
                        if (modules_get_agentmodule_status($exc['id_agent_module']) != 0) {
                            continue 2;
                        }
                    break;

                    case REPORT_EXCEPTION_CONDITION_NOT_OK:
                        if (modules_get_agentmodule_status($exc['id_agent_module']) == 0) {
                            continue 2;
                        }
                    break;
                }

                $item = [];
                $item['value'] = $value;
                $item['module_id'] = $exc['id_agent_module'];
                $item['module'] = $mod_name;
                $item['agent'] = $ag_alias;
                $item['unit'] = $unit;
                if ($exc['operation'] == 'avg') {
                    $item['operation'] = 'rate';
                } else {
                    $item['operation'] = $exc['operation'];
                }

                $items[] = $item;

                $i++;
            }

            // Restore dbconnection
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                metaconsole_restore_db();
            }
        }

        if ($i == 0) {
            switch ($exception_condition) {
                case REPORT_EXCEPTION_CONDITION_EVERYTHING:
                    $return['failed'] = __('There are no Modules under those conditions.');
                break;

                case REPORT_EXCEPTION_CONDITION_GE:
                    $return['failed'] = __('There are no Modules over or equal to %s.', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_LE:
                    $return['failed'] = __('There are no Modules less or equal to %s.', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_L:
                    $return['failed'] = __('There are no Modules less %s.', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_G:
                    $return['failed'] = __('There are no Modules over %s.', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_E:
                    $return['failed'] = __('There are no Modules equal to %s', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_NE:
                    $return['failed'] = __('There are no Modules not equal to %s', $exception_condition_value);
                break;

                case REPORT_EXCEPTION_CONDITION_OK:
                    $return['failed'] = __('There are no Modules normal status');
                break;

                case REPORT_EXCEPTION_CONDITION_NOT_OK:
                    $return['failed'] = __('There are no Modules at critial or warning status');
                break;
            }
        } else {
            $avg = ($avg / $i);

            // Sort the items
            $sort_number = function ($a, $b, $sort=SORT_ASC) {
                if ($a == $b) {
                    return 0;
                } else if ($a > $b) {
                    return ($sort === SORT_ASC) ? 1 : -1;
                } else {
                    return ($sort === SORT_ASC) ? -1 : 1;
                }
            };
            $sort_string = function ($a, $b, $sort=SORT_ASC) {
                if ($sort === SORT_ASC) {
                    return strcasecmp($a, $b);
                } else {
                    return strcasecmp($b, $a);
                }
            };
            usort(
                $items,
                function ($a, $b) use ($order_uptodown, $sort_number, $sort_string) {
                    switch ($order_uptodown) {
                        case 1:
                        case 2:
                            if ($a['value'] == $b['value']) {
                                if ($a['agent'] == $b['agent']) {
                                    if ($a['module'] == $b['module']) {
                                        return $sort_number($a['module_id'], $b['module_id']);
                                    }

                                    return $sort_string($a['module'], $b['module']);
                                }

                                return $sort_string($a['agent'], $b['agent']);
                            }
                        return $sort_number($a['value'], $b['value'], ($order_uptodown == 1) ? SORT_DESC : SORT_ASC);

                        // Order by agent name or without selection
                        case 0:
                        case 3:
                            if ($a['agent'] == $b['agent']) {
                                if ($a['value'] == $b['value']) {
                                    if ($a['module'] == $b['module']) {
                                        return $sort_number($a['module_id'], $b['module_id']);
                                    }

                                    return $sort_string($a['module'], $b['module']);
                                }

                                return $sort_number($a['value'], $b['value']);
                            }
                        return $sort_string($a['agent'], $b['agent']);
                    }
                }
            );

            $data_pie_graph = [];
            $data_hbar = [];
            foreach ($items as $key => $item) {
                if ($show_graph == 1 || $show_graph == 2) {
                    // TODO: Find a better way to show the graphs
                    $data_hbar[$item['agent'].' - '.$item['operation']]['g'] = $item['value'];
                    $data_pie_graph[$item['agent'].' - '.$item['operation']] = $item['value'];
                }

                if ($show_graph == 0 || $show_graph == 1) {
                    $data = [];
                    $data['agent'] = $item['agent'];
                    $data['module'] = $item['module'];
                    $data['operation'] = __($item['operation']);
                    $data['value'] = $item['value'];
                    $data['formated_value'] = format_for_graph($item['value'], 2).' '.$item['unit'];
                    $return['data'][] = $data;
                }
            }

            if ($show_graph == 1 || $show_graph == 2) {
                reporting_set_conf_charts(
                    $width,
                    $height,
                    $only_image,
                    $type,
                    $content,
                    $ttl
                );

                if (!empty($force_width_chart)) {
                    $width = $force_width_chart;
                }

                if (!empty($force_height_chart)) {
                    $height = $force_height_chart;
                }

                $return['chart']['pie'] = pie_graph(
                    $data_pie_graph,
                    600,
                    150,
                    __('other'),
                    ui_get_full_url(false, false, false, false),
                    ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                    $config['fontpath'],
                    $config['font_size'],
                    $ttl
                );

                $params = [
                    'chart_data'      => $data_hbar,
                    'width'           => 600,
                    'height'          => (25 * count($data_hbar)),
                    'color'           => [],
                    'legend'          => [],
                    'long_index'      => [],
                    'no_data_image'   => ui_get_full_url('images/image_problem_area_small.png', false, false, false),
                    'xaxisname'       => '',
                    'yaxisname'       => '',
                    'water_mark'      => ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                    'font'            => '',
                    'font_size'       => '',
                    'unit'            => '',
                    'ttl'             => $ttl,
                    'homeurl'         => ui_get_full_url(false, false, false, false),
                    'backgroundColor' => 'white',
                ];
                $return['chart']['hbar'] = call_user_func_array(
                    'hbar_graph',
                    $params
                );
            }

            if ($content['show_resume'] && $i > 0) {
                $return['resume']['min']['value'] = $min;
                $return['resume']['min']['formated_value'] = format_for_graph($min, 2);
                $return['resume']['max']['value'] = $max;
                $return['resume']['max']['formated_value'] = format_for_graph($max, 2);
                $return['resume']['avg']['value'] = $avg;
                $return['resume']['avg']['formated_value'] = format_for_graph($avg, 2);
            }
        }
    }

    return reporting_check_structure_content($return);
}


function reporting_group_report($report, $content)
{
    global $config;

    $metaconsole_on = ($config['metaconsole'] == 1) && is_metaconsole();

    $return['type'] = 'group_report';

    if (empty($content['name'])) {
        $content['name'] = __('Group Report');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $return['title'] = $content['name'];
    $return['subtitle'] = groups_get_name($content['id_group'], true);
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['data'] = [];

    $events = events_get_agent(
        false,
        $content['period'],
        $report['datetime'],
        false,
        true,
        false,
        false,
        false,
        false,
        $content['id_group'],
        true
    );

    if (empty($events)) {
        $events = [];
    }

    $return['data']['count_events'] = count($events);

    $return['data']['group_stats'] = reporting_get_group_stats($content['id_group']);

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


/**
 * Create data report event agent.
 *
 * @param array   $report             Data report.
 * @param array   $content            Content report.
 * @param string  $type               Type report.
 * @param integer $force_width_chart  Force width.
 * @param integer $force_height_chart Force height.
 *
 * @return array Data.
 */
function reporting_event_report_agent(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;

    $return['type'] = 'event_report_agent';

    if (empty($content['name'])) {
        $content['name'] = __('Event Report Agent');
    }

    $history = false;
    if ($config['history_event_enabled']) {
        $history = true;
    }

    $id_server = false;
    if (is_metaconsole()) {
        $id_server = metaconsole_get_id_server($content['server_name']);
        metaconsole_connect(null, $id_server);
    }

    $id_agent = $content['id_agent'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);

    $items_label = [
        'type'              => $return['type'],
        'id_agent'          => $id_agent,
        'agent_description' => $agent_description,
        'agent_group'       => $agent_group,
        'agent_address'     => $agent_address,
        'agent_alias'       => $agent_alias,
    ];

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    $label = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($label != '') {
        $label = reporting_label_macro(
            $items_label,
            $label
        );
    }

    $return['label'] = $label;
    $return['title'] = $content['name'];
    $return['subtitle'] = io_safe_output($agent_alias);
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['show_summary_group'] = $content['style']['show_summary_group'];
    $return['show_extended_events'] = $content['show_extended_events'];

    $style = $content['style'];

    // Filter.
    $show_summary_group = $style['show_summary_group'];
    $filter_event_severity = json_decode($style['filter_event_severity'], true);
    $filter_event_type = json_decode($style['filter_event_type'], true);
    $filter_event_status = json_decode($style['filter_event_status'], true);
    $filter_event_filter_search = $style['event_filter_search'];

    // Graph.
    $event_graph_by_user_validator = $style['event_graph_by_user_validator'];
    $event_graph_by_criticity = $style['event_graph_by_criticity'];
    $event_graph_validated_vs_unvalidated = $style['event_graph_validated_vs_unvalidated'];

    $return['data'] = reporting_get_agents_detailed_event(
        $content['id_agent'],
        $content['period'],
        $report['datetime'],
        true,
        true,
        $history,
        $show_summary_group,
        $filter_event_severity,
        $filter_event_type,
        $filter_event_status,
        $filter_event_filter_search
    );

    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    $return['chart']['by_user_validator'] = null;
    $return['chart']['by_criticity'] = null;
    $return['chart']['validated_vs_unvalidated'] = null;

    $server_name = $content['server_name'];
    if (is_metaconsole() && $server_name != '') {
        $metaconsole_dbtable = true;
    } else {
        $metaconsole_dbtable = false;
    }

    if ($event_graph_by_user_validator) {
        $data_graph = events_get_count_events_validated_by_user(
            ['id_agent' => $content['id_agent']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $return['chart']['by_user_validator'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl
        );
    }

    if ($event_graph_by_criticity) {
        $data_graph = events_get_count_events_by_criticity(
            ['id_agent' => $content['id_agent']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $colors = get_criticity_pie_colors($data_graph);

        $return['chart']['by_criticity'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl,
            false,
            $colors
        );
    }

    if ($event_graph_validated_vs_unvalidated) {
        $data_graph = events_get_count_events_validated(
            ['id_agent' => $content['id_agent']],
            $content['period'],
            $report['datetime'],
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            $metaconsole_dbtable
        );

        $return['chart']['validated_vs_unvalidated'] = pie_graph(
            $data_graph,
            500,
            150,
            __('other'),
            ui_get_full_url(false, false, false, false),
            ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
            $config['fontpath'],
            $config['font_size'],
            $ttl
        );
    }

    // Total events.
    if ($return['data'] != '') {
        $return['total_events'] = count($return['data']);
    } else {
        $return['total_events'] = 0;
    }

    return reporting_check_structure_content($return);
}


/**
 * Show historical data.
 *
 * @param array $report  Data report.
 * @param array $content Content report.
 *
 * @return array
 */
function reporting_historical_data($report, $content)
{
    global $config;

    $return['type'] = 'historical_data';
    $period = $content['period'];
    $date_limit = ($report['datetime'] - $period);
    if (empty($content['name'])) {
        $content['name'] = __('Historical data');
    }

    if (is_metaconsole()) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        $connection = metaconsole_connect($server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = io_safe_output(agents_get_alias($id_agent));
    $module_name = io_safe_output(
        modules_get_agentmodule_name(
            $id_agent_module
        )
    );
    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $return['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    $return['keys'] = [
        __('Date'),
        __('Data'),
    ];

    $module_type = db_get_value_filter(
        'id_tipo_modulo',
        'tagente_modulo',
        ['id_agente_modulo' => $content['id_agent_module']]
    );

    $result = [];
    switch ($module_type) {
        case 3:
        case 17:
        case 23:
        case 33:
            $result = db_get_all_rows_sql(
                'SELECT *
                FROM tagente_datos_string
                WHERE id_agente_modulo ='.$content['id_agent_module'].'
                    AND utimestamp >'.$date_limit.'
                    AND utimestamp <='.$report['datetime'],
                true
            );
        break;

        default:
            $result = db_get_all_rows_sql(
                'SELECT *
                FROM tagente_datos
                WHERE id_agente_modulo ='.$content['id_agent_module'].'
                    AND utimestamp >'.$date_limit.'
                    AND utimestamp <='.$report['datetime'],
                true
            );
        break;
    }

    $data = [];
    foreach ($result as $row) {
        $data[] = [
            __('Date') => date($config['date_format'], $row['utimestamp']),
            __('Data') => $row['datos'],
        ];
    }

    $return['data'] = $data;

    if (is_metaconsole() && $connection > 0) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


/**
 * Show data serialized.
 *
 * @param array $report  Data report.
 * @param array $content Content report.
 *
 * @return array
 */
function reporting_database_serialized($report, $content)
{
    global $config;

    $return['type'] = 'database_serialized';

    if (empty($content['name'])) {
        $content['name'] = __('Database Serialized');
    }

    if (is_metaconsole()) {
        $id_meta = metaconsole_get_id_server($content['server_name']);
        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );

    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );

    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $return['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $keys = [];
    if (isset($content['header_definition']) && ($content['header_definition'] != '')) {
        $keys = explode('|', $content['header_definition']);
    }

    $return['keys'] = $keys;
    $return['agent_name'] = $agent_alias;
    $return['module_name'] = $module_name;

    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    $datelimit = ($report['datetime'] - $content['period']);
    $search_in_history_db = db_search_in_history_db($datelimit);

    // This query gets information from the default and the historic database.
    $result = db_get_all_rows_sql(
        'SELECT *
        FROM tagente_datos
        WHERE id_agente_modulo = '.$content['id_agent_module'].'
            AND utimestamp > '.$datelimit.'
            AND utimestamp <= '.$report['datetime'],
        $search_in_history_db
    );

    // Adds string data if there is no numeric data.
    if ((count($result) < 0) || (!$result)) {
        // This query gets information from the default and the historic database.
        $result = db_get_all_rows_sql(
            'SELECT *
            FROM tagente_datos_string
            WHERE id_agente_modulo = '.$content['id_agent_module'].'
                AND utimestamp > '.$datelimit.'
                AND utimestamp <= '.$report['datetime'],
            $search_in_history_db
        );
    }

    if ($result === false) {
        $result = [];
    }

    $data = [];
    foreach ($result as $row) {
        $date = date($config['date_format'], $row['utimestamp']);
        $serialized_data = $row['datos'];

        // Cut line by line.
        if (empty($content['line_separator'])
            || empty($serialized_data)
        ) {
            $rowsUnserialize = [$row['datos']];
        } else {
            $rowsUnserialize = explode(
                $content['line_separator'],
                $serialized_data
            );
        }

        foreach ($rowsUnserialize as $rowUnser) {
            $row = [];

            $row['date'] = $date;
            $row['data'] = [];

            if (empty($content['column_separator'])) {
                if (empty($keys)) {
                    $row['data'][][] = $rowUnser;
                } else {
                    $row['data'][][$keys[0]] = $rowUnser;
                }
            } else {
                $columnsUnserialize = explode(
                    $content['column_separator'],
                    $rowUnser
                );

                $i = 0;
                $temp_row = [];
                foreach ($columnsUnserialize as $cell) {
                    if (isset($keys[$i])) {
                        $temp_row[$keys[$i]] = $cell;
                    } else {
                        $temp_row[] = $cell;
                    }

                    $i++;
                }

                $row['data'][] = $temp_row;
            }

            $data[] = $row;
        }
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    $return['data'] = $data;

    return reporting_check_structure_content($return);
}


function reporting_group_configuration($report, $content)
{
    global $config;

    $return['type'] = 'group_configuration';

    if (empty($content['name'])) {
        $content['name'] = __('Group configuration');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $group_name = groups_get_name($content['id_group'], true);

    $return['title'] = $content['name'];
    $return['subtitle'] = $group_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['id_group'] = $content['id_group'];

    if ($content['id_group'] == 0) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $sql = 'SELECT * FROM tagente;';
            break;

            case 'oracle':
                $sql = 'SELECT * FROM tagente';
            break;
        }
    } else {
        $sql = '
            SELECT *
            FROM tagente
            WHERE id_grupo='.$content['id_group'];
    }

    $agents_list = db_get_all_rows_sql($sql);
    if ($agents_list === false) {
        $agents_list = [];
    }

    $return['data'] = [];
    foreach ($agents_list as $agent) {
        $content_agent = $content;
        $content_agent['id_agent'] = $agent['id_agente'];

        // Restore the connection to metaconsole
        // because into the function reporting_agent_configuration
        // connect to metaconsole.
        if ($config['metaconsole']) {
            metaconsole_restore_db();
        }

        $agent_report = reporting_agent_configuration(
            $report,
            $content_agent
        );

        $return['data'][] = $agent_report['data'];
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_network_interfaces_report($report, $content, $type='dinamic', $pdf=0)
{
    global $config;

    $return['type'] = 'network_interfaces_report';

    if (empty($content['name'])) {
        $content['name'] = __('Network interfaces report');
    }

    if (isset($content['style']['fullscale'])) {
        $fullscale = (bool) $content['style']['fullscale'];
    }

    $group_name = groups_get_name($content['id_group']);

    $return['title'] = $content['name'];
    $return['subtitle'] = $group_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    include_once $config['homedir'].'/include/functions_custom_graphs.php';

    $filter = [
        'id_grupo' => $content['id_group'],
        'disabled' => 0,
    ];

    $return['failed'] = null;
    $return['data'] = [];

    if ($config['metaconsole']) {
        $server_names = metaconsole_get_connection_names();
        if (isset($server_names) && is_array($server_names)) {
            foreach ($server_names as $key => $value) {
                $id_meta = metaconsole_get_id_server($value);
                $connection = metaconsole_get_connection_by_id($id_meta);
                if (metaconsole_connect($connection) != NOERR) {
                    continue;
                } else {
                    $network_interfaces_by_agents = agents_get_network_interfaces(false, $filter);
                    $return = agents_get_network_interfaces_array(
                        $network_interfaces_by_agents,
                        $return,
                        $type,
                        $content,
                        $report,
                        $fullscale,
                        $pdf
                    );
                    metaconsole_restore_db();
                }
            }
        }
    } else {
        $network_interfaces_by_agents = agents_get_network_interfaces(false, $filter);
        $return = agents_get_network_interfaces_array(
            $network_interfaces_by_agents,
            $return,
            $type,
            $content,
            $report,
            $fullscale,
            $pdf,
            $id_meta
        );
    }

    return reporting_check_structure_content($return);
}


function agents_get_network_interfaces_array(
    $network_interfaces_by_agents,
    $return,
    $type,
    $content,
    $report,
    $fullscale,
    $pdf,
    $id_meta
) {
    if (empty($network_interfaces_by_agents)) {
        $return['failed'] = __('The group has no agents or none of the agents has any network interface');
        $return['data'] = [];
    } else {
        foreach ($network_interfaces_by_agents as $agent_id => $agent) {
            $row_data = [];
            $row_data['agent'] = $agent['name'];
            $row_data['interfaces'] = [];
            foreach ($agent['interfaces'] as $interface_name => $interface) {
                $row_interface = [];
                $row_interface['name'] = $interface_name;
                $row_interface['ip'] = $interface['ip'];
                $row_interface['mac'] = $interface['mac'];
                $row_interface['status'] = $interface['status_image'];
                $row_interface['chart'] = null;

                $width = null;

                $params = [
                    'period'     => $content['period'],
                    'width'      => $width,
                    'unit_name'  => array_fill(0, count($interface['traffic']), __('bytes/s')),
                    'date'       => $report['datetime'],
                    'only_image' => $pdf,
                    'homeurl'    => $config['homeurl'],
                    'fullscale'  => $fullscale,
                    'server_id'  => $id_meta,
                    'height'     => $config['graph_image_height'],
                ];

                $params_combined = [
                    'labels'         => array_keys($interface['traffic']),
                    'modules_series' => array_values($interface['traffic']),
                ];

                switch ($type) {
                    case 'dinamic':
                    case 'static':
                        if (!empty($interface['traffic'])) {
                            $row_interface['chart'] = graphic_combined_module(
                                array_values($interface['traffic']),
                                $params,
                                $params_combined
                            );
                        }
                    break;

                    case 'data':
                        if (!empty($interface['traffic'])) {
                            $params['return_data'] = true;
                            $row_interface['chart'] = graphic_combined_module(
                                array_values($interface['traffic']),
                                $params,
                                $params_combined
                            );
                        }
                    break;
                }

                $row_data['interfaces'][] = $row_interface;
            }

            $return['data'][] = $row_data;
        }
    }

    return $return;
}


/**
 * reporting alert get fired
 */
function reporting_alert_get_fired($id_agent_module, $id_alert_template_module, $period, $datetime)
{
    $fired = [];
    $firedTimes = get_module_alert_fired(
        $id_agent_module,
        $id_alert_template_module,
        $period,
        $datetime
    );

    if (!is_numeric($datetime)) {
        $datetime = time_w_fixed_tz($datetime);
    }

    if (empty($datetime)) {
        $datetime = get_system_time();
    }

        $datelimit = ($datetime - $period);

    $empty = '----------------------------';
    if (empty($firedTimes)) {
        $firedTimes = [];
        $firedTimes[0]['timestamp'] = $empty;
    }

    foreach ($firedTimes as $fireTime) {
        if ($fireTime['utimestamp'] > $datelimit && $fireTime['utimestamp'] <= $datetime) {
            $fired[] = $fireTime['timestamp'];
        } else {
            $fired[] = $empty;
        }
    }

    return $fired;
}


/**
 * Reporting alert report group
 */
function reporting_alert_report_group($report, $content)
{
    global $config;

    $return['type'] = 'alert_report_group';

    if (empty($content['name'])) {
        $content['name'] = __('Alert Report Group');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $group_name = groups_get_name($content['id_group'], true);

    $return['title'] = $content['name'];
    $return['subtitle'] = $group_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    if ($content['id_group'] == 0) {
        $agent_modules = db_get_all_rows_sql(
            '
            SELECT distinct(id_agent_module)
            FROM talert_template_modules
            WHERE disabled = 0
                AND id_agent_module IN (
                    SELECT id_agente_modulo
                    FROM tagente_modulo)'
        );
    } else {
        $agent_modules = db_get_all_rows_sql(
            '
            SELECT distinct(id_agent_module)
            FROM talert_template_modules
            WHERE disabled = 0
                AND id_agent_module IN (
                    SELECT id_agente_modulo
                    FROM tagente_modulo
                    WHERE id_agente IN (
                        SELECT id_agente
                        FROM tagente WHERE id_grupo = '.$content['id_group'].'))'
        );
    }

    if (empty($alerts)) {
        $alerts = [];
    }

    $data = [];

    foreach ($agent_modules as $agent_module) {
        $data_row = [];

        $data_row['agent'] = io_safe_output(
            agents_get_alias(
                agents_get_agent_id_by_module_id($agent_module['id_agent_module'])
            )
        );
        $data_row['module'] = db_get_value_filter(
            'nombre',
            'tagente_modulo',
            ['id_agente_modulo' => $agent_module['id_agent_module']]
        );

        // Alerts over $id_agent_module
        $alerts = alerts_get_effective_alert_actions($agent_module['id_agent_module']);

        if ($alerts === false) {
            continue;
        }

        $ntemplates = 0;

        foreach ($alerts as $template => $actions) {
            $datetime = (int) $report['datetime'];
            if (!is_numeric($datetime)) {
                $datetime = time_w_fixed_tz($datetime);
            }

            if (empty($datetime)) {
                $datetime = get_system_time();
            }

            $period = (int) $content['period'];
            $datelimit = ($datetime - $period);

            $data_action = [];
            $data_action['actions'] = [];

            $naction = 0;
            if (isset($actions['custom'])) {
                foreach ($actions['custom'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            } else if (isset($actions['default'])) {
                foreach ($actions['default'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            } else if (isset($actions['unavailable'])) {
                foreach ($actions['unavailable'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            }

            $module_actions = [];

            $module_actions['template']       = $template;
            $module_actions['template_fired'] = reporting_alert_get_fired(
                $agent_module['id_agent_module'],
                $actions['id'],
                (int) $content['period'],
                (int) $report['datetime']
            );
            $module_actions['actions']        = $data_action;

            $data_row['alerts'][$ntemplates] = $module_actions;
            $ntemplates++;
        }

        if ($ntemplates > 0) {
            $data[] = $data_row;
        }
    }

    $return['data'] = $data;

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


/**
 * Report alert agent.
 *
 * @param array $report  Info report.
 * @param array $content Content report.
 *
 * @return array
 */
function reporting_alert_report_agent($report, $content)
{
    global $config;

    $return['type'] = 'alert_report_agent';

    if (empty($content['name'])) {
        $content['name'] = __('Alert Report Agent');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $id_agent = $content['id_agent'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);

    $items_label = [
        'type'              => $return['type'],
        'id_agent'          => $id_agent,
        'id_agent_module'   => $id_agent_module,
        'agent_description' => $agent_description,
        'agent_group'       => $agent_group,
        'agent_address'     => $agent_address,
        'agent_alias'       => $agent_alias,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    $module_list = agents_get_modules($content['id_agent']);

    $data = [];
    foreach ($module_list as $id => $module_name) {
        $data_row = [];
        $data_row['agent']  = $agent_alias;
        $data_row['module'] = $module_name;

        // Alerts over $id_agent_module.
        $alerts = alerts_get_effective_alert_actions($id);

        if ($alerts === false) {
            continue;
        }

        $ntemplates = 0;

        foreach ($alerts as $template => $actions) {
            $datetime = (int) $report['datetime'];
            if (!is_numeric($datetime)) {
                $datetime = time_w_fixed_tz($datetime);
            }

            if (empty($datetime)) {
                $datetime = get_system_time();
            }

            $period = (int) $content['period'];
            $datelimit = ($datetime - $period);

            $data_action = [];
            $data_action['actions'] = [];

            $naction = 0;
            if (isset($actions['custom'])) {
                foreach ($actions['custom'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            } else if (isset($actions['default'])) {
                foreach ($actions['default'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            } else if (isset($actions['unavailable'])) {
                foreach ($actions['unavailable'] as $action) {
                    $data_action[$naction]['name'] = $action['name'];
                    $fired = $action['fired'];
                    if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                        $data_action[$naction]['fired'] = '----------------------------';
                    } else {
                        $data_action[$naction]['fired'] = $fired;
                    }

                    $naction++;
                }
            }

            $module_actions = [];

            $module_actions['template']       = $template;
            $module_actions['template_fired'] = reporting_alert_get_fired(
                $id,
                $actions['id'],
                (int) $content['period'],
                (int) $report['datetime']
            );
            $module_actions['actions']        = $data_action;

            $data_row['alerts'][$ntemplates] = $module_actions;
            $ntemplates++;
        }

        if ($ntemplates > 0) {
            $data[] = $data_row;
        }
    }

    $return['data'] = $data;

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


/**
 * Alert report module.
 *
 * @param array $report  Info report.
 * @param array $content Content report.
 *
 * @return array
 */
function reporting_alert_report_module($report, $content)
{
    global $config;

    $return['type'] = 'alert_report_module';

    if (empty($content['name'])) {
        $content['name'] = __('Alert Report Module');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );

    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $return['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    $data_row = [];

    $data_row['agent'] = io_safe_output($agent_alias);
    $data_row['module'] = db_get_value_filter(
        'nombre',
        'tagente_modulo',
        ['id_agente_modulo' => $content['id_agent_module']]
    );

    // Alerts over $id_agent_module.
    $alerts = alerts_get_effective_alert_actions($content['id_agent_module']);

    $ntemplates = 0;

    foreach ($alerts as $template => $actions) {
        $datetime = (int) $report['datetime'];
        if (!is_numeric($datetime)) {
            $datetime = time_w_fixed_tz($datetime);
        }

        if (empty($datetime)) {
            $datetime = get_system_time();
        }

        $period = (int) $content['period'];
        $datelimit = ($datetime - $period);

        $data_action = [];
        $data_action['actions'] = [];

        $naction = 0;
        if (isset($actions['custom'])) {
            foreach ($actions['custom'] as $action) {
                $data_action[$naction]['name'] = $action['name'];
                $fired = $action['fired'];
                if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                    $data_action[$naction]['fired'] = '----------------------------';
                } else {
                    $data_action[$naction]['fired'] = $fired;
                }

                $naction++;
            }
        } else if (isset($actions['default'])) {
            foreach ($actions['default'] as $action) {
                $data_action[$naction]['name'] = $action['name'];
                $fired = $action['fired'];
                if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                    $data_action[$naction]['fired'] = '----------------------------';
                } else {
                    $data_action[$naction]['fired'] = $fired;
                }

                $naction++;
            }
        } else if (isset($actions['unavailable'])) {
            foreach ($actions['unavailable'] as $action) {
                $data_action[$naction]['name'] = $action['name'];
                $fired = $action['fired'];
                if ($fired == 0 || ($fired <= $datelimit || $fired > $datetime)) {
                    $data_action[$naction]['fired'] = '----------------------------';
                } else {
                    $data_action[$naction]['fired'] = $fired;
                }

                $naction++;
            }
        }

        $module_actions = [];

        $module_actions['template']       = $template;
        $module_actions['template_fired'] = reporting_alert_get_fired(
            $content['id_agent_module'],
            $actions['id'],
            (int) $content['period'],
            (int) $report['datetime']
        );
        $module_actions['actions']        = $data_action;

        $data_row['alerts'][$ntemplates] = $module_actions;
        $ntemplates++;
    }

    if ($ntemplates > 0) {
        $data[] = $data_row;
    }

    $return['data'] = $data;

    if ($config['metaconsole']) {
         metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_sql_graph(
    $report,
    $content,
    $type,
    $force_width_chart,
    $force_height_chart,
    $type_sql_graph
) {
    global $config;

    switch ($type_sql_graph) {
        case 'sql_graph_hbar':
            $return['type'] = 'sql_graph_hbar';
        break;

        case 'sql_graph_vbar':
            $return['type'] = 'sql_graph_vbar';
        break;

        case 'sql_graph_pie':
            $return['type'] = 'sql_graph_pie';
        break;
    }

    if (empty($content['name'])) {
        switch ($type_sql_graph) {
            case 'sql_graph_vbar':
                $content['name'] = __('SQL Graph Vertical Bars');
            break;

            case 'sql_graph_hbar':
                $content['name'] = __('SQL Graph Horizontal Bars');
            break;

            case 'sql_graph_pie':
                $content['name'] = __('SQL Graph Pie');
            break;
        }
    }

    // Get chart.
    reporting_set_conf_charts($width, $height, $only_image, $type, $content, $ttl);

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text();

    switch ($type) {
        case 'dinamic':
        case 'static':
        case 'data':
            $return['chart'] = graph_custom_sql_graph(
                $content['id_rc'],
                $width,
                $height,
                $content['type'],
                true,
                ui_get_full_url(false, false, false, false),
                $ttl,
                $content['top_n_value']
            );
        break;
    }

    return reporting_check_structure_content($return);
}


/**
 * Monitor report module.
 *
 * @param array $report  Info report.
 * @param array $content Content report.
 *
 * @return array
 */
function reporting_monitor_report($report, $content)
{
    global $config;

    $return['type'] = 'monitor_report';

    if (empty($content['name'])) {
        $content['name'] = __('Monitor Report');
    }

    if (is_metaconsole()) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );

    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $return['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($return['label'] != '') {
        $return['label'] = reporting_label_macro(
            $items_label,
            $return['label']
        );
    }

    $return['agent_name'] = $agent_alias;
    $return['module_name'] = $module_name;

    // All values (except id module and report time) by default.
    $report = reporting_advanced_sla(
        $content['id_agent_module'],
        ($report['datetime'] - $content['period']),
        $report['datetime']
    );

    if ($report['time_total'] === $report['time_unknown']
        || empty($content['id_agent_module'])
    ) {
        $return['data']['unknown'] = 1;
    } else {
        $return['data']['ok']['value'] = $report['SLA'];
        $return['data']['ok']['formated_value'] = $report['SLA_fixed'];

        $return['data']['fail']['value'] = (100 - $return['data']['ok']['value']);
        $return['data']['fail']['formated_value'] = (100 - $return['data']['ok']['formated_value']);
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


/**
 * Generates the data structure to build a netflow report.
 *
 * @param array   $report             Global report info.
 * @param array   $content            Report item info.
 * @param string  $type               Report type (static, dynamic, data).
 * @param integer $force_width_chart  Fixed width chart.
 * @param integer $force_height_chart Fixed height chart.
 * @param string  $type_netflow       One of netflow_area, netflow_data,
 *      netflow_summary.
 * @param boolean $pdf                True if a pdf report is generating.
 *
 * @return array Report item structure.
 */
function reporting_netflow(
    $report,
    $content,
    $type,
    $force_width_chart,
    $force_height_chart,
    $type_netflow=null,
    $pdf=false
) {
    global $config;

    switch ($type_netflow) {
        case 'netflow_area':
            $return['type'] = 'netflow_area';
        break;

        case 'netflow_data':
            $return['type'] = 'netflow_data';
        break;

        case 'netflow_summary':
            $return['type'] = 'netflow_summary';
        break;

        default:
            $return['type'] = 'unknown';
        break;
    }

    if (empty($content['name'])) {
        switch ($type_netflow) {
            case 'netflow_area':
                $content['name'] = __('Netflow Area');
            break;

            case 'netflow_summary':
                $content['name'] = __('Netflow Summary');
            break;

            case 'netflow_data':
                $content['name'] = __('Netflow Data');
            break;

            default:
                $content['name'] = __('Unknown report');
            break;
        }
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);

    // Get chart.
    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    // Get item filters.
    $filter = db_get_row_sql(
        "SELECT *
        FROM tnetflow_filter
        WHERE id_sg = '".(int) $content['text']."'",
        false,
        true
    );

    switch ($type) {
        case 'dinamic':
        case 'static':
        case 'data':
            $return['chart'] = netflow_draw_item(
                ($report['datetime'] - $content['period']),
                $report['datetime'],
                $content['top_n'],
                $type_netflow,
                $filter,
                $content['top_n_value'],
                $content['server_name'],
                $pdf ? 'PDF' : 'HTML'
            );
        break;

        case 'data':
        default:
            // Nothing to do.
        break;
    }

    $return['subtitle'] = netflow_generate_subtitle_report(
        $filter['aggregate'],
        $content['top_n'],
        $type_netflow
    );

    return reporting_check_structure_content($return);
}


function reporting_prediction_date($report, $content)
{
    global $config;

    $return['type'] = 'prediction_date';

    if (empty($content['name'])) {
        $content['name'] = __('Prediction Date');
    }

    $module_name = io_safe_output(
        modules_get_agentmodule_name($content['id_agent_module'])
    );
    $agent_name = io_safe_output(
        modules_get_agentmodule_agent_alias($content['id_agent_module'])
    );

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_name.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';

    $return['agent_name'] = $agent_name;
    $return['module_name'] = $module_name;

    $intervals_text = explode(';', $content['text']);

    $max_interval = $intervals_text[0];
    $min_interval = $intervals_text[1];

    $value = forecast_prediction_date($content['id_agent_module'], $content['period'], $max_interval, $min_interval);

    if ($value === false) {
        $return['data']['value'] = __('Unknown');
    } else {
        $return['data']['value'] = date('d M Y H:i:s', $value);
    }

    return reporting_check_structure_content($return);
}


function reporting_projection_graph(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null,
    $pdf=false
) {
    global $config;

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);
        $server  = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $return['type'] = 'projection_graph';

    if (empty($content['name'])) {
        $content['name'] = __('Projection Graph');
    }

    $module_name = io_safe_output(modules_get_agentmodule_name($content['id_agent_module']));
    $agent_name = io_safe_output(modules_get_agentmodule_agent_alias($content['id_agent_module']));

    $return['title']       = $content['name'];
    $return['subtitle']    = $agent_name.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date']        = reporting_get_date_text($report, $content);
    $return['label']       = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    $return['agent_name']  = $agent_name;
    $return['module_name'] = $module_name;

    set_time_limit(500);

    switch ($type) {
        case 'dinamic':
        case 'static':
            $params = [
                'period'     => $content['period'],
                'width'      => $width,
                'date'       => $report['datetime'],
                'unit'       => '',
                'only_image' => $pdf,
                'homeurl'    => ui_get_full_url(false, false, false, false).'/',
                'ttl'        => $ttl,
                'server_id'  => $id_meta,
                'height'     => $config['graph_image_height'],
            ];

            $params_combined = [
                'projection' => $content['top_n_value'],
            ];

            $return['chart'] = graphic_combined_module(
                [$content['id_agent_module']],
                $params,
                $params_combined
            );

        break;

        case 'data':
            $return['data'] = forecast_projection_graph(
                $content['id_agent_module'],
                $content['period'],
                $content['top_n_value'],
                false,
                false,
                true
            );
        break;
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_agent_configuration($report, $content)
{
    global $config;

    $return['type'] = 'agent_configuration';

    if (empty($content['name'])) {
        $content['name'] = __('Agent configuration');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $sql = '
        SELECT *
        FROM tagente
        WHERE id_agente='.$content['id_agent'];
    $agent_data = db_get_row_sql($sql);

    $agent_configuration = [];
    $agent_configuration['name'] = $agent_data['alias'];
    $agent_configuration['group'] = groups_get_name($agent_data['id_grupo']);
    $agent_configuration['group_icon'] = ui_print_group_icon($agent_data['id_grupo'], true, '', '', false);
    $agent_configuration['os'] = os_get_name($agent_data['id_os']);
    $agent_configuration['os_icon'] = ui_print_os_icon($agent_data['id_os'], true, true);
    $agent_configuration['address'] = $agent_data['direccion'];
    $agent_configuration['description'] = $agent_data['comentarios'];
    $agent_configuration['enabled'] = (int) !$agent_data['disabled'];
    $agent_configuration['group'] = $report['group'];
    $modules = agents_get_modules($content['id_agent']);

    $agent_configuration['modules'] = [];
    // Agent's modules
    if (!empty($modules)) {
        foreach ($modules as $id_agent_module => $module) {
            $sql = "
                SELECT *
                FROM tagente_modulo
                WHERE id_agente_modulo = $id_agent_module";
            $module_db = db_get_row_sql($sql);

            $data_module = [];
            $data_module['name'] = $module_db['nombre'];
            if ($module_db['disabled']) {
                $data_module['name'] .= ' ('.__('Disabled').')';
            }

            $data_module['type_icon'] = ui_print_moduletype_icon($module_db['id_tipo_modulo'], true);
            $data_module['type'] = modules_get_type_name($module_db['id_tipo_modulo']);
            $data_module['max_warning'] = $module_db['max_warning'];
            $data_module['min_warning'] = $module_db['min_warning'];
            $data_module['max_critical'] = $module_db['max_critical'];
            $data_module['min_critical'] = $module_db['min_critical'];
            $data_module['threshold'] = $module_db['module_ff_interval'];
            $data_module['description'] = $module_db['descripcion'];
            if (($module_db['module_interval'] == 0)
                || ($module_db['module_interval'] == '')
            ) {
                $data_module['interval'] = db_get_value(
                    'intervalo',
                    'tagente',
                    'id_agente',
                    $content['id_agent']
                );
            } else {
                $data_module['interval'] = $module_db['module_interval'];
            }

            $data_module['unit'] = $module_db['unit'];
            $module_status = db_get_row(
                'tagente_estado',
                'id_agente_modulo',
                $id_agent_module
            );
            modules_get_status(
                $id_agent_module,
                $module_status['estado'],
                $module_status['datos'],
                $status,
                $title
            );
            $data_module['status_icon'] = ui_print_status_image($status, $title, true);
            $data_module['status'] = $title;
            $sql_tag = "
                SELECT name
                FROM ttag
                WHERE id_tag IN (
                    SELECT id_tag
                    FROM ttag_module
                    WHERE id_agente_modulo = $id_agent_module)";
            $tags = db_get_all_rows_sql($sql_tag);
            if ($tags === false) {
                $data_module['tags'] = [];
            } else {
                foreach ($tags as $tag) {
                    $data_module['tags'][] = $tag['name'];
                }
            }

            $agent_configuration['modules'][] = $data_module;
        }
    }

    $return['data'] = $agent_configuration;

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_value($report, $content, $type, $pdf=false)
{
    global $config;

    $return = [];
    switch ($type) {
        case 'max':
            $return['type'] = 'max_value';
        break;

        case 'min':
            $return['type'] = 'min_value';
        break;

        case 'avg':
            $return['type'] = 'avg_value';
        break;

        case 'sum':
            $return['type'] = 'sumatory';
        break;

        /*
            case 'MTTR':
            $return['type'] = 'MTTR';
            break;

            case 'MTBF':
            $return['type'] = 'MTBF';
            break;

            case 'TTO':
            $return['type'] = 'TTO';
            break;

            case 'TTRT':
            $return['type'] = 'TTRT';
        break;*/
    }

    if (empty($content['name'])) {
        switch ($type) {
            case 'max':
                $content['name'] = __('Max. Value');
            break;

            case 'min':
                $content['name'] = __('Min. Value');
            break;

            case 'avg':
                $content['name'] = __('AVG. Value');
            break;

            case 'sum':
                $content['name'] = __('Summatory');
            break;

            /*
                case 'MTTR':
                $content['name'] = __('MTTR');
                break;

                case 'MTBF':
                $content['name'] = __('MTBF');
                break;

                case 'TTO':
                $content['name'] = __('TTO');
                break;

                case 'TTRT':
                $content['name'] = __('TTRT');
            break;*/
        }
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $module_name = io_safe_output(
        modules_get_agentmodule_name($content['id_agent_module'])
    );
    $agent_name = io_safe_output(
        modules_get_agentmodule_agent_alias($content['id_agent_module'])
    );
    $unit = db_get_value(
        'unit',
        'tagente_modulo',
        'id_agente_modulo',
        $content['id_agent_module']
    );

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_name.' - '.$module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text($report, $content);
    $return['label'] = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    $return['agents'] = [$content['id_agent']];
    $return['id_agent'] = $content['id_agent'];
    $return['id_agent_module'] = $content['id_agent_module'];

    $return['agent_name'] = $agent_name;
    $return['module_name'] = $module_name;

    if ($pdf) {
        $only_image = 1;
    }

    $params = [
        'agent_module_id' => $content['id_agent_module'],
        'period'          => $content['period'],
        'width'           => '600px',
        'pure'            => false,
        'date'            => $report['datetime'],
        'only_image'      => $only_image,
        'homeurl'         => ui_get_full_url(false, false, false, false),
        'ttl'             => 1,
        'type_graph'      => $config['type_module_charts'],
        'time_interval'   => $content['lapse'],
        'server_id'       => $id_meta,
        'height'          => $config['graph_image_height'],
        'fullscale'       => true,
    ];

    switch ($type) {
        case 'max':
        case 'min':
        case 'avg':
            if ($content['lapse_calc'] == 0) {
                switch ($type) {
                    case 'max':
                        $value = reporting_get_agentmodule_data_max(
                            $content['id_agent_module'],
                            $content['period'],
                            $report['datetime']
                        );
                    break;

                    case 'min':
                        $value = reporting_get_agentmodule_data_min(
                            $content['id_agent_module'],
                            $content['period'],
                            $report['datetime']
                        );
                    break;

                    case 'avg':
                        $value = reporting_get_agentmodule_data_average(
                            $content['id_agent_module'],
                            $content['period'],
                            $report['datetime']
                        );
                    break;
                }

                if (!$config['simple_module_value']) {
                    $formated_value = $value;
                } else {
                    $formated_value = format_for_graph($value, $config['graph_precision']).' '.$unit;
                }
            } else {
                $return['visual_format'] = $content['visual_format'];

                switch ($type) {
                    case 'max':
                        $params['force_interval'] = 'max_only';
                        $value = format_for_graph(reporting_get_agentmodule_data_max($content['id_agent_module'], $content['period'], $report['datetime']), $config['graph_precision']).' '.$unit;
                    break;

                    case 'min':
                        $params['force_interval'] = 'min_only';
                        $value = format_for_graph(reporting_get_agentmodule_data_min($content['id_agent_module'], $content['period'], $report['datetime']), $config['graph_precision']).' '.$unit;
                    break;

                    case 'avg':
                        $params['force_interval'] = 'avg_only';
                        $value = format_for_graph(reporting_get_agentmodule_data_average($content['id_agent_module'], $content['period'], $report['datetime']), $config['graph_precision']).' '.$unit;
                    break;
                }

                $return['data'][] = [
                    __('Agent')   => $agent_name,
                    __('Module')  => $module_name,
                    __('Maximun') => $value,
                ];

                if ($content['visual_format'] != 1) {
                    $graph = grafico_modulo_sparse($params);
                    $return['data'][] = ['value' => $graph];
                }

                if ($content['visual_format'] != 2) {
                    $time_begin = db_get_row_sql('select utimestamp from tagente_datos where id_agente_modulo ='.$content['id_agent_module'], true);

                    for ($i = ($report['datetime'] - $content['period']); $i < $report['datetime']; $i += $content['lapse']) {
                        $row = [];
                        $row[__('Lapse')] = date('Y-m-d H:i:s', ($i + 1)).' to '.date('Y-m-d H:i:s', (($i + $content['lapse']) ));

                        if ($i > $time_begin['utimestamp']) {
                            switch ($type) {
                                case 'max':
                                    $row[__('Maximun')] = format_for_graph(reporting_get_agentmodule_data_max($content['id_agent_module'], $content['lapse'], ($i + $content['lapse'])), $config['graph_precision']).' '.$unit;
                                break;

                                case 'min':
                                    $row[__('Maximun')] = format_for_graph(reporting_get_agentmodule_data_min($content['id_agent_module'], $content['lapse'], ($i + $content['lapse'])), $config['graph_precision']).' '.$unit;
                                break;

                                case 'avg':
                                    $row[__('Maximun')] = format_for_graph(reporting_get_agentmodule_data_average($content['id_agent_module'], $content['lapse'], ($i + $content['lapse'])), $config['graph_precision']).' '.$unit;
                                break;
                            }
                        } else {
                            $row[__('Maximun')] = 'N/A';
                        }

                        $return['data'][] = $row;
                    }
                }

                if ($config['metaconsole']) {
                    metaconsole_restore_db();
                }

                return reporting_check_structure_content($return);
            }
        break;

        case 'sum':
            $value = reporting_get_agentmodule_data_sum(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime'],
                $content['uncompressed_module']
            );
            if (!$config['simple_module_value']) {
                $formated_value = $value;
            } else {
                $formated_value = format_for_graph($value, $config['graph_precision']).' '.$unit;
            }
        break;

        /*
            case 'MTTR':
            $value = reporting_get_agentmodule_mttr(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime']
            );
            $formated_value = null;
            break;

            case 'MTBF':
            $value = reporting_get_agentmodule_mtbf(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime']
            );
            $formated_value = null;
            break;

            case 'TTO':
            $value = reporting_get_agentmodule_tto(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime']
            );
            if ($value == 0) {
                $formated_value = null;
            } else {
                $formated_value = human_time_description_raw($value);
            }
            break;

            case 'TTRT':
            $value = reporting_get_agentmodule_ttr(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime']
            );
            if ($value == 0) {
                $formated_value = null;
            } else {
                $formated_value = human_time_description_raw($value);
            }
        break;*/
    }

    $return['data'] = [
        'value'          => $value,
        'formated_value' => $formated_value,
    ];

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_url($report, $content, $type='dinamic')
{
    global $config;

    $return = [];
    $return['type'] = 'url';

    if (empty($content['name'])) {
        $content['name'] = __('Url');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text();

    $return['url'] = $content['external_source'];

    switch ($type) {
        case 'dinamic':
            $return['data'] = null;
        break;

        case 'data':
        case 'static':
            $curlObj = curl_init();
            curl_setopt($curlObj, CURLOPT_URL, $content['external_source']);
            curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curlObj);
            curl_close($curlObj);
            $return['data'] = $output;
        break;
    }

    return reporting_check_structure_content($return);
}


function reporting_text($report, $content)
{
    global $config;

    $return = [];
    $return['type'] = 'text';

    if (empty($content['name'])) {
        $content['name'] = __('Text');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text();

    $return['data'] = html_entity_decode($content['text']);

    return reporting_check_structure_content($return);
}


function reporting_sql($report, $content)
{
    global $config;

    $return = [];
    $return['type'] = 'sql';

    if (empty($content['name'])) {
        $content['name'] = __('SQL');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text();

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server(
            $content['server_name']
        );

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    if ($content['treport_custom_sql_id'] != 0) {
        switch ($config['dbtype']) {
            case 'mysql':
                $sql = io_safe_output(
                    db_get_value_filter(
                        '`sql`',
                        'treport_custom_sql',
                        ['id' => $content['treport_custom_sql_id']]
                    )
                );
            break;

            case 'postgresql':
                $sql = io_safe_output(
                    db_get_value_filter(
                        '"sql"',
                        'treport_custom_sql',
                        ['id' => $content['treport_custom_sql_id']]
                    )
                );
            break;

            case 'oracle':
                $sql = io_safe_output(
                    db_get_value_filter(
                        'sql',
                        'treport_custom_sql',
                        ['id' => $content['treport_custom_sql_id']]
                    )
                );
            break;
        }
    } else {
        $sql = io_safe_output($content['external_source']);
    }

    // Check if exist sql macro
    $sql = reporting_sql_macro($report, $sql);

    // Do a security check on SQL coming from the user.
    $sql = check_sql($sql);

    $return['sql'] = $sql;
    $return['correct'] = 1;
    $return['error'] = '';
    $return['data'] = [];
    if ($sql != '') {
        $header = [];
        if ($content['header_definition'] != '') {
            $header = explode('|', $content['header_definition']);
            $return['header'] = $header;
        }

        if ($content['id_rc'] != null) {
            $historical_db = db_get_value_sql(
                'SELECT historical_db from treport_content where id_rc ='.$content['id_rc']
            );
        } else {
            $historical_db = $content['historical_db'];
        }

        $result = db_get_all_rows_sql($sql, $historical_db);
        if ($result !== false) {
            foreach ($result as $row) {
                $data_row = [];

                $i = 0;
                foreach ($row as $dbkey => $field) {
                    if (isset($header[$i])) {
                        $key = $header[$i];
                    } else {
                        $key = $dbkey;
                    }

                    $data_row[$key] = $field;

                    $i++;
                }

                $return['data'][] = $data_row;
            }
        }
    } else {
        $return['correct'] = 0;
        $return['error'] = __('Illegal query: Due security restrictions, there are some tokens or words you cannot use: *, delete, drop, alter, modify, password, pass, insert or update.');
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


//
// Truncates a value
//
// Returns the truncated value
//
function sla_truncate($num, $accurancy=2)
{
    if (!isset($accurancy)) {
        $accurancy = 2;
    }

    $mult = pow(10, $accurancy);
    return (floor($num * $mult) / $mult);
}


/**
 * SLA check value.
 *
 * @param integer $value            Value.
 * @param integer $min              Treshold min SLA.
 * @param boolean $max              Treshold max SLA.
 * @param boolean $inverse_interval Treshold inverse SLA.
 *
 * @return boolean Returns the interval in downtime (false if no matches).
 */
function sla_check_value($value, $min, $max, $inverse_interval=0)
{
    if (!isset($inverse_interval)) {
        $inverse_interval = 0;
    }

    if ((!isset($max)) && (!isset($min))) {
        // Disabled thresholds.
        return true;
    }

    if ($max == $min) {
        // Equal.
        if ($value == $max) {
            return ($inverse_interval == 0) ? true : false;
        }

        return ($inverse_interval == 0) ? false : true;
    }

    if (!isset($max)) {
        // Greater or equal than min.
        if ($value >= $min) {
            return ($inverse_interval == 0) ? true : false;
        }

        return ($inverse_interval == 0) ? false : true;
    }

    if (!isset($min)) {
        // Smaller or equal than max.
        if ($value <= $max) {
            return ($inverse_interval == 0) ? true : false;
        }

        return ($inverse_interval == 0) ? false : true;
    }

    if (($value >= $min) && ($value <= $max)) {
        return ($inverse_interval == 0) ? true : false;
    }

    return ($inverse_interval == 0) ? false : true;
}


/**
 * SLA downtime worktime.
 *
 * Check (if needed) if the range specified by wt_start and wt_end is downtime.
 *
 * Only used for inclusive downtimes calculation (from sla_fixed_worktime).
 *
 * @param integer $wt_start            Start of the range.
 * @param integer $wt_end              End of the range.
 * @param boolean $inclusive_downtimes Boolean.
 * @param array   $planned_downtimes   Array with the planned downtimes (ordered and merged).
 *
 * @return integer                     Returns the interval in downtime (false if no matches).
 */
function sla_downtime_worktime(
    $wt_start,
    $wt_end,
    $inclusive_downtimes=1,
    $planned_downtimes=null
) {
    if ((!isset($planned_downtimes)) || (!is_array($planned_downtimes))) {
        return false;
    }

    if ((!isset($wt_start)) || (!isset($wt_end)) || ($wt_start > $wt_end)) {
        return false;
    }

    if ($inclusive_downtimes != 1) {
        return false;
    }

    $rt = false;

    foreach ($planned_downtimes as $pd) {
        if (($wt_start >= $pd['date_from'])
            && ($wt_start <= $pd['date_to'])
            && ($wt_end >= $pd['date_from'])
            && ($wt_end <= $pd['date_to'])
        ) {
            // ..[..start..end..]..
            $rt = ($wt_end - $wt_start);
            break;
        } else if (($wt_start < $pd['date_from'])
            && ($wt_end > $pd['date_from'])
            && ($wt_end < $pd['date_to'])
        ) {
            // ..start..[..end..]..
            $rt = ($wt_end - $pd['date_from']);
            break;
        } else if (($wt_start >= $pd['date_from'])
            && ($wt_start < $pd['date_to'])
            && ($wt_end > $pd['date_to'])
        ) {
            // ..[..start..]..end..
            $rt = ($pd['date_to'] - $wt_start);
            break;
        } else if (($wt_start >= $pd['date_to'])
            && ($wt_end >= $pd['date_to'])
        ) {
            // ..[..]..start..end..
        } else {
            // ..start..end..[..]..
        }
    }

    return $rt;
}


/**
 * SLA fixed worktime
 *
 * Check (if needed) if the range specified by wt_start and wt_end is a valid
 * range or not.
 *
 * As worktime is order (older ... newer) the idx works as flag to identify
 * last range checked, in order to improve the algorythm performance.
 *
 * @param integer $wt_start            Start of the range.
 * @param integer $wt_end              End of the range.
 * @param array   $worktime            Hash containing the valid intervals.
 * @param array   $planned_downtimes   Array with the planned downtimes (ordered and merged).
 * @param integer $inclusive_downtimes In downtime as OK (1) or ignored (0).
 * @param integer $idx                 Last ranges checked.
 *
 * @return array
 */
function sla_fixed_worktime(
    $wt_start,
    $wt_end,
    $worktime=null,
    $planned_downtimes=null,
    $inclusive_downtimes=1,
    $idx=0
) {
    $return = [];

    // Accept all ranges by default.
    $return['wt_valid'] = 1;
    $return['interval'] = ($wt_end - $wt_start);

    if ((!isset($wt_start)) || (!isset($wt_end))
        || ($wt_start > $wt_end) || ($wt_start > time())
    ) {
        $return['wt_valid'] = 0;
        $return['interval'] = 0;
    }

    // No exclusions defined, entire worktime is valid.
    if ((!isset($worktime) || (!is_array($worktime)))) {
        $time_in_downtime = sla_downtime_worktime(
            $wt_start,
            $wt_end,
            $inclusive_downtimes,
            $planned_downtimes
        );
        if ($time_in_downtime != false) {
            $return['wt_in_downtime']    = 1;
            $return['downtime_interval'] = $time_in_downtime;
            $return['interval']         -= $time_in_downtime;
        }

        return $return;
    }

    // Check exceptions.
    $total = count($worktime);

    $return['idx'] = $idx;

    if (!(($idx <= $total) && ($idx >= 0))) {
        $idx = 0;
    }

    $start_fixed = 0;
    for ($i = $idx; $i < $total; $i++) {
        $wt = $worktime[$i];

        if ($start_fixed == 1) {
            // Intervals greater than 1 DAY.
            if ($wt_end < $wt['date_from']) {
                // Case G: ..end..[..]..
                $time_in_downtime = sla_downtime_worktime(
                    $wt_start,
                    $wt_end,
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                // Ignore older worktimes
                $return['idx'] = $i;
                return $return;
            }

            if (($wt_end >= $wt['date_from'])
                && ($wt_end <= $wt['date_to'])
            ) {
                // Case H: ..[..end..]..
                // add last slice.
                $return['interval'] += ($wt_end - $wt['date_from']);
                $time_in_downtime = sla_downtime_worktime(
                    $wt['date_from'],
                    $wt_end,
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                return $return;
            }

            if (($wt_end > $wt['date_from'])
                && ($wt_end > $wt['date_to'])
            ) {
                // Case H: ..[..]..end..
                // Add current slice and continue checking.
                $return['interval'] += ($wt['date_to'] - $wt['date_from']);
                $time_in_downtime = sla_downtime_worktime(
                    $wt['date_from'],
                    $wt['date_to'],
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }
            }
        } else {
            if (($wt_start < $wt['date_from'])
                && ($wt_end < $wt['date_from'])
            ) {
                // Case A: ..start..end..[...]......
                $return['wt_valid'] = 0;
                $return['idx'] = $i;
                return $return;
            }

            if (($wt_start <= $wt['date_from'])
                && ($wt_end >= $wt['date_from'])
                && ($wt_end < $wt['date_to'])
            ) {
                // Case B: ...start..[..end..]......
                $return['wt_valid'] = 1;
                $return['interval'] = ($wt_end - $wt['date_from']);
                $time_in_downtime = sla_downtime_worktime(
                    $wt['date_from'],
                    $wt_end,
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                return $return;
            }

            if (($wt_start >= $wt['date_from'])
                && ($wt_start <= $wt['date_to'])
                && ($wt_end >= $wt['date_from'])
                && ($wt_end <= $wt['date_to'])
            ) {
                // Case C: ...[..start..end..]......
                $return['wt_valid'] = 1;
                $time_in_downtime = sla_downtime_worktime(
                    $wt_start,
                    $wt_end,
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                return $return;
            }

            if (($wt_start >= $wt['date_from'])
                && ($wt_start < $wt['date_to'])
                && ($wt_end > $wt['date_to'])
            ) {
                // Case D: ...[..start..]...end.....
                $return['interval'] = ($wt['date_to'] - $wt_start);
                $time_in_downtime = sla_downtime_worktime(
                    $wt_start,
                    $wt['date_to'],
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                $return['wt_valid'] = 1;
                $start_fixed = 1;
                // We must check if 'end' is greater than the next valid
                // worktime range start time unless is the last one.
                if (($i + 1) == $total) {
                    // If there's no more worktime ranges
                    // to check return the accumulated.
                    return $return;
                }
            }

            if (($wt_start < $wt['date_from'])
                && ($wt_end > $wt['date_to'])
            ) {
                // Case E: ...start...[...]...end...
                $return['wt_valid'] = 1;
                $return['interval'] = ($wt['date_to'] - $wt['date_from']);
                $time_in_downtime = sla_downtime_worktime(
                    $wt['date_from'],
                    $wt['date_to'],
                    $inclusive_downtimes,
                    $planned_downtimes
                );
                if ($time_in_downtime != false) {
                    $return['wt_in_downtime']    = 1;
                    $return['downtime_interval'] = $time_in_downtime;
                    $return['interval']         -= $time_in_downtime;
                }

                if (($wt_end - $wt_start) < SECONDS_1DAY) {
                    // Interval is less than 1 day.
                    return $return;
                } else {
                    // Interval greater than 1 day, split valid worktimes.
                    $start_fixed = 1;
                }
            }

            if (($wt_start > $wt['date_to'])
                && ($wt_end > $wt['date_to'])
            ) {
                // Case F: ...[....]..start...end...
                // Invalid, check next worktime hole.
                $return['wt_valid'] = 0;
                // And remove current one.
                $return['idx'] = ($i + 1);
            }
        }
    }

    $return['wt_valid'] = 0;

    return $return;
}


/**
 * Advanced SLA result with summary
 *
 * @param integer $id_agent_module     Id_agent_module.
 * @param integer $time_from           Time start.
 * @param integer $time_to             Time end.
 * @param integer $min_value           Minimum value for OK status.
 * @param integer $max_value           Maximum value for OK status.
 * @param integer $inverse_interval    Inverse interval (range) for OK status.
 * @param array   $daysWeek            Days of active work times (M-T-W-T-V-S-S).
 * @param integer $timeFrom            Start of work time, in each day.
 * @param integer $timeTo              End of work time, in each day.
 * @param integer $slices              Number of reports (time division).
 * @param integer $inclusive_downtimes In downtime as OK (1) or ignored (0).
 *
 * @return array                      Returns a hash with the calculated data.
 */
function reporting_advanced_sla(
    $id_agent_module,
    $time_from=null,
    $time_to=null,
    $min_value=null,
    $max_value=null,
    $inverse_interval=0,
    $daysWeek=null,
    $timeFrom=null,
    $timeTo=null,
    $slices=1,
    $inclusive_downtimes=1
) {
    // In content:
    // Example: [time_from, time_to] => Worktime
    // week's days => flags to manage workdays.
    if (!isset($id_agent_module)) {
        return false;
    }

    if ($slices < 1) {
        $slices = 1;
    }

    if ((!isset($min_value)) && (!isset($max_value))) {
        // Infer availability range based on the critical thresholds.
        $agentmodule_info = modules_get_agentmodule($id_agent_module);

        // Take in mind: the "inverse" critical threshold.
        $min_value        = $agentmodule_info['min_critical'];
        $max_value        = $agentmodule_info['max_critical'];
        $inverse_interval = ($agentmodule_info['critical_inverse'] == 0) ? 1 : 0;

        if ((!isset($min_value)) || ($min_value == 0)) {
            $min_value = null;
        }

        if ((!isset($max_value)) || ($max_value == 0)) {
            $max_value = null;
        }

        if ((!(isset($max_value))) && (!(isset($min_value)))) {
            $max_value = null;
            $min_value = null;
        }

        if ((!isset($min_value)) && (!isset($max_value))) {
            if (($agentmodule_info['id_tipo_modulo'] == '2')
                // Generic_proc.
                || ($agentmodule_info['id_tipo_modulo'] == '6')
                // Remote_icmp_proc.
                || ($agentmodule_info['id_tipo_modulo'] == '9')
                // Remote_tcp_proc.
                || ($agentmodule_info['id_tipo_modulo'] == '18')
                // Remote_snmp_proc.
                || ($agentmodule_info['id_tipo_modulo'] == '21')
                // Async_proc.
                || ($agentmodule_info['id_tipo_modulo'] == '31')
            ) {
                // Web_proc
                // boolean values are OK if they're different from 0.
                $max_value = 0;
                $min_value = 0;
                $inverse_interval = 1;
            } else if ($agentmodule_info['id_tipo_modulo'] == '100') {
                $max_value = 0.9;
                $min_value = 0;
            }
        }
    }

    // By default show last day.
    $datetime_to = time();
    $datetime_from = ($datetime_to - SECONDS_1DAY);

    // Or apply specified range.
    if ((isset($time_to) && isset($time_from)) && ($time_to > $time_from)) {
        $datetime_to   = $time_to;
        $datetime_from = $time_from;
    }

    if (!isset($time_to)) {
        $datetime_to = $time_to;
    }

    if (!isset($time_from)) {
        $datetime_from = $time_from;
    }

    $uncompressed_data = db_uncompress_module_data(
        $id_agent_module,
        $datetime_from,
        $datetime_to
    );

    if (is_array($uncompressed_data)) {
        $n_pools = count($uncompressed_data);
        if ($n_pools == 0) {
            return false;
        }
    }

    $planned_downtimes = reporting_get_planned_downtimes_intervals(
        $id_agent_module,
        $datetime_from,
        $datetime_to
    );

    if ((is_array($planned_downtimes)) && (count($planned_downtimes) > 0)) {
        // Sort retrieved planned downtimes.
        usort(
            $planned_downtimes,
            function ($a, $b) {
                $a = intval($a['date_from']);
                $b = intval($b['date_from']);
                if ($a == $b) {
                    return 0;
                }

                return ($a < $b) ? (-1) : 1;
            }
        );

        // Compress (overlapped) planned downtimes.
        $npd = count($planned_downtimes);
        for ($i = 0; $i < $npd; $i++) {
            if (isset($planned_downtimes[($i + 1)])) {
                if ($planned_downtimes[$i]['date_to'] >= $planned_downtimes[($i + 1)]['date_from']) {
                    // Merge.
                    $planned_downtimes[$i]['date_to'] = $planned_downtimes[($i + 1)]['date_to'];
                    array_splice($planned_downtimes, ($i + 1), 1);
                    $npd--;
                }
            }
        }
    } else {
        $planned_downtimes = null;
    }

    // Structure retrieved: schema:
    //
    // uncompressed_data =>
    // pool_id (int)
    // utimestamp (start of current slice)
    // data
    // array
    // utimestamp
    // datos.
    // Build exceptions.
    $worktime = null;

    if (((isset($daysWeek))
        && (isset($timeFrom))
        && (isset($timeTo)))
        || (is_array($planned_downtimes))
    ) {
        $n = 0;

        if (!isset($daysWeek)) {
            // Init.
            $daysWeek = [
                '1' => 1,
            // Sunday.
                '2' => 1,
            // Monday.
                '3' => 1,
            // Tuesday.
                '4' => 1,
            // Wednesday.
                '5' => 1,
            // Thursday.
                '6' => 1,
            // Friday.
                '7' => 1,
            // Saturday.
            ];
        }

        foreach ($daysWeek as $day) {
            if ($day == 1) {
                $n++;
            }
        }

        if (($n == count($daysWeek)) && ($timeFrom == $timeTo)) {
            // Ignore custom ranges.
            $worktime = null;
        } else {
            // Get only first day.
            $date_start = strtotime(date('Y/m/d', $datetime_from));
            $date_end   = strtotime(date('Y/m/d', $datetime_to));

            $t_day    = $date_start;
            $i        = 0;
            $worktime = [];

            if ($timeFrom == $timeTo) {
                $timeFrom = '00:00:00';
                $timeTo   = '00:00:00';
            }

            if (!isset($timeFrom)) {
                $timeFrom = '00:00:00';
            }

            if (!isset($timeTo)) {
                $timeTo = '00:00:00';
            }

            // TimeFrom (seconds).
            sscanf($timeFrom, '%d:%d:%d', $hours, $minutes, $seconds);
            $secondsFrom = ($hours * 3600 + $minutes * 60 + $seconds);

            // TimeTo (seconds).
            sscanf($timeTo, '%d:%d:%d', $hours, $minutes, $seconds);
            $secondsTo = ($hours * 3600 + $minutes * 60 + $seconds);

            // Apply planned downtime exceptions (fix matrix).
            while ($t_day <= $date_end) {
                if ($daysWeek[(date('w', $t_day) + 1)] == 1) {
                    $wt_start = strtotime(
                        date('Y/m/d H:i:s', ($t_day + $secondsFrom))
                    );
                    $wt_end   = strtotime(
                        date('Y/m/d H:i:s', ($t_day + $secondsTo))
                    );
                    if ($timeFrom == $timeTo) {
                        $wt_end += SECONDS_1DAY;
                    }

                    // Check if in planned downtime if exclusive downtimes.
                    if (($inclusive_downtimes == 0)
                        && (is_array($planned_downtimes))
                    ) {
                        $start_fixed = 0;

                        $n_planned_downtimes = count($planned_downtimes);
                        $i_planned_downtimes = 0;

                        $last_pd = end($planned_downtimes);

                        if ($wt_start > $last_pd['date_to']) {
                            // There's no more planned downtimes,
                            // accept remaining range.
                            $worktime[$i] = [];
                            $worktime[$i]['date_from'] = $wt_start;
                            $worktime[$i]['date_to']   = $wt_end;
                            $i++;
                        } else {
                            for ($i_planned_downtimes = 0; $i_planned_downtimes < $n_planned_downtimes; $i_planned_downtimes++) {
                                $pd = $planned_downtimes[$i_planned_downtimes];

                                if ($start_fixed == 1) {
                                    // Interval greater than found planned downtime.
                                    if ($wt_end < $pd['date_from']) {
                                        $worktime[$i] = [];
                                        // Wt_start already fixed.
                                        $worktime[$i]['date_from'] = $wt_start;
                                        $worktime[$i]['date_to']   = $wt_end;
                                        $i++;
                                        break;
                                    }

                                    if (( $wt_end >= $pd['date_from'] )
                                        && ( $wt_end <= $pd['date_to']  )
                                    ) {
                                        $worktime[$i] = [];
                                        // Wt_start already fixed.
                                        $worktime[$i]['date_from'] = $wt_start;
                                        $worktime[$i]['date_to']   = $pd['date_from'];
                                        $i++;
                                        break;
                                    }

                                    if ($wt_end > $pd['date_to']) {
                                        $worktime[$i] = [];
                                        // Wt_start already fixed.
                                        $worktime[$i]['date_from'] = $wt_start;
                                        $worktime[$i]['date_to']   = $pd['date_from'];
                                        $i++;

                                        $start_fixed = 0;
                                        // Search following planned downtimes,
                                        // we're still on work time!.
                                        $wt_start = $pd['date_from'];
                                    }
                                }

                                if (( $wt_start < $pd['date_from'])
                                    && ( $wt_end < $pd['date_from'])
                                ) {
                                    // Out of planned downtime: Add worktime.
                                    $worktime[$i] = [];
                                    $worktime[$i]['date_from'] = $wt_start;
                                    $worktime[$i]['date_to']   = $wt_end;
                                    $i++;
                                    break;
                                }

                                if (( $wt_start < $pd['date_from'])
                                    && ( $wt_end <= $pd['date_to'])
                                ) {
                                    // Not all worktime in downtime.
                                    $worktime[$i] = [];
                                    $worktime[$i]['date_from'] = $wt_start;
                                    $worktime[$i]['date_to']   = $pd['date_from'];
                                    $i++;
                                    break;
                                }

                                if (( $wt_start >= $pd['date_from'])
                                    && ( $wt_end <= $pd['date_to'])
                                ) {
                                    // All worktime in downtime, ignore.
                                    break;
                                }

                                if (( $wt_start >= $pd['date_from'])
                                    && ( $wt_start <= $pd['date_to'])
                                    && ( $wt_end > $pd['date_to'])
                                ) {
                                    // Begin of the worktime in downtime, adjust.
                                    // Search for end of worktime.
                                    $wt_start = $pd['date_to'];
                                    $start_fixed = 1;
                                }

                                if (( $wt_start < $pd['date_from'])
                                    && ( $wt_end > $pd['date_to'])
                                ) {
                                    // Begin of the worktime in downtime, adjust.
                                    // Search for end of worktime.
                                    $worktime[$i] = [];
                                    $worktime[$i]['date_from'] = $wt_start;
                                    $worktime[$i]['date_to']   = $pd['date_from'];
                                    $i++;
                                    $wt_start = $pd['date_to'];
                                    $start_fixed = 1;
                                }

                                if (($start_fixed == 1)
                                    && (($i_planned_downtimes + 1) == $n_planned_downtimes)
                                ) {
                                    // There's no more planned downtimes,
                                    // accept remaining range.
                                    $worktime[$i] = [];
                                    $worktime[$i]['date_from'] = $wt_start;
                                    $worktime[$i]['date_to']   = $wt_end;
                                    $i++;
                                    break;
                                }
                            }
                        }
                    } else {
                        // No planned downtimes scheduled.
                        $worktime[$i] = [];
                        $worktime[$i]['date_from'] = $wt_start;
                        $worktime[$i]['date_to']   = $wt_end;
                        $i++;
                    }
                }

                $t_day = strtotime(' + 1 days', $t_day);
            }
        }
    }

    // DEBUG
    // print "<pre>Umcompressed data debug:\n";
    // foreach ($uncompressed_data as $k => $caja) {
    // print "caja: $k\t" . $caja["utimestamp"] . "\n";
    // foreach ($caja["data"] as $dato) {
    // print "\t" . $dato["utimestamp"] . "\t" . $dato["datos"] . "\t" . date("Y/m/d H:i:s",$dato["utimestamp"]) . "\t" . $dato["obs"] . "\n";
    // }
    // }
    // print "</pre>";
    // Initialization.
    $global_return = [];

    $wt_check['idx'] = 0;
    $last_pool_id    = 0;
    $last_item_id    = 0;

    // Support to slices.
    $global_datetime_from = $datetime_from;
    $global_datetime_to   = $datetime_to;
    $range                = (($datetime_to - $datetime_from) / $slices);

    // Analysis begins.
    for ($count = 0; $count < $slices; $count++) {
        // Use strtotime based on local timezone to avoid datetime conversions.
        $datetime_from = strtotime(
            ' + '.($count * $range).' seconds',
            $global_datetime_from
        );
        $datetime_to = strtotime(
            ' + '.(($count + 1) * $range).' seconds',
            $global_datetime_from
        );

        if ((!isset($datetime_from)) || ($datetime_from === false)) {
            $datetime_from = ($global_datetime_from + ($count * $range));
        }

        if ((!isset($datetime_to)) || ($datetime_to === false)) {
            $datetime_to = ($global_datetime_from + (($count + 1) * $range));
        }

        $return = [];
        // Timing.
        $time_total       = 0;
        $time_in_ok       = 0;
        $time_in_error    = 0;
        $time_in_unknown  = 0;
        $time_in_not_init = 0;
        $time_in_down     = 0;
        $time_out         = 0;

        // Checks.
        $bad_checks       = 0;
        $ok_checks        = 0;
        $not_init_checks  = 0;
        $unknown_checks   = 0;
        $total_checks     = 0;

        if (is_array($uncompressed_data)) {
            $n_pools = count($uncompressed_data);
            for ($pool_index = $last_pool_id; $pool_index < $n_pools; $pool_index++) {
                $pool = $uncompressed_data[$pool_index];

                // Check limits.
                if (isset($uncompressed_data[($pool_index + 1)])) {
                    $next_pool = $uncompressed_data[($pool_index + 1)];
                } else {
                    $next_pool = null;
                }

                if (isset($next_pool)) {
                    $pool['next_utimestamp'] = $next_pool['utimestamp'];
                } else {
                    $pool['next_utimestamp'] = $global_datetime_to;
                }

                // Update last pool checked: avoid repetition.
                $last_pool_id = $pool_index;

                if ($datetime_from > $pool['utimestamp']) {
                    // Skip pool.
                    continue;
                }

                // Test if need to acquire current pool.
                if ((($datetime_from <= $pool['utimestamp'])
                    && ($datetime_to >= $pool['next_utimestamp']))
                    || ($datetime_to > $pool['utimestamp'])
                ) {
                    // Acquire pool to this slice.
                    $nitems_in_pool = count($pool['data']);
                    for ($i = 0; $i < $nitems_in_pool; $i++) {
                        $current_data = $pool['data'][$i];

                        if (($i + 1) >= $nitems_in_pool) {
                            // If pool exceded, check next pool timestamp.
                            $next_data = $next_pool;
                        } else {
                            // Pool not exceded, check next item.
                            $next_data = $pool['data'][($i + 1)];
                        }

                        if (isset($next_data['utimestamp'])) {
                            // Check next mark time in current pool.
                            $next_timestamp = $next_data['utimestamp'];
                        } else {
                            // Check last time -> datetime_to.
                            if (!isset($next_pool)) {
                                $next_timestamp = $global_datetime_to;
                            } else {
                                $next_timestamp = $datetime_to;
                            }
                        }

                        // Effective time limits for current data.
                        $wt_start = $current_data['utimestamp'];
                        $wt_end   = $next_timestamp;

                        // Remove time spent not in planning
                        // (and in planned downtime if needed).
                        $wt_check = sla_fixed_worktime(
                            $wt_start,
                            $wt_end,
                            $worktime,
                            $planned_downtimes,
                            $inclusive_downtimes,
                            $wt_check['idx']
                        );
                        $time_interval = $wt_check['interval'];

                        if (($wt_check['wt_valid'] == 1)) {
                            $time_total += $time_interval;

                            if ($time_interval > 0) {
                                $total_checks++;
                                if ((isset($current_data['datos']))
                                    && ($current_data['datos'] !== false)
                                ) {
                                    // Not unknown nor not init values.
                                    if (sla_check_value(
                                        $current_data['datos'],
                                        $min_value,
                                        $max_value,
                                        $inverse_interval
                                    )
                                    ) {
                                        $ok_checks++;
                                        $time_in_ok += $time_interval;
                                    } else {
                                        $bad_checks++;
                                        $time_in_error += $time_interval;
                                    }
                                } else {
                                    if ($current_data['datos'] === null) {
                                        $time_in_unknown += $time_interval;
                                        $unknown_checks++;
                                    } else if ($current_data['datos'] === false) {
                                        $time_in_not_init += $time_interval;
                                        $not_init_checks++;
                                    }
                                }
                            }

                            if ($inclusive_downtimes == 1) {
                                if ($wt_check['wt_in_downtime']) {
                                    // Add downtime interval as
                                    // OK in inclusion mode.
                                    $total_checks++;
                                    $ok_checks++;
                                    $time_total   += $wt_check['downtime_interval'];
                                    $time_in_down += $wt_check['downtime_interval'];
                                }
                            }
                        } else {
                            $time_out += $time_interval;
                            if ($wt_check['wt_in_downtime']) {
                                $time_out += $wt_check['downtime_interval'];
                            }

                            // Ignore worktime, is in an invalid period:
                            // scheduled downtimes in exclusion mode
                            // not 24x7 sla's.
                        }
                    }
                } else {
                    break;
                }
            }
        } else {
            // If monitor in not-init status => no data to show.
            $time_in_not_init  = ($datetime_to - $datetime_from);
            $time_total       += $time_in_not_init;
            $not_init_checks++;
        }

        // Timing.
        $return['time_total']      = $time_total;
        $return['time_ok']         = $time_in_ok;
        $return['time_error']      = $time_in_error;
        $return['time_unknown']    = $time_in_unknown;
        $return['time_not_init']   = $time_in_not_init;
        $return['time_downtime']   = $time_in_down;
        $return['time_out']        = $time_out;

        // Checks.
        $return['checks_total']    = $total_checks;
        $return['checks_ok']       = $ok_checks;
        $return['checks_error']    = $bad_checks;
        $return['checks_unknown']  = $unknown_checks;
        $return['checks_not_init'] = $not_init_checks;

        // SLA.
        $return['SLA'] = reporting_sla_get_compliance_from_array($return);
        $return['sla_fixed'] = sla_truncate(
            $return['SLA'],
            $config['graph_precision']
        );

        // Time ranges.
        $return['date_from'] = $datetime_from;
        $return['date_to']   = $datetime_to;

        if ($slices > 1) {
            array_push($global_return, $return);
        }
    }

    if ($slices > 1) {
        return $global_return;
    }

    return $return;
}


/**
 * reporting_availability
 *
 *  Generates a structure the report.
 */
function reporting_availability($report, $content, $date=false, $time=false)
{
    global $config;

    $return = [];
    $return['type'] = 'availability';
    $return['subtype'] = $content['group_by_agent'];

    if (empty($content['name'])) {
        $content['name'] = __('Availability');
    }

    if ($date) {
        $datetime_to = strtotime($date.' '.$time);
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text(
        $report,
        $content
    );

    $return['id_rc'] = $content['id_rc'];

    if ($content['show_graph']) {
        $return['kind_availability'] = 'address';
    } else {
        $return['kind_availability'] = 'module';
    }

    if (empty($content['subitems'])) {
        $sql = sprintf(
            '
            SELECT id_agent_module,
                server_name, operation
            FROM treport_content_item
            WHERE id_report_content = %d',
            $content['id_rc']
        );

        $items = db_process_sql($sql);
    } else {
        $items = $content['subitems'];
    }

    $data = [];

    $avg = 0;
    $min = null;
    $min_text = '';
    $max = null;
    $max_text = '';
    $count = 0;

    $style = io_safe_output($content['style']);
    if ($style['hide_notinit_agents']) {
        $aux_id_agents = $agents;
        $i = 0;
        foreach ($items as $item) {
            $utimestamp = db_get_value('utimestamp', 'tagente_datos', 'id_agente_modulo', $item['id_agent_module'], true);
            if (($utimestamp === false) || (intval($utimestamp) > intval($datetime_to))) {
                unset($items[$i]);
            }

            $i++;
        }
    }

    if (!empty($items)) {
        foreach ($items as $item) {
            // aaMetaconsole connection
            $server_name = $item['server_name'];
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_load_external_db($connection) != NOERR) {
                    // ui_print_error_message ("Error connecting to ".$server_name);
                    continue;
                }
            }

            if (modules_is_disable_agent($item['id_agent_module'])
                || modules_is_not_init($item['id_agent_module'])
            ) {
                // Restore dbconnection
                if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                    metaconsole_restore_db();
                }

                continue;
            }

            $row = [];

            $text = '';

            $row['data'] = reporting_advanced_sla(
                $item['id_agent_module'],
                ($report['datetime'] - $content['period']),
                $report['datetime'],
                null,
                // min_value -> dynamic
                null,
                // max_value -> dynamic
                null,
                // inverse_interval -> dynamic
                [
                    '1' => $content['sunday'],
                    '2' => $content['monday'],
                    '3' => $content['tuesday'],
                    '4' => $content['wednesday'],
                    '5' => $content['thursday'],
                    '6' => $content['friday'],
                    '7' => $content['saturday'],
                ],
                $content['time_from'],
                $content['time_to']
            );

            // HACK it is saved in show_graph field.
            // Show interfaces instead the modules
            if ($content['show_graph']) {
                $text = $row['data']['availability_item'] = agents_get_address(
                    modules_get_agentmodule_agent($item['id_agent_module'])
                );

                if (empty($text)) {
                    $text = $row['data']['availability_item'] = __('No Address');
                }
            } else {
                $text = $row['data']['availability_item'] = modules_get_agentmodule_name(
                    $item['id_agent_module']
                );
            }

            $row['data']['agent'] = modules_get_agentmodule_agent_alias(
                $item['id_agent_module']
            );

            $text = $row['data']['agent'].' ('.$text.')';

            // Restore dbconnection
            if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
                metaconsole_restore_db();
            }

            // find order
            $row['data']['order'] = $row['data']['SLA'];

            $percent_ok = $row['data']['SLA'];
            $avg = ((($avg * $count) + $percent_ok) / ($count + 1));
            if (is_null($min)) {
                $min = $percent_ok;
                $min_text = $text;
            } else {
                if ($min > $percent_ok) {
                    $min = $percent_ok;
                    $min_text = $text;
                }
            }

            if (is_null($max)) {
                $max = $percent_ok;
                $max_text = $text;
            } else {
                if ($max < $percent_ok) {
                    $max = $percent_ok;
                    $max_text = $text;
                }
            }

            $data[] = $row['data'];
            $count++;
        }

        switch ($content['order_uptodown']) {
            case REPORT_ITEM_ORDER_BY_AGENT_NAME:
                $temp = [];
                foreach ($data as $row) {
                    $i = 0;
                    foreach ($temp as $t_row) {
                        if (strcmp($row['data']['agent'], $t_row['agent']) < 0) {
                            break;
                        }

                        $i++;
                    }

                    array_splice($temp, $i, 0, [$row]);
                }

                $data = $temp;
            break;

            case REPORT_ITEM_ORDER_BY_ASCENDING:
                $temp = [];
                foreach ($data as $row) {
                    $i = 0;
                    foreach ($temp as $t_row) {
                        if ($row['data']['SLA'] < $t_row['order']) {
                            break;
                        }

                        $i++;
                    }

                    array_splice($temp, $i, 0, [$row]);
                }

                $data = $temp;
            break;

            case REPORT_ITEM_ORDER_BY_DESCENDING:
                $temp = [];
                foreach ($data as $row) {
                    $i = 0;
                    foreach ($temp as $t_row) {
                        if ($row['data']['SLA'] > $t_row['order']) {
                            break;
                        }

                        $i++;
                    }

                    array_splice($temp, $i, 0, [$row]);
                }

                $data = $temp;
            break;
        }
    }

    $return['data'] = $data;
    $return['resume'] = [];
    $return['resume']['resume'] = $content['show_resume'];
    $return['resume']['min_text'] = $min_text;
    $return['resume']['min'] = $min;
    $return['resume']['avg'] = $avg;
    $return['resume']['max_text'] = $max_text;
    $return['resume']['max'] = $max;
    $return['fields'] = [];
    $return['fields']['total_time'] = $content['total_time'];
    $return['fields']['time_failed'] = $content['time_failed'];
    $return['fields']['time_in_ok_status'] = $content['time_in_ok_status'];
    $return['fields']['time_in_unknown_status'] = $content['time_in_unknown_status'];
    $return['fields']['time_of_not_initialized_module'] = $content['time_of_not_initialized_module'];
    $return['fields']['time_of_downtime'] = $content['time_of_downtime'];
    $return['fields']['total_checks'] = $content['total_checks'];
    $return['fields']['checks_failed'] = $content['checks_failed'];
    $return['fields']['checks_in_ok_status'] = $content['checks_in_ok_status'];
    $return['fields']['unknown_checks'] = $content['unknown_checks'];
    $return['fields']['agent_max_value'] = $content['agent_max_value'];
    $return['fields']['agent_min_value'] = $content['agent_min_value'];

    return reporting_check_structure_content($return);
}


 /**
  * Reporting_availability_graph.
  *
  * @param array   $report  Info report.
  * @param array   $content Content data.
  * @param boolean $pdf     Output type PDF.
  *
  * @return array Generates a structure the report.
  */
function reporting_availability_graph($report, $content, $pdf=false)
{
    global $config;
    $return = [];
    $return['type'] = 'availability_graph';
    $ttl = 1;
    if ($pdf) {
        $ttl = 2;
    }

    if (empty($content['name'])) {
        $content['name'] = __('Availability');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['failover_type'] = $content['failover_type'];
    $return['date'] = reporting_get_date_text($report, $content);

    // Get chart.
    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    $return['id_rc'] = $content['id_rc'];

    $edge_interval = 10;

    if (empty($content['subitems'])) {
        $slas = io_safe_output(
            db_get_all_rows_field_filter(
                'treport_content_sla_combined',
                'id_report_content',
                $content['id_rc']
            )
        );
    } else {
        $slas = $content['subitems'];
    }

    if (empty($slas)) {
        $return['failed'] = __('There are no SLAs defined');
    } else {
        include_once $config['homedir'].'/include/functions_planned_downtimes.php';
        $metaconsole_on = is_metaconsole();

        $urlImage = ui_get_full_url(false, true, false, false);

        $sla_failed = false;
        $total_SLA = 0;
        $total_result_SLA = 'ok';
        $sla_showed = [];
        $sla_showed_values = [];

        $priority_mode = $content['style']['priority_mode'];

        foreach ($slas as $sla) {
            $server_name = $sla['server_name'];

            // Metaconsole connection.
            if ($metaconsole_on && $server_name != '') {
                $connection = metaconsole_get_connection($server_name);
                if (metaconsole_connect($connection) != NOERR) {
                    continue;
                }
            }

            if ($content['failover_mode']) {
                $sla_failover = [];
                $sla_failover['primary'] = $sla;
                if (isset($sla['id_agent_module_failover']) === true
                    && $sla['id_agent_module_failover'] != 0
                ) {
                    $sla_failover['failover'] = $sla;
                    $sla_failover['failover']['id_agent_module'] = $sla['id_agent_module_failover'];
                } else {
                    $sql_relations = sprintf(
                        'SELECT module_b
                        FROM tmodule_relationship
                        WHERE module_a = %d
                        AND type = "failover"',
                        $sla['id_agent_module']
                    );
                    $relations = db_get_all_rows_sql($sql_relations);
                    if (isset($relations) === true
                        && is_array($relations) === true
                    ) {
                        foreach ($relations as $key => $value) {
                            $sla_failover['failover_'.$key] = $sla;
                            $sla_failover['failover_'.$key]['id_agent_module'] = $value['module_b'];
                        }
                    }
                }

                // For graph slice for module-interval, if not slice=0.
                $module_interval = modules_get_interval($sla['id_agent_module']);
                $slice = ($content['period'] / $module_interval);
                $data_combined = [];

                foreach ($sla_failover as $k_sla => $v_sla) {
                    $sla_array = data_db_uncompress_module(
                        $v_sla,
                        $content,
                        $report['datetime'],
                        $slice
                    );

                    if ($content['failover_type'] == REPORT_FAILOVER_TYPE_NORMAL) {
                        $return = prepare_data_for_paint(
                            $v_sla,
                            $sla_array,
                            $content,
                            $report['datetime'],
                            $return,
                            $k_sla,
                            $pdf
                        );
                    }

                    $data_combined[] = $sla_array;
                }

                if (isset($data_combined) === true
                    && is_array($data_combined) === true
                    && count($data_combined) > 0
                ) {
                    $count_failover = count($data_combined);

                    $data_a = $data_combined[0];
                    for ($i = 1; $count_failover > $i; $i++) {
                        $data_a = array_map(
                            function ($primary, $failover) {
                                $return_map = [];
                                if ($primary['date_from'] === $failover['date_from']
                                    && $primary['date_to'] === $failover['date_to']
                                ) {
                                    if ($primary['time_ok'] < $failover['time_ok']) {
                                        $primary['time_total'] = $failover['time_total'];
                                        $primary['time_ok'] = $failover['time_ok'];
                                        $primary['time_error'] = $failover['time_error'];
                                        $primary['time_unknown'] = $failover['time_unknown'];
                                        $primary['time_not_init'] = $failover['time_not_init'];
                                        $primary['time_downtime'] = $failover['time_downtime'];
                                        $primary['time_out'] = $failover['time_out'];
                                        $primary['checks_total'] = $failover['checks_total'];
                                        $primary['checks_ok'] = $failover['checks_ok'];
                                        $primary['checks_error'] = $failover['checks_error'];
                                        $primary['checks_unknown'] = $failover['checks_unknown'];
                                        $primary['checks_not_init'] = $failover['checks_not_init'];
                                        $primary['SLA'] = $failover['SLA'];
                                        $primary['sla_fixed'] = $failover['sla_fixed'];
                                    }

                                    $return_map = $primary;
                                }

                                return $return_map;
                            },
                            $data_a,
                            $data_combined[($i)]
                        );
                    }

                    $return = prepare_data_for_paint(
                        $sla,
                        $data_a,
                        $content,
                        $report['datetime'],
                        $return,
                        'result',
                        $pdf
                    );
                }
            } else {
                $sla_array = data_db_uncompress_module(
                    $sla,
                    $content,
                    $report['datetime']
                );

                $return = prepare_data_for_paint(
                    $sla,
                    $sla_array,
                    $content,
                    $report['datetime'],
                    $return,
                    '',
                    $pdf
                );
            }

            if ($metaconsole_on) {
                // Restore db connection.
                metaconsole_restore_db();
            }
        }

        // SLA items sorted descending.
        if ($content['top_n'] == 2) {
            arsort($return['data']['']);
        } else if ($content['top_n'] == 1) {
            // SLA items sorted ascending.
            asort($sla_showed_values);
        }

        // Order data for ascending or descending.
        if ($content['top_n'] != 0) {
            switch ($content['top_n']) {
                case 1:
                    // Order tables.
                    $temp = [];
                    foreach ($return['data'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] < $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['data'] = $temp;

                    // Order graphs.
                    $temp = [];
                    foreach ($return['charts'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] < $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['charts'] = $temp;
                break;

                case 2:
                    // Order tables.
                    $temp = [];
                    foreach ($return['data'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] > $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['data'] = $temp;

                    // Order graph.
                    $temp = [];
                    foreach ($return['charts'] as $row) {
                        $i = 0;
                        foreach ($temp as $t_row) {
                            if ($row['sla_value'] > $t_row['order']) {
                                break;
                            }

                            $i++;
                        }

                        array_splice($temp, $i, 0, [$row]);
                    }

                    $return['charts'] = $temp;
                break;

                default:
                    // If not posible.
                break;
            }
        }
    }

    return reporting_check_structure_content($return);
}


/**
 * Return data db uncompress for module.
 *
 * @param array   $sla      Data neccesary for db_uncompress.
 * @param array   $content  Conetent report.
 * @param array   $datetime Date.
 * @param integer $slice    Defined slice.
 *
 * @return array
 */
function data_db_uncompress_module($sla, $content, $datetime, $slice=0)
{
    // Controller min and max == 0 then dinamic min and max critical.
    $dinamic_text = 0;
    if ($sla['sla_min'] == 0 && $sla['sla_max'] == 0) {
        $sla['sla_min'] = null;
        $sla['sla_max'] = null;
        $dinamic_text = __('Dynamic');
    }

    // Controller inverse interval.
    $inverse_interval = 0;
    if ((isset($sla['sla_max'])) && (isset($sla['sla_min']))) {
        if ($sla['sla_max'] < $sla['sla_min']) {
            $content_sla_max  = $sla['sla_max'];
            $sla['sla_max']   = $sla['sla_min'];
            $sla['sla_min']   = $content_sla_max;
            $inverse_interval = 1;
            $dinamic_text = __('Inverse');
        }
    }

    if ($slice === 0) {
        // For graph slice for module-interval, if not slice=0.
        $module_interval = modules_get_interval($sla['id_agent_module']);
        $slice = ($content['period'] / $module_interval);
    }

    // Call functions sla.
    $sla_array = [];
    $sla_array = reporting_advanced_sla(
        $sla['id_agent_module'],
        ($datetime - $content['period']),
        $datetime,
        $sla['sla_min'],
        $sla['sla_max'],
        $inverse_interval,
        [
            '1' => $content['sunday'],
            '2' => $content['monday'],
            '3' => $content['tuesday'],
            '4' => $content['wednesday'],
            '5' => $content['thursday'],
            '6' => $content['friday'],
            '7' => $content['saturday'],
        ],
        $content['time_from'],
        $content['time_to'],
        $slice
    );

    return $sla_array;
}


/**
 * Return array planned downtimes.
 *
 * @param integer $id_agent_module Id module.
 * @param integer $datetime        Date utimestamp.
 * @param integer $period          Period utimestamp.
 *
 * @return array
 */
function reporting_get_planned_downtimes_sla($id_agent_module, $datetime, $period)
{
    $planned_downtimes = reporting_get_planned_downtimes_intervals(
        $id_agent_module,
        ($datetime - $period),
        $datetime
    );

    if ((is_array($planned_downtimes))
        && (count($planned_downtimes) > 0)
    ) {
        // Sort retrieved planned downtimes.
        usort(
            $planned_downtimes,
            function ($a, $b) {
                $a = intval($a['date_from']);
                $b = intval($b['date_from']);
                if ($a == $b) {
                    return 0;
                }

                return ($a < $b) ? (-1) : 1;
            }
        );

        // Compress (overlapped) planned downtimes.
        $npd = count($planned_downtimes);
        for ($i = 0; $i < $npd; $i++) {
            if (isset($planned_downtimes[($i + 1)])) {
                if ($planned_downtimes[$i]['date_to'] >= $planned_downtimes[($i + 1)]['date_from']) {
                    // Merge.
                    $planned_downtimes[$i]['date_to'] = $planned_downtimes[($i + 1)]['date_to'];
                    array_splice($planned_downtimes, ($i + 1), 1);
                    $npd--;
                }
            }
        }
    } else {
        $planned_downtimes = [];
    }

    return $planned_downtimes;
}


/**
 * Prepare data for Paint in report.
 *
 * @param array   $sla       Data Module to sla.
 * @param array   $sla_array Data uncompressed.
 * @param array   $content   Content report data.
 * @param integer $datetime  Date.
 * @param array   $return    Array return.
 * @param string  $failover  Type primary, failover, Result.
 * @param boolean $pdf       Chart pdf mode.
 *
 * @return array Return modify.
 */
function prepare_data_for_paint(
    $sla,
    $sla_array,
    $content,
    $datetime,
    $return,
    $failover='',
    $pdf=false
) {
    $data = [];
    $alias_agent = modules_get_agentmodule_agent_alias(
        $sla['id_agent_module']
    );
    $name_module = modules_get_agentmodule_name(
        $sla['id_agent_module']
    );

    $data['agent'] = $alias_agent;
    $data['module'] = $name_module;
    $data['max'] = $sla['sla_max'];
    $data['min'] = $sla['sla_min'];
    $data['sla_limit'] = $sla['sla_limit'];
    $data['dinamic_text'] = $dinamic_text;
    $data['failover'] = $failover;
    if (isset($sla_array[0])) {
        $data['time_total']      = 0;
        $data['time_ok']         = 0;
        $data['time_error']      = 0;
        $data['time_unknown']    = 0;
        $data['time_not_init']   = 0;
        $data['time_downtime']   = 0;
        $data['checks_total']    = 0;
        $data['checks_ok']       = 0;
        $data['checks_error']    = 0;
        $data['checks_unknown']  = 0;
        $data['checks_not_init'] = 0;

        $raw_graph = [];
        $i = 0;
        foreach ($sla_array as $value_sla) {
            $data['time_total']      += $value_sla['time_total'];
            $data['time_ok']         += $value_sla['time_ok'];
            $data['time_error']      += $value_sla['time_error'];
            $data['time_unknown']    += $value_sla['time_unknown'];
            $data['time_downtime']   += $value_sla['time_downtime'];
            $data['time_not_init']   += $value_sla['time_not_init'];
            $data['checks_total']    += $value_sla['checks_total'];
            $data['checks_ok']       += $value_sla['checks_ok'];
            $data['checks_error']    += $value_sla['checks_error'];
            $data['checks_unknown']  += $value_sla['checks_unknown'];
            $data['checks_not_init'] += $value_sla['checks_not_init'];

            // Generate raw data for graph.
            $period = reporting_sla_get_status_period(
                $value_sla,
                $priority_mode
            );
            $raw_graph[$i]['data'] = reporting_translate_sla_status_for_graph(
                $period
            );
            $raw_graph[$i]['utimestamp'] = ($value_sla['date_to'] - $value_sla['date_from']);
            $i++;
        }

        $data['sla_value'] = reporting_sla_get_compliance_from_array(
            $data
        );
        $data['sla_fixed'] = sla_truncate(
            $data['sla_value'],
            $config['graph_precision']
        );
    } else {
        // Show only table not divider in slice for defect slice=1.
        $data['time_total']      = $sla_array['time_total'];
        $data['time_ok']         = $sla_array['time_ok'];
        $data['time_error']      = $sla_array['time_error'];
        $data['time_unknown']    = $sla_array['time_unknown'];
        $data['time_downtime']   = $sla_array['time_downtime'];
        $data['time_not_init']   = $sla_array['time_not_init'];
        $data['checks_total']    = $sla_array['checks_total'];
        $data['checks_ok']       = $sla_array['checks_ok'];
        $data['checks_error']    = $sla_array['checks_error'];
        $data['checks_unknown']  = $sla_array['checks_unknown'];
        $data['checks_not_init'] = $sla_array['checks_not_init'];
        $data['sla_value']       = $sla_array['SLA'];
    }

    // Checks whether or not it meets the SLA.
    if ($data['sla_value'] >= $sla['sla_limit']) {
        $data['sla_status'] = 1;
        $sla_failed = false;
    } else {
        $sla_failed = true;
        $data['sla_status'] = 0;
    }

    // Do not show right modules if 'only_display_wrong' is active.
    if ($content['only_display_wrong'] && $sla_failed == false) {
        return $return;
    }

    // Find order.
    $data['order'] = $data['sla_value'];
    $return['data'][] = $data;

    $data_init = -1;
    $acum = 0;
    $sum = 0;
    $array_result = [];
    $i = 0;
    foreach ($raw_graph as $key => $value) {
        if ($data_init == -1) {
            $data_init = $value['data'];
            $acum      = $value['utimestamp'];
            $sum       = $value['data'];
        } else {
            if ($data_init == $value['data']) {
                $acum = ($acum + $value['utimestamp']);
                $sum  = ($sum + $value['real_data']);
            } else {
                $array_result[$i]['data'] = $data_init;
                $array_result[$i]['utimestamp'] = $acum;
                $array_result[$i]['real_data'] = $sum;
                $i++;
                $data_init = $value['data'];
                $acum = $value['utimestamp'];
                $sum = $value['real_data'];
            }
        }
    }

    $array_result[$i]['data'] = $data_init;
    $array_result[$i]['utimestamp'] = $acum;
    $array_result[$i]['real_data'] = $sum;

    // Slice graphs calculation.
    $dataslice = [];
    $dataslice['agent'] = $alias_agent;
    $dataslice['module'] = $name_module;
    $dataslice['order'] = $data['sla_value'];
    $dataslice['checks_total'] = $data['checks_total'];
    $dataslice['checks_ok'] = $data['checks_ok'];
    $dataslice['time_total'] = $data['time_total'];
    $dataslice['time_not_init'] = $data['time_not_init'];
    $dataslice['sla_status'] = $data['sla_status'];
    $dataslice['sla_value'] = $data['sla_value'];

    $height = 80;
    if ($failover !== '' && $failover !== 'result') {
        $height = 50;
    }

    $dataslice['chart'] = graph_sla_slicebar(
        $sla['id_agent_module'],
        $content['period'],
        $sla['sla_min'],
        $sla['sla_max'],
        $datetime,
        $content,
        $content['time_from'],
        $content['time_to'],
        100,
        $height,
        $urlImage,
        ($pdf) ? 2 : 0,
        $array_result,
        false
    );

    $return['charts'][] = $dataslice;

    return $return;
}


/**
 * reporting_increment
 *
 *  Generates a structure the report.
 */
function reporting_increment($report, $content)
{
    global $config;

    $return = [];
    $return['type'] = 'increment';
    if (empty($content['name'])) {
        $content['name'] = __('Increment');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['id_agent_module'] = $content['id_agent_module'];
    $return['id_agent'] = $content['id_agent'];

    $id_agent_module = $content['id_agent_module'];
    $period = (int) $content['period'];

    $return['from'] = (time() - $period);
    $return['to'] = time();

    $return['data'] = [];

    if (is_metaconsole()) {
        $sql1 = 'SELECT datos FROM tagente_datos WHERE id_agente_modulo = '.$id_agent_module.' 
                                     AND utimestamp <= '.(time() - $period).' ORDER BY utimestamp DESC';
        $sql2 = 'SELECT datos FROM tagente_datos WHERE id_agente_modulo = '.$id_agent_module.' ORDER BY utimestamp DESC';

        $servers = db_get_all_rows_sql(
            'SELECT *
        FROM tmetaconsole_setup
        WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        $result = [];
        $count_modules = 0;
        foreach ($servers as $server) {
            // If connection was good then retrieve all data server
            if (metaconsole_connect($server) == NOERR) {
                $connection = true;
            } else {
                $connection = false;
            }

            $old_data = db_get_value_sql($sql1);

            $last_data = db_get_value_sql($sql2);
        }
    } else {
        $old_data = db_get_value_sql(
            'SELECT datos FROM tagente_datos WHERE id_agente_modulo = '.$id_agent_module.' 
                                     AND utimestamp <= '.(time() - $period).' ORDER BY utimestamp DESC'
        );

        $last_data = db_get_value_sql('SELECT datos FROM tagente_datos WHERE id_agente_modulo = '.$id_agent_module.' ORDER BY utimestamp DESC');
    }

    if (!is_metaconsole()) {
    }

    if ($old_data === false || $last_data === false) {
        $return['data']['message'] = __('The monitor have no data in this range of dates or monitor type is not numeric');
        $return['data']['error'] = true;
    } else if (is_numeric($old_data) && is_numeric($last_data)) {
        $return['data']['old'] = $old_data;
        $return['data']['now'] = $last_data;
        $increment = ($old_data - $last_data);

        if ($increment < 0) {
            $return['data']['inc'] = 'positive';
            $return['data']['inc_data'] = ($last_data - $old_data);
        } else if ($increment == 0) {
            $return['data']['inc'] = 'neutral';
            $return['data']['inc_data'] = 0;
        } else {
            $return['data']['inc'] = 'negative';
            $return['data']['inc_data'] = ($old_data - $last_data);
        }
    } else {
        $return['data']['message'] = __('The monitor type is not numeric');
        $return['data']['error'] = true;
    }

    return reporting_check_structure_content($return);
}


/**
 * reporting_general
 *
 *  Generates a structure the report.
 */
function reporting_general($report, $content)
{
    global $config;

    $return = [];
    $return['type'] = 'general';
    $return['subtype'] = $content['group_by_agent'];
    $return['resume'] = $content['show_resume'];

    if (empty($content['name'])) {
        $content['name'] = __('General');
    }

    $return['title'] = $content['name'];
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text(
        $report,
        $content
    );

    $return['data'] = [];
    $return['avg_value'] = 0;
    $return['min'] = [];
    $return['min']['value'] = null;
    $return['min']['formated_value'] = null;
    $return['min']['agent'] = null;
    $return['min']['module'] = null;
    $return['max'] = [];
    $return['max']['value'] = null;
    $return['max']['formated_value'] = null;
    $return['max']['agent'] = null;
    $return['max']['module'] = null;
    $return['show_in_same_row'] = $content['style']['show_in_same_row'];

    if (empty($content['subitems'])) {
        $generals = db_get_all_rows_filter(
            'treport_content_item',
            ['id_report_content' => $content['id_rc']]
        );
    } else {
        $generals = $content['subitems'];
    }

    if (empty($generals)) {
        $generals = [];
    }

    $i = 0;
    $index = 0;
    $is_string = [];

    foreach ($generals as $row) {
        // Metaconsole connection
        $server_name = $row['server_name'];
        if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
            $connection = metaconsole_get_connection($server_name);
            if (metaconsole_load_external_db($connection) != NOERR) {
                // ui_print_error_message ("Error connecting to ".$server_name);
                continue;
            }
        }

        if (modules_is_disable_agent($row['id_agent_module'])
            || modules_is_not_init($row['id_agent_module'])
        ) {
            if (is_metaconsole()) {
                // Restore db connection
                metaconsole_restore_db();
            }

            continue;
        }

        $mod_name = modules_get_agentmodule_name($row['id_agent_module']);
        $ag_name = modules_get_agentmodule_agent_alias($row['id_agent_module']);
        $name_agent = modules_get_agentmodule_agent_name($row['id_agent_module']);
        $type_mod = modules_get_last_value($row['id_agent_module']);
        $is_string[$index] = modules_is_string($row['id_agent_module']);
        $unit = db_get_value(
            'unit',
            'tagente_modulo',
            'id_agente_modulo',
            $row['id_agent_module']
        );
        $id_module_type = db_get_value('id_tipo_modulo', 'tagente_modulo', 'nombre', $mod_name);
        if ($content['period'] == 0) {
            $data_res[$index] = modules_get_last_value($row['id_agent_module']);
        } else {
            if (is_numeric($type_mod) && !$is_string[$index]) {
                switch ($row['operation']) {
                    case 'sum':
                        $data_res[$index] = reporting_get_agentmodule_data_sum(
                            $row['id_agent_module'],
                            $content['period'],
                            $report['datetime']
                        );
                    break;

                    case 'max':
                        $data_res[$index] = reporting_get_agentmodule_data_max(
                            $row['id_agent_module'],
                            $content['period']
                        );
                    break;

                    case 'min':
                        $data_res[$index] = reporting_get_agentmodule_data_min(
                            $row['id_agent_module'],
                            $content['period']
                        );
                    break;

                    case 'avg':
                    default:
                        $data_res[$index] = reporting_get_agentmodule_data_average(
                            $row['id_agent_module'],
                            $content['period']
                        );
                    break;
                }
            } else {
                $data_res[$index] = $type_mod;
            }
        }

        switch ($content['group_by_agent']) {
            case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
                $id_agent_module[$index] = $row['id_agent_module'];
                $agent_name[$index] = $ag_name;
                $module_name[$index] = $mod_name;
                $units[$index] = $unit;
                $id_module_types[$index] = $id_module_type;
                $operations[$index] = $row['operation'];
            break;

            case REPORT_GENERAL_GROUP_BY_AGENT:
                $id_module_types[$index] = $id_module_type;
                if ($id_module_types[$index] == 2 || $id_module_types[$index] == 6 || $id_module_types[$index] == 9 || $id_module_types[$index] == 18) {
                    $data_res[$index] = round($data_res[$index], 0, PHP_ROUND_HALF_DOWN);
                }

                if ($id_module_types[$index] == 2 || $id_module_types[$index] == 6 || $id_module_types[$index] == 9 || $id_module_types[$index] == 18) {
                    if ($data_res[$index] == 1) {
                        $data_res[$index] = 'Up';
                    } else if ($data_res[$index] == 0) {
                        $data_res[$index] = 'Down';
                    }
                }

                if ($data_res[$index] === false) {
                    $return['data'][$name_agent][$mod_name] = null;
                } else {
                    if (!is_numeric($data_res[$index])) {
                        $return['data'][$name_agent][$mod_name] = $data_res[$index];
                    } else {
                        $return['data'][$name_agent][$mod_name] = format_for_graph($data_res[$index], 2).' '.$unit;
                    }
                }
            break;
        }

        // Calculate the avg, min and max
        if (is_numeric($data_res[$index]) && !$is_string[$index]) {
            $change_min = false;
            if (is_null($return['min']['value'])) {
                $change_min = true;
            } else {
                if ($return['min']['value'] > $data_res[$index]) {
                    $change_min = true;
                }
            }

            if ($change_min) {
                $return['min']['value'] = $data_res[$index];
                $return['min']['formated_value'] = format_for_graph($data_res[$index], 2).' '.$unit;
                $return['min']['agent'] = $ag_name;
                $return['min']['module'] = $mod_name;
            }

            $change_max = false;
            if (is_null($return['max']['value'])) {
                $change_max = true;
            } else {
                if ($return['max']['value'] < $data_res[$index]) {
                    $change_max = true;
                }
            }

            if ($change_max) {
                $return['max']['value'] = $data_res[$index];
                $return['max']['formated_value'] = format_for_graph($data_res[$index], 2).' '.$unit;
                $return['max']['agent'] = $ag_name;
                $return['max']['module'] = $mod_name;
            }

            if ($i == 0) {
                $return['avg_value'] = $data_res[$index];
            } else {
                $return['avg_value'] = ((($return['avg_value'] * $i) / ($i + 1)) + ($data_res[$index] / ($i + 1)));
            }
        }

        $index++;
        $i++;

        // Restore dbconnection
        if (($config['metaconsole'] == 1) && $server_name != '' && is_metaconsole()) {
            metaconsole_restore_db();
        }
    }

    switch ($content['group_by_agent']) {
        case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
            switch ($content['order_uptodown']) {
                case REPORT_ITEM_ORDER_BY_AGENT_NAME:
                    array_multisort(
                        $agent_name,
                        SORT_ASC,
                        $data_res,
                        SORT_ASC,
                        $module_name,
                        SORT_ASC,
                        $id_agent_module,
                        SORT_ASC,
                        $operations,
                        SORT_ASC
                    );
                break;

                case REPORT_ITEM_ORDER_BY_ASCENDING:
                    array_multisort(
                        $data_res,
                        SORT_ASC,
                        $agent_name,
                        SORT_ASC,
                        $module_name,
                        SORT_ASC,
                        $id_agent_module,
                        SORT_ASC,
                        $operations,
                        SORT_ASC
                    );
                break;

                case REPORT_ITEM_ORDER_BY_DESCENDING:
                    array_multisort(
                        $data_res,
                        SORT_DESC,
                        $agent_name,
                        SORT_ASC,
                        $module_name,
                        SORT_ASC,
                        $id_agent_module,
                        SORT_ASC,
                        $operations,
                        SORT_ASC
                    );
                break;

                case REPORT_ITEM_ORDER_BY_UNSORT:
                break;
            }

            $i = 0;
            foreach ($data_res as $d) {
                $data = [];
                $data['agent'] = $agent_name[$i];
                $data['module'] = $module_name[$i];
                $data['id_agent_module'] = $id_agent_module[$i];
                $data['id_agent'] = agents_get_agent_id_by_module_id($id_agent_module[$i]);
                $data['id_module_type'] = $id_module_types[$i];
                $data['operator'] = '';
                if ($content['period'] != 0) {
                    switch ($operations[$i]) {
                        case 'sum':
                            $data['operator'] = __('Summatory');
                        break;

                        case 'min':
                            $data['operator'] = __('Minimum');
                        break;

                        case 'max':
                            $data['operator'] = __('Maximum');
                        break;

                        case 'avg':
                        default:
                            $data['operator'] = __('Rate');
                        break;
                    }
                }

                if ($d === false) {
                    $data['value'] = null;
                } else {
                    switch ($config['dbtype']) {
                        case 'mysql':
                        case 'postgresql':
                        break;

                        case 'oracle':
                            if (preg_match('/[0-9]+,[0-9]E+[+-][0-9]+/', $d)) {
                                $d = oracle_format_float_to_php($d);
                            }
                        break;
                    }

                    if (!is_numeric($d) || $is_string[$i]) {
                        $data['value'] = $d;
                        // to see the chains on the table
                        $data['formated_value'] = $d;
                    } else {
                        $data['value'] = $d;
                        $data['formated_value'] = format_for_graph($d, 2).' '.$units[$i];
                    }
                }

                $return['data'][] = $data;

                $i++;
            }
        break;
    }

    return reporting_check_structure_content($return);
}


function reporting_custom_graph(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null,
    $type_report='custom_graph',
    $pdf=false
) {
    global $config;

    $modules = [];

    include_once $config['homedir'].'/include/functions_graph.php';

    if ($type_report == 'custom_graph') {
        if (is_metaconsole()) {
            $servers = metaconsole_get_connection_names();
            foreach ($servers as $server) {
                $connection = metaconsole_get_connection($server);
                if (metaconsole_connect($connection) != NOERR) {
                    continue;
                }
            }
        }
    }

    $graph = db_get_row('tgraph', 'id_graph', $content['id_gs']);

    $return = [];
    $return['type'] = 'custom_graph';

    if (empty($content['name'])) {
        if ($type_report == 'custom_graph') {
            $content['name'] = __('Custom graph');
        } else if ($type_report == 'automatic_graph') {
            $content['name'] = __('Automatic combined graph');
        } else {
            $content['name'] = '';
        }
    }

    if ($type_report == 'custom_graph') {
        $graphs = db_get_all_rows_field_filter('tgraph', 'id_graph', $content['id_gs']);
        $id_graph = $content['id_gs'];
    } else if ($type_report == 'automatic_graph') {
        $graphs[0]['stacked'] = '';
        $graphs[0]['summatory_series'] = '';
        $graphs[0]['average_series'] = '';
        $graphs[0]['modules_series'] = '';
        $graphs[0]['fullscale'] = $content['style']['fullscale'];
        $modules = $content['id_agent_module'];

        if (is_metaconsole()) {
            $module_source = db_get_all_rows_sql(
                'SELECT id_agent_module, id_server
            FROM tgraph_source
            WHERE id_graph = '.$content['id_gs']
            );

            if (isset($module_source) && is_array($module_source)) {
                $modules = [];
                foreach ($module_source as $key => $value) {
                    $modules[$key]['module'] = $value['id_agent_module'];
                    $modules[$key]['server'] = $value['id_server'];
                }
            }
        }

        $id_graph = 0;
    } else {
        $content['name'] = __('Simple graph');
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );

    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $return['title'] = $content['name'];
    $return['subtitle'] = io_safe_output($graph['name']);
    $return['agent_name'] = $agent_alias;
    $return['module_name'] = $module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text(
        $report,
        $content
    );

    $return['chart'] = '';

    $width = null;

    switch ($type) {
        case 'dinamic':
        case 'static':
        case 'data':
            $params = [
                'period'     => $content['period'],
                'width'      => $width,
                'date'       => $report['datetime'],
                'only_image' => $pdf,
                'homeurl'    => ui_get_full_url(false, false, false, false),
                'ttl'        => $ttl,
                'percentil'  => $graphs[0]['percentil'],
                'fullscale'  => $graphs[0]['fullscale'],
                'server_id'  => $id_meta,
                'height'     => $config['graph_image_height'],
            ];

            $params_combined = [
                'stacked'        => $graphs[0]['stacked'],
                'summatory'      => $graphs[0]['summatory_series'],
                'average'        => $graphs[0]['average_series'],
                'modules_series' => $graphs[0]['modules_series'],
                'id_graph'       => $id_graph,
                'type_report'    => $type_report,
                'labels'         => $content['style']['label'],
            ];

            $return['chart'] = graphic_combined_module(
                $modules,
                $params,
                $params_combined
            );

        break;
    }

    if ($type_report == 'custom_graph') {
        if (is_metaconsole()) {
            metaconsole_restore_db();
        }
    }

    return reporting_check_structure_content($return);
}


/**
 * Simple graph report.
 *
 * @param array   $report             Info report.
 * @param array   $content            Content report.
 * @param string  $type               Type report.
 * @param integer $force_width_chart  Width chart.
 * @param integer $force_height_chart Height chart.
 *
 * @return array
 */
function reporting_simple_graph(
    $report,
    $content,
    $type='dinamic',
    $force_width_chart=null,
    $force_height_chart=null
) {
    global $config;

    $return = [];
    $return['type'] = 'simple_graph';

    if (empty($content['name'])) {
        $content['name'] = __('Simple graph');
    }

    if ($config['metaconsole']) {
        $id_meta = metaconsole_get_id_server($content['server_name']);

        $server = metaconsole_get_connection_by_id($id_meta);
        metaconsole_connect($server);
    }

    $id_agent = agents_get_module_id(
        $content['id_agent_module']
    );
    $id_agent_module = $content['id_agent_module'];
    $agent_description = agents_get_description($id_agent);
    $agent_group = agents_get_agent_group($id_agent);
    $agent_address = agents_get_address($id_agent);
    $agent_alias = agents_get_alias($id_agent);
    $module_name = modules_get_agentmodule_name(
        $id_agent_module
    );

    $module_description = modules_get_agentmodule_descripcion(
        $id_agent_module
    );

    $items_label = [
        'type'               => $return['type'],
        'id_agent'           => $id_agent,
        'id_agent_module'    => $id_agent_module,
        'agent_description'  => $agent_description,
        'agent_group'        => $agent_group,
        'agent_address'      => $agent_address,
        'agent_alias'        => $agent_alias,
        'module_name'        => $module_name,
        'module_description' => $module_description,
    ];

    $label = (isset($content['style']['label'])) ? $content['style']['label'] : '';
    if ($label != '') {
        $label = reporting_label_macro(
            $items_label,
            $label
        );
    }

    $return['title'] = $content['name'];
    $return['subtitle'] = $agent_alias.' - '.$module_name;
    $return['agent_name'] = $agent_alias;
    $return['module_name'] = $module_name;
    $return['description'] = $content['description'];
    $return['date'] = reporting_get_date_text(
        $report,
        $content
    );

    if (isset($content['style']['fullscale'])) {
        $fullscale = (bool) $content['style']['fullscale'];
    }

    $return['chart'] = '';

    // Get chart.
    reporting_set_conf_charts(
        $width,
        $height,
        $only_image,
        $type,
        $content,
        $ttl
    );

    if (!empty($force_width_chart)) {
        $width = $force_width_chart;
    }

    if (!empty($force_height_chart)) {
        $height = $force_height_chart;
    }

    switch ($type) {
        case 'dinamic':
        case 'static':
            // HACK it is saved in show_graph field.
            $time_compare_overlapped = false;
            if ($content['show_graph']) {
                $time_compare_overlapped = 'overlapped';
            }

            $params = [
                'agent_module_id' => $content['id_agent_module'],
                'period'          => $content['period'],
                'title'           => $label,
                'pure'            => false,
                'date'            => $report['datetime'],
                'only_image'      => $only_image,
                'homeurl'         => ui_get_full_url(
                    false,
                    false,
                    false,
                    false
                ),
                'ttl'             => $ttl,
                'compare'         => $time_compare_overlapped,
                'show_unknown'    => true,
                'percentil'       => ($content['style']['percentil'] == 1) ? $config['percentil'] : null,
                'fullscale'       => $fullscale,
                'server_id'       => $id_meta,
                'height'          => $config['graph_image_height'],
            ];

            $return['chart'] = grafico_modulo_sparse($params);
        break;

        case 'data':
            $data = modules_get_agentmodule_data(
                $content['id_agent_module'],
                $content['period'],
                $report['datetime']
            );
            $return['chart'] = [];
            foreach ($data as $d) {
                $return['chart'][$d['utimestamp']] = $d['data'];
            }
        break;

        default:
            // Not Possible.
        break;
    }

    if ($config['metaconsole']) {
        metaconsole_restore_db();
    }

    return reporting_check_structure_content($return);
}


function reporting_get_date_text($report=null, $content=null)
{
    global $config;

    $return = [];
    $return['date'] = null;
    $return['period'] = null;
    $return['from'] = null;
    $return['to'] = null;

    if (!empty($report) && !empty($content)) {
        if ($content['period'] == 0) {
            $es = json_decode($content['external_source'], true);
            if ($es['date'] == 0) {
                $return['period'] = 0;
            } else {
                $return['date'] = $es['date'];
            }
        } else {
            $return['period'] = $content['period'];
            $return['from'] = ($report['datetime'] - $content['period']);
            $return['to'] = $report['datetime'];
        }
    }

    return $return;
}


/**
 * Check the common items exits
 */
function reporting_check_structure_report($return)
{
    if (!isset($return['group_name'])) {
        $return['group_name'] = '';
    }

    if (!isset($return['title'])) {
        $return['title'] = '';
    }

    if (!isset($return['datetime'])) {
        $return['datetime'] = '';
    }

    if (!isset($return['period'])) {
        $return['period'] = '';
    }

    return $return;
}


/**
 * Check the common items exits
 */
function reporting_check_structure_content($report)
{
    if (!isset($report['title'])) {
        $report['title'] = '';
    }

    if (!isset($report['subtitle'])) {
        $report['subtitle'] = '';
    }

    if (!isset($report['description'])) {
        $report['description'] = '';
    }

    if (!isset($report['date'])) {
        $report['date']['date'] = '';
        $report['date']['period'] = '';
        $report['date']['from'] = '';
        $report['date']['to'] = '';
    }

    if (!isset($report['fields'])) {
        $return['fields']['total_time'] = '';
        $return['fields']['time_failed'] = '';
        $return['fields']['time_in_ok_status'] = '';
        $return['fields']['time_in_unknown_status'] = '';
        $return['fields']['time_of_not_initialized_module'] = '';
        $return['fields']['time_of_downtime'] = '';
        $return['fields']['total_checks'] = '';
        $return['fields']['checks_failed'] = '';
        $return['fields']['checks_in_ok_status'] = '';
        $return['fields']['unknown_checks'] = '';
        $return['fields']['agent_max_value'] = '';
        $return['fields']['agent_min_value'] = '';
    }

    return $report;
}


function reporting_set_conf_charts(
    &$width,
    &$height,
    &$only_image,
    $type,
    $content,
    &$ttl
) {
    switch ($type) {
        case 'dinamic':
            $only_image = false;
            $width = 900;
            $height = isset($content['style']['dyn_height']) ? $content['style']['dyn_height'] : 230;
            $ttl = 1;
        break;

        case 'static':
            $ttl = 2;
            $only_image = true;
            if ($content['style']['show_in_landscape']) {
                $height = 1100;
                $width = 1700;
            } else {
                $height = 360;
                $width = 780;
            }
        break;

        case 'data':
        break;
    }
}


//
//
//
// MAYBE MOVE THE NEXT FUNCTIONS TO A FILE NAMED AS FUNCTION_REPORTING.UTILS.PHP //
//
//
//


/**
 * Get a detailed report of summarized events per agent
 *
 * It construct a table object with all the grouped events happened in an agent
 * during a period of time.
 *
 * @param mixed Module id to get the report from.
 * @param int Period of time (in seconds) to get the report.
 * @param int Beginning date (unixtime) of the report
 * @param bool Flag to return or echo the report table (echo by default).
 * @param bool Flag to return the html or table object, by default html.
 *
 * @return mixed A table object (XHTML) or object table is false the html.
 */
function reporting_get_module_detailed_event(
    $id_modules,
    $period=0,
    $date=0,
    $show_summary_group=false,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false,
    $force_width_chart=false,
    $event_graph_by_user_validator=false,
    $event_graph_by_criticity=false,
    $event_graph_validated_vs_unvalidated=false,
    $ttl=1,
    $id_server=false,
    $metaconsole_dbtable=false
) {
    global $config;

    $id_modules = (array) safe_int($id_modules, 1);

    if (!is_numeric($date)) {
        $date = strtotime($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $history = false;
    if ($config['history_event_enabled']) {
        $history = true;
    }

    $events = [];

    foreach ($id_modules as $id_module) {
        $event['data'] = events_get_agent(
            false,
            (int) $period,
            (int) $date,
            $history,
            $show_summary_group,
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            false,
            false,
            $id_module,
            true,
            $id_server
        );

        // total_events
        if (isset($event['data'])) {
            $event['total_events'] = count($event['data']);
        } else {
            $event['total_events'] = 0;
        }

        // graphs
        if (!empty($force_width_chart)) {
            $width = $force_width_chart;
        }

        if (!empty($force_height_chart)) {
            $height = $force_height_chart;
        }

        if ($event_graph_by_user_validator) {
            $data_graph = events_get_count_events_validated_by_user(
                ['id_agentmodule' => $id_module],
                $period,
                $date,
                $filter_event_severity,
                $filter_event_type,
                $filter_event_status,
                $filter_event_filter_search,
                $metaconsole_dbtable
            );

            $event['chart']['by_user_validator'] = pie_graph(
                $data_graph,
                500,
                150,
                __('other'),
                ui_get_full_url(false, false, false, false),
                ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                $config['fontpath'],
                $config['font_size'],
                $ttl
            );
        }

        if ($event_graph_by_criticity) {
            $data_graph = events_get_count_events_by_criticity(
                ['id_agentmodule' => $id_module],
                $period,
                $date,
                $filter_event_severity,
                $filter_event_type,
                $filter_event_status,
                $filter_event_filter_search,
                $metaconsole_dbtable
            );

            $colors = get_criticity_pie_colors($data_graph);

            $event['chart']['by_criticity'] = pie_graph(
                $data_graph,
                500,
                150,
                __('other'),
                ui_get_full_url(false, false, false, false),
                ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                $config['fontpath'],
                $config['font_size'],
                $ttl,
                false,
                $colors
            );
        }

        if ($event_graph_validated_vs_unvalidated) {
            $data_graph = events_get_count_events_validated(
                ['id_agentmodule' => $id_module],
                $period,
                $date,
                $filter_event_severity,
                $filter_event_type,
                $filter_event_status,
                $filter_event_filter_search,
                $metaconsole_dbtable
            );

            $event['chart']['validated_vs_unvalidated'] = pie_graph(
                $data_graph,
                500,
                150,
                __('other'),
                ui_get_full_url(false, false, false, false),
                ui_get_full_url(false, false, false, false).'/images/logo_vertical_water.png',
                $config['fontpath'],
                $config['font_size'],
                $ttl
            );
        }

        if (!empty($event)) {
            array_push($events, $event);
        }
    }

    return $events;
}


/**
 * Get a detailed report of summarized events per agent
 *
 * It construct a table object with all the grouped events happened in an agent
 * during a period of time.
 *
 * @param mixed Agent id(s) to get the report from.
 * @param int Period of time (in seconds) to get the report.
 * @param int Beginning date (unixtime) of the report
 * @param bool Flag to return or echo the report table (echo by default).
 *
 * @return A table object (XHTML)
 */
function reporting_get_agents_detailed_event(
    $id_agents,
    $period=0,
    $date=0,
    $return=false,
    $only_data=false,
    $history=false,
    $show_summary_group=false,
    $filter_event_severity=false,
    $filter_event_type=false,
    $filter_event_status=false,
    $filter_event_filter_search=false
) {
    global $config;

    if ($only_data) {
        $return_data = [];
    }

    $id_agents = (array) safe_int($id_agents, 1);

    if (!is_numeric($date)) {
        $date = strtotime($date);
    }

    if (empty($date)) {
        $date = get_system_time();
    }

    $events = [];

    foreach ($id_agents as $id_agent) {
        $event = events_get_agent(
            $id_agent,
            (int) $period,
            (int) $date,
            $history,
            $show_summary_group,
            $filter_event_severity,
            $filter_event_type,
            $filter_event_status,
            $filter_event_filter_search,
            false,
            false
        );

        if (empty($event)) {
            $event = [];
        }

        if ($only_data) {
            $nevents = count($event);
            for ($i = ($nevents - 1); $i >= 0; $i--) {
                $e = $event[$i];
                if ($show_summary_group) {
                    $return_data[] = [
                        'status'       => $e['estado'],
                        'count'        => $e['event_rep'],
                        'name'         => $e['evento'],
                        'type'         => $e['event_type'],
                        'criticity'    => $e['criticity'],
                        'validated_by' => $e['id_usuario'],
                        'timestamp'    => $e['timestamp_rep'],
                        'id_evento'    => $e['id_evento'],
                    ];
                } else {
                    $return_data[] = [
                        'status'       => $e['estado'],
                        'name'         => $e['evento'],
                        'type'         => $e['event_type'],
                        'criticity'    => $e['criticity'],
                        'validated_by' => $e['id_usuario'],
                        'timestamp'    => $e['timestamp'],
                        'id_evento'    => $e['id_evento'],
                    ];
                }
            }
        } else {
            if (!empty($event)) {
                array_push($events, $event);
            }
        }
    }

    if ($only_data) {
        return $return_data;
    }

    if ($events) {
        $note = '';
        if (count($events) >= 1000) {
            $note .= '* '.__('Maximum of events shown').' (1000)<br>';
        }

        foreach ($events as $eventRow) {
            foreach ($eventRow as $k => $event) {
                // First pass along the class of this row.
                $table->cellclass[$k][1] = $table->cellclass[$k][2] = $table->cellclass[$k][4] = $table->cellclass[$k][5] = $table->cellclass[$k][6] = get_priority_class($event['criticity']);

                $data = [];
                // Colored box.
                switch ($event['estado']) {
                    case 0:
                        $img_st = 'images/star.png';
                        $title_st = __('New event');
                    break;

                    case 1:
                        $img_st = 'images/tick.png';
                        $title_st = __('Event validated');
                    break;

                    case 2:
                        $img_st = 'images/hourglass.png';
                        $title_st = __('Event in process');
                    break;
                }

                $data[] = html_print_image(
                    $img_st,
                    true,
                    [
                        'class' => 'image_status',
                        'width' => 16,
                        'title' => $title_st,
                    ]
                );

                $data[] = $event['event_rep'];

                $data[] = ui_print_truncate_text(
                    io_safe_output($event['evento']),
                    140,
                    false,
                    true
                );
                // $data[] = $event['event_type'];
                $data[] = events_print_type_img($event['event_type'], true);

                $data[] = get_priority_name($event['criticity']);
                if (empty($event['id_usuario']) && $event['estado'] == EVENT_VALIDATE) {
                    $data[] = '<i>'.__('System').'</i>';
                } else {
                    $user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['id_usuario']);
                    $data[] = io_safe_output($user_name);
                }

                $data[] = '<font style="font-size: 6pt;">'.date($config['date_format'], $event['timestamp_rep']).'</font>';
                array_push($table->data, $data);
            }
        }
    }

    if ($events) {
        return html_print_table($table, $return).$note;
    }
}


/**
 * Get general statistical info on a group
 *
 * @param int Group Id to get info from. 0 = all
 *
 * @return array Group statistics
 */
function reporting_get_group_stats($id_group=0, $access='AR')
{
    global $config;

    $data = [];
    $data['monitor_checks'] = 0;
    $data['monitor_not_init'] = 0;
    $data['monitor_unknown'] = 0;
    $data['monitor_ok'] = 0;
    $data['monitor_bad'] = 0;
    // Critical + Unknown + Warning
    $data['monitor_warning'] = 0;
    $data['monitor_critical'] = 0;
    $data['monitor_not_normal'] = 0;
    $data['monitor_alerts'] = 0;
    $data['monitor_alerts_fired'] = 0;
    $data['monitor_alerts_fire_count'] = 0;
    $data['total_agents'] = 0;
    $data['total_alerts'] = 0;
    $data['total_checks'] = 0;
    $data['alerts'] = 0;
    $data['agents_unknown'] = 0;
    $data['monitor_health'] = 100;
    $data['alert_level'] = 100;
    $data['module_sanity'] = 100;
    $data['server_sanity'] = 100;
    $data['total_not_init'] = 0;
    $data['monitor_non_init'] = 0;
    $data['agent_ok'] = 0;
    $data['agent_warning'] = 0;
    $data['agent_critical'] = 0;
    $data['agent_unknown'] = 0;
    $data['agent_not_init'] = 0;

    $cur_time = get_system_time();

    // Check for access credentials using check_acl. More overhead, much safer
    if (!check_acl($config['id_user'], $id_group, $access)) {
        return $data;
    }

    if ($id_group == 0) {
        $id_group = array_keys(
            users_get_groups($config['id_user'], $access, false)
        );
    }

    // -----------------------------------------------------------------
    // Server processed stats. NOT realtime (taken from tgroup_stat)
    // -----------------------------------------------------------------
    if ($config['realtimestats'] == 0) {
        if (!is_array($id_group)) {
            $my_group = $id_group;
            $id_group = [];
            $id_group[0] = $my_group;
        }

        foreach ($id_group as $group) {
            $group_stat = db_get_all_rows_sql(
                "SELECT *
                FROM tgroup_stat, tgrupo
                WHERE tgrupo.id_grupo = tgroup_stat.id_group
                    AND tgroup_stat.id_group = $group
                ORDER BY nombre"
            );

            $data['monitor_checks'] += $group_stat[0]['modules'];
            $data['agent_not_init'] += $group_stat[0]['non-init'];
            $data['agent_unknown'] += $group_stat[0]['unknown'];
            $data['agent_ok'] += $group_stat[0]['normal'];
            $data['agent_warning'] += $group_stat[0]['warning'];
            $data['agent_critical'] += $group_stat[0]['critical'];
            $data['monitor_alerts'] += $group_stat[0]['alerts'];
            $data['monitor_alerts_fired'] += $group_stat[0]['alerts_fired'];
            $data['monitor_alerts_fire_count'] += $group_stat[0]['alerts_fired'];
            $data['total_checks'] += $group_stat[0]['modules'];
            $data['total_alerts'] += $group_stat[0]['alerts'];
            $data['total_agents'] += $group_stat[0]['agents'];
            $data['agents_unknown'] += $group_stat[0]['agents_unknown'];
            $data['utimestamp'] = $group_stat[0]['utimestamp'];

            // This fields are not in database
            $data['monitor_ok'] += (int) groups_get_normal_monitors($group);
            $data['monitor_warning'] += (int) groups_get_warning_monitors($group);
            $data['monitor_critical'] += (int) groups_get_critical_monitors($group);
            $data['monitor_unknown'] += (int) groups_get_unknown_monitors($group);
            $data['monitor_not_init'] += (int) groups_get_not_init_monitors($group);
        }

        // -------------------------------------------------------------------
        // Realtime stats, done by PHP Console
        // -------------------------------------------------------------------
    } else {
        if (!is_array($id_group)) {
            $my_group = $id_group;
            $id_group = [];
            $id_group[0] = $my_group;
        }

        // Store the groups where we are quering
        $covered_groups = [];
        $group_array = [];
        foreach ($id_group as $group) {
            $children = groups_get_childrens($group);

            // Show empty groups only if they have children with agents
            // $group_array = array();
            foreach ($children as $sub) {
                // If the group is quering previously, we ingore it
                if (!in_array($sub['id_grupo'], $covered_groups)) {
                    array_push($covered_groups, $sub['id_grupo']);
                    array_push($group_array, $sub['id_grupo']);
                }
            }

            // Add id of this group to create the clause
            // If the group is quering previously, we ingore it
            if (!in_array($group, $covered_groups)) {
                array_push($covered_groups, $group);
                array_push($group_array, $group);
            }

            // If there are not groups to query, we jump to nextone
            if (empty($group_array)) {
                continue;
            }
        }

        if (!empty($group_array)) {
            $monitors_info = groups_monitor_total_counters($group_array, true);
            // Get monitor NOT INIT, except disabled AND async modules
            $data['monitor_not_init'] += $monitors_info['not_init'];
            $data['total_not_init'] += $data['monitor_not_init'];
            // Get monitor OK, except disabled and non-init
            $data['monitor_ok'] += $monitors_info['ok'];
            // Get monitor CRITICAL, except disabled and non-init
            $data['monitor_critical'] += $monitors_info['critical'];
            // Get monitor WARNING, except disabled and non-init
            $data['monitor_warning'] += $monitors_info['warning'];
            // Get monitor UNKNOWN, except disabled and non-init
            $data['monitor_unknown'] += $monitors_info['unknown'];
            $data['monitor_checks'] += $monitors_info['total'];

            // Get alerts configured, except disabled
            $alerts_info = groups_monitor_alerts_total_counters($group_array);
            $data['monitor_alerts'] += $alerts_info['total'];
            // Get alert configured currently FIRED, except disabled
            $data['monitor_alerts_fired'] += $alerts_info['fired'];

            $agents_info = groups_agents_total_counters($group_array);
            // Get TOTAL agents in a group
            $data['total_agents'] += $agents_info['total'];
            // Get Agents OK
            $data['agent_ok'] += $agents_info['ok'];
            // Get Agents Warning
            $data['agent_warning'] += $agents_info['warning'];
            // Get Agents Critical
            $data['agent_critical'] += $agents_info['critical'];
            // Get Agents Unknown
            $data['agent_unknown'] += $agents_info['unknown'];
            // Get Agents Not init
            $data['agent_not_init'] += $agents_info['not_init'];
        }
    }

    // Calculate not_normal monitors
    $data['monitor_not_normal'] += ($data['monitor_checks'] - $data['monitor_ok']);

    if ($data['monitor_unknown'] > 0 && $data['monitor_checks'] > 0) {
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

    $data['alert_fired'] = 0;
    if ($data['monitor_alerts_fired'] > 0) {
        $data['alert_fired'] = 1;
    }

    if ($data['monitor_critical'] > 0) {
        $data['status'] = 'critical';
    } else if ($data['monitor_warning'] > 0) {
        $data['status'] = 'warning';
    } else if (($data['monitor_unknown'] > 0) || ($data['agents_unknown'] > 0)) {
        $data['status'] = 'unknown';
    } else if ($data['monitor_ok'] > 0) {
        $data['status'] = 'ok';
    } else if ($data['agent_not_init'] > 0) {
        $data['status'] = 'not_init';
    } else {
        $data['status'] = 'none';
    }

    return ($data);
}


/**
 * Get general statistical info on a group
 *
 * @param int Group Id to get info from. 0 = all
 *
 * @return array Group statistics
 */
function reporting_get_group_stats_resume($id_group=0, $access='AR')
{
    global $config;

    $data = [];
    $data['monitor_checks'] = 0;
    $data['monitor_not_init'] = 0;
    $data['monitor_unknown'] = 0;
    $data['monitor_ok'] = 0;
    $data['monitor_bad'] = 0;
    // Critical + Unknown + Warning
    $data['monitor_warning'] = 0;
    $data['monitor_critical'] = 0;
    $data['monitor_not_normal'] = 0;
    $data['monitor_alerts'] = 0;
    $data['monitor_alerts_fired'] = 0;
    $data['monitor_alerts_fire_count'] = 0;
    $data['total_agents'] = 0;
    $data['total_alerts'] = 0;
    $data['total_checks'] = 0;
    $data['alerts'] = 0;
    $data['agents_unknown'] = 0;
    $data['monitor_health'] = 100;
    $data['alert_level'] = 100;
    $data['module_sanity'] = 100;
    $data['server_sanity'] = 100;
    $data['total_not_init'] = 0;
    $data['monitor_non_init'] = 0;
    $data['agent_ok'] = 0;
    $data['agent_warning'] = 0;
    $data['agent_critical'] = 0;
    $data['agent_unknown'] = 0;
    $data['agent_not_init'] = 0;

    $cur_time = get_system_time();

    // Check for access credentials using check_acl. More overhead, much safer
    if (!check_acl($config['id_user'], $id_group, $access)) {
        return $data;
    }

    if ($id_group == 0) {
        $id_group = array_keys(
            users_get_groups($config['id_user'], $access, false)
        );
    }

    // -----------------------------------------------------------------
    // Server processed stats. NOT realtime (taken from tgroup_stat)
    // -----------------------------------------------------------------
    if ($config['realtimestats'] == 0) {
        if (!is_array($id_group)) {
            $my_group = $id_group;
            $id_group = [];
            $id_group[0] = $my_group;
        }

        foreach ($id_group as $group) {
            $group_stat = db_get_all_rows_sql(
                "SELECT *
                FROM tgroup_stat, tgrupo
                WHERE tgrupo.id_grupo = tgroup_stat.id_group
                    AND tgroup_stat.id_group = $group
                ORDER BY nombre"
            );

            $data['monitor_checks'] += $group_stat[0]['modules'];
            $data['agent_not_init'] += $group_stat[0]['non-init'];
            $data['agent_unknown'] += $group_stat[0]['unknown'];
            $data['agent_ok'] += $group_stat[0]['normal'];
            $data['agent_warning'] += $group_stat[0]['warning'];
            $data['agent_critical'] += $group_stat[0]['critical'];
            $data['monitor_alerts'] += $group_stat[0]['alerts'];
            $data['monitor_alerts_fired'] += $group_stat[0]['alerts_fired'];
            $data['monitor_alerts_fire_count'] += $group_stat[0]['alerts_fired'];
            $data['total_checks'] += $group_stat[0]['modules'];
            $data['total_alerts'] += $group_stat[0]['alerts'];
            $data['total_agents'] += $group_stat[0]['agents'];
            $data['agents_unknown'] += $group_stat[0]['agents_unknown'];
            $data['utimestamp'] = $group_stat[0]['utimestamp'];

            // This fields are not in database
            $data['monitor_ok'] += (int) groups_get_normal_monitors($group);
            $data['monitor_warning'] += (int) groups_get_warning_monitors($group);
            $data['monitor_critical'] += (int) groups_get_critical_monitors($group);
            $data['monitor_unknown'] += (int) groups_get_unknown_monitors($group);
            $data['monitor_not_init'] += (int) groups_get_not_init_monitors($group);
        }

        // -------------------------------------------------------------------
        // Realtime stats, done by PHP Console
        // -------------------------------------------------------------------
    } else {
        if (!empty($id_group)) {
            // check tags for user
            $tags = db_get_value('tags', 'tusuario_perfil', 'id_usuario', $config['id_user']);
            if ($tags) {
                $tags_sql = " AND tae.id_agente_modulo IN ( SELECT id_agente_modulo 
                                                           FROM ttag_module 
                                                           WHERE id_tag IN ($tags) ) ";
            } else {
                $tags_sql = '';
            }

            if (is_array($id_group)) {
                $id_group = implode(',', $id_group);
            }

            // for stats modules
            $sql = "SELECT tg.id_grupo as id, tg.nombre as name, 
                    SUM(tae.estado=0) as monitor_ok,
                    SUM(tae.estado=1) as monitor_critical,
                    SUM(tae.estado=2) as monitor_warning,
                    SUM(tae.estado=3) as monitor_unknown,
                    SUM(tae.estado=4) as monitor_not_init,
                    COUNT(tae.estado) as monitor_total

                    FROM
                        tagente_estado tae,
                        tagente        ta,
                        tagente_modulo tam,
                        tgrupo         tg
    
                    WHERE 1=1
                        AND tae.id_agente = ta.id_agente
                        AND tae.id_agente_modulo = tam.id_agente_modulo
                        AND ta.id_grupo = tg.id_grupo
                        AND tam.disabled = 0
                        AND ta.disabled = 0
                        AND ta.id_grupo IN ($id_group) $tags_sql 
                    GROUP BY tg.id_grupo;";
            $data_array = db_get_all_rows_sql($sql);

            $data = $data_array[0];

            // Get alerts configured, except disabled
            $data['monitor_alerts'] += groups_monitor_alerts($group_array);

            // Get alert configured currently FIRED, except disabled
            $data['monitor_alerts_fired'] += groups_monitor_fired_alerts($group_array);

            // for stats agents
            $sql = "SELECT tae.id_agente id_agente, tg.id_grupo id_grupo,
                    SUM(tae.estado=0) as monitor_agent_ok,
                    SUM(tae.estado=1) as monitor_agent_critical,
                    SUM(tae.estado=2) as monitor_agent_warning,
                    SUM(tae.estado=3) as monitor_agent_unknown,
                    SUM(tae.estado=4) as monitor_agent_not_init,
                    COUNT(tae.estado) as monitor_agent_total

                FROM
                    tagente_estado tae,
                    tagente        ta,
                    tagente_modulo tam,
                    tgrupo         tg
                    
                WHERE 1=1
                    AND tae.id_agente = ta.id_agente
                    AND tae.id_agente_modulo = tam.id_agente_modulo
                    AND ta.id_grupo = tg.id_grupo
                    AND tam.disabled = 0
                    AND ta.disabled = 0
                    AND ta.id_grupo IN ($id_group) $tags_sql
                GROUP BY tae.id_agente;";
            $data_array_2 = db_get_all_rows_sql($sql);

            if (is_array($data_array_2) || is_object($data_array_2)) {
                foreach ($data_array_2 as $key => $value) {
                    if ($value['monitor_agent_critical'] != 0) {
                        $data['agent_critical'] ++;
                    } else if ($value['monitor_agent_critical'] == 0 && $value['monitor_agent_warning'] != 0) {
                        $data['agent_warning'] ++;
                    } else if ($value['monitor_agent_critical'] == 0 && $value['monitor_agent_warning'] == 0
                        && $value['monitor_agent_unknown'] != 0
                    ) {
                        $data['agent_unknown'] ++;
                    } else if ($value['monitor_agent_critical'] == 0 && $value['monitor_agent_warning'] == 0
                        && $value['monitor_agent_unknown'] == 0 && $value['monitor_agent_ok'] != 0
                    ) {
                        $data['agent_ok'] ++;
                    } else if ($value['monitor_agent_critical'] == 0 && $value['monitor_agent_warning'] == 0
                        && $value['monitor_agent_unknown'] == 0 && $value['monitor_agent_ok'] == 0
                        && $value['monitor_agent_not_init'] != 0
                    ) {
                        $data['agent_not_init'] ++;
                    }

                    $data['total_agents'] ++;
                }
            }

            // Get total count of monitors for this group, except disabled.
            $data['monitor_checks'] = ($data['monitor_not_init'] + $data['monitor_unknown'] + $data['monitor_warning'] + $data['monitor_critical'] + $data['monitor_ok']);

            // Calculate not_normal monitors
            $data['monitor_not_normal'] += ($data['monitor_checks'] - $data['monitor_ok']);
        }

        // Get total count of monitors for this group, except disabled.
        $data['monitor_checks'] = ($data['monitor_not_init'] + $data['monitor_unknown'] + $data['monitor_warning'] + $data['monitor_critical'] + $data['monitor_ok']);
    }

    if ($data['monitor_unknown'] > 0 && $data['monitor_checks'] > 0) {
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

    $data['alert_fired'] = 0;
    if ($data['monitor_alerts_fired'] > 0) {
        $data['alert_fired'] = 1;
    }

    if ($data['monitor_critical'] > 0) {
        $data['status'] = 'critical';
    } else if ($data['monitor_warning'] > 0) {
        $data['status'] = 'warning';
    } else if (($data['monitor_unknown'] > 0) || ($data['agents_unknown'] > 0)) {
        $data['status'] = 'unknown';
    } else if ($data['monitor_ok'] > 0) {
        $data['status'] = 'ok';
    } else if ($data['agent_not_init'] > 0) {
        $data['status'] = 'not_init';
    } else {
        $data['status'] = 'none';
    }

    return ($data);
}


function reporting_get_stats_indicators($data, $width=280, $height=20, $html=true)
{
    $table_ind = html_get_predefined_table();

    $servers = [];
    $servers['all'] = (int) db_get_value('COUNT(id_server)', 'tserver');
    $servers['up'] = (int) servers_check_status();
    $servers['down'] = ($servers['all'] - $servers['up']);
    if ($servers['all'] == 0) {
        $servers['health'] = 0;
    } else {
        $servers['health'] = ($servers['up'] / ($servers['all'] / 100));
    }

    if ($html) {
        $tdata[0] = '<fieldset class="databox tactical_set">
                        <legend>'.__('Server health').ui_print_help_tip(sprintf(__('%d Downed servers'), $servers['down']), true).'</legend>'.progress_bar($servers['health'], $width, $height, '', 0).'</fieldset>';
        $table_ind->rowclass[] = '';
        $table_ind->data[] = $tdata;

        $tdata[0] = '<fieldset class="databox tactical_set">
                        <legend>'.__('Monitor health').ui_print_help_tip(sprintf(__('%d Not Normal monitors'), $data['monitor_not_normal']), true).'</legend>'.progress_bar($data['monitor_health'], $width, $height, $data['monitor_health'].'% '.__('of monitors up'), 0).'</fieldset>';
        $table_ind->rowclass[] = '';
        $table_ind->data[] = $tdata;

        $tdata[0] = '<fieldset class="databox tactical_set">
                        <legend>'.__('Module sanity').ui_print_help_tip(sprintf(__('%d Not inited monitors'), $data['monitor_not_init']), true).'</legend>'.progress_bar($data['module_sanity'], $width, $height, $data['module_sanity'].'% '.__('of total modules inited'), 0).'</fieldset>';
        $table_ind->rowclass[] = '';
        $table_ind->data[] = $tdata;

        $tdata[0] = '<fieldset class="databox tactical_set">
                        <legend>'.__('Alert level').ui_print_help_tip(sprintf(__('%d Fired alerts'), $data['monitor_alerts_fired']), true).'</legend>'.progress_bar($data['alert_level'], $width, $height, $data['alert_level'].'% '.__('of defined alerts not fired'), 0).'</fieldset>';
        $table_ind->rowclass[] = '';
        $table_ind->data[] = $tdata;

        return html_print_table($table_ind, true);
    } else {
        $return = [];

        $return['server_health'] = [
            'title' => __('Server health'),
            'graph' => progress_bar($servers['health'], $width, $height, '', 0),
        ];
        $return['monitor_health'] = [
            'title' => __('Monitor health'),
            'graph' => progress_bar($data['monitor_health'], $width, $height, $data['monitor_health'].'% '.__('of monitors up'), 0),
        ];
        $return['module_sanity'] = [
            'title' => __('Module sanity'),
            'graph' => progress_bar($data['module_sanity'], $width, $height, $data['module_sanity'].'% '.__('of total modules inited'), 0),
        ];
        $return['alert_level'] = [
            'title' => __('Alert level'),
            'graph' => progress_bar($data['alert_level'], $width, $height, $data['alert_level'].'% '.__('of defined alerts not fired'), 0),
        ];

        return $return;
    }
}


function reporting_get_stats_alerts($data, $links=false)
{
    global $config;

    // Link URLS
    $mobile = false;
    if (isset($data['mobile'])) {
        if ($data['mobile']) {
            $mobile = true;
        }
    }

    if ($mobile) {
        $urls = [];
        $urls['monitor_alerts'] = 'index.php?page=alerts&status=all_enabled';
        $urls['monitor_alerts_fired'] = 'index.php?page=alerts&status=fired';
    } else {
        $urls = [];
        if ($links) {
            $urls['monitor_alerts'] = 'index.php?sec=estado&sec2=operation/agentes/alerts_status&pure='.$config['pure'];
            $urls['monitor_alerts_fired'] = 'index.php?sec=estado&sec2=operation/agentes/alerts_status&filter=fired&pure='.$config['pure'];
        } else {
            $urls['monitor_alerts'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60';
            $urls['monitor_alerts_fired'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&filter=fired';
        }
    }

    // Alerts table
    $table_al = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/bell.png', true, ['title' => __('Defined alerts')], false, false, false, true);
    $tdata[1] = $data['monitor_alerts'] <= 0 ? '-' : $data['monitor_alerts'];
    $tdata[1] = '<a class="big_data" href="'.$urls['monitor_alerts'].'">'.$tdata[1].'</a>';

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */

    if ($data['monitor_alerts'] > $data['total_agents'] && !enterprise_installed()) {
        $tdata[2] = "<div id='alertagentmodal' class='publienterprise' title='Community version' style=''><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
    }

    $tdata[3] = html_print_image('images/bell_error.png', true, ['title' => __('Fired alerts')], false, false, false, true);
    $tdata[4] = $data['monitor_alerts_fired'] <= 0 ? '-' : $data['monitor_alerts_fired'];
    $tdata[4] = '<a style="color: '.COL_ALERTFIRED.';" class="big_data" href="'.$urls['monitor_alerts_fired'].'">'.$tdata[4].'</a>';
    $table_al->rowclass[] = '';
    $table_al->data[] = $tdata;

    if (!is_metaconsole()) {
        $output = '<fieldset class="databox tactical_set">
                    <legend>'.__('Defined and fired alerts').'</legend>'.html_print_table($table_al, true).'</fieldset>';
    } else {
        // Remove the defined alerts cause with the new cache table is difficult to retrieve them
        unset($table_al->data[0][0], $table_al->data[0][1]);

        $table_al->class = 'tactical_view';
        $table_al->style = [];
        $output = '<fieldset class="tactical_set">
                    <legend>'.__('Fired alerts').'</legend>'.html_print_table($table_al, true).'</fieldset>';
    }

    return $output;
}


function reporting_get_stats_modules_status($data, $graph_width=250, $graph_height=150, $links=false, $data_agents=false)
{
    global $config;

    // Link URLS
    if ($links === false) {
        $urls = [];
        $urls['monitor_critical'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_CRITICAL_BAD.'&pure='.$config['pure'];
        $urls['monitor_warning'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_WARNING.'&pure='.$config['pure'];
        $urls['monitor_ok'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_NORMAL.'&pure='.$config['pure'];
        $urls['monitor_unknown'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_UNKNOWN.'&pure='.$config['pure'];
        $urls['monitor_not_init'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_NOT_INIT.'&pure='.$config['pure'];
    } else {
        $urls = [];
        $urls['monitor_critical'] = $links['monitor_critical'];
        $urls['monitor_warning'] = $links['monitor_warning'];
        $urls['monitor_ok'] = $links['monitor_ok'];
        $urls['monitor_unknown'] = $links['monitor_unknown'];
        $urls['monitor_not_init'] = $links['monitor_not_init'];
    }

    // Fixed width non interactive charts
    $status_chart_width = $graph_width;

    // Modules by status table
    $table_mbs = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/module_critical.png', true, ['title' => __('Monitor critical')], false, false, false, true);
    $tdata[1] = $data['monitor_critical'] <= 0 ? '-' : $data['monitor_critical'];
    $tdata[1] = '<a style="color: '.COL_CRITICAL.';" class="big_data" href="'.$urls['monitor_critical'].'">'.$tdata[1].'</a>';

    $tdata[2] = html_print_image('images/module_warning.png', true, ['title' => __('Monitor warning')], false, false, false, true);
    $tdata[3] = $data['monitor_warning'] <= 0 ? '-' : $data['monitor_warning'];
    $tdata[3] = '<a style="color: '.COL_WARNING_DARK.';" class="big_data" href="'.$urls['monitor_warning'].'">'.$tdata[3].'</a>';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image('images/module_ok.png', true, ['title' => __('Monitor normal')], false, false, false, true);
    $tdata[1] = $data['monitor_ok'] <= 0 ? '-' : $data['monitor_ok'];
    $tdata[1] = '<a style="color: '.COL_NORMAL.';" class="big_data" href="'.$urls['monitor_ok'].'">'.$tdata[1].'</a>';

    $tdata[2] = html_print_image('images/module_unknown.png', true, ['title' => __('Monitor unknown')], false, false, false, true);
    $tdata[3] = $data['monitor_unknown'] <= 0 ? '-' : $data['monitor_unknown'];
    $tdata[3] = '<a style="color: '.COL_UNKNOWN.';" class="big_data" href="'.$urls['monitor_unknown'].'">'.$tdata[3].'</a>';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image('images/module_notinit.png', true, ['title' => __('Monitor not init')], false, false, false, true);
    $tdata[1] = $data['monitor_not_init'] <= 0 ? '-' : $data['monitor_not_init'];
    $tdata[1] = '<a style="color: '.COL_NOTINIT.';" class="big_data" href="'.$urls['monitor_not_init'].'">'.$tdata[1].'</a>';

    $tdata[2] = $tdata[3] = '';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    if ($data['monitor_checks'] > 0) {
        $tdata = [];
        $table_mbs->colspan[count($table_mbs->data)][0] = 4;
        $table_mbs->cellstyle[count($table_mbs->data)][0] = 'text-align: center;';
        $tdata[0] = '<div id="outter_status_pie" style="height: '.$graph_height.'px">'.'<div id="status_pie" style="margin: auto; width: '.$status_chart_width.'px;">'.graph_agent_status(false, $graph_width, $graph_height, true, true, $data_agents).'</div></div>';
        $table_mbs->rowclass[] = '';
        $table_mbs->data[] = $tdata;
    }

    if (!is_metaconsole()) {
        $output = '
            <fieldset class="databox tactical_set">
                <legend>'.__('Monitors by status').'</legend>'.html_print_table($table_mbs, true).'</fieldset>';
    } else {
        $table_mbs->class = 'tactical_view';
        $table_mbs->style = [];
        $output = '
            <fieldset class="tactical_set">
                <legend>'.__('Monitors by status').'</legend>'.html_print_table($table_mbs, true).'</fieldset>';
    }

    return $output;
}


function reporting_get_stats_agents_monitors($data)
{
    global $config;

    // Link URLS
    $mobile = false;
    if (isset($data['mobile'])) {
        if ($data['mobile']) {
            $mobile = true;
        }
    }

    if ($mobile) {
        $urls = [];
        $urls['total_agents'] = 'index.php?page=agents';
        $urls['monitor_checks'] = 'index.php?page=modules';
    } else {
        $urls = [];
        $urls['total_agents'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60';
        $urls['monitor_checks'] = $config['homeurl'].'index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1';
    }

    // Agents and modules table
    $table_am = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/agent.png', true, ['title' => __('Total agents')], false, false, false, true);
    $tdata[1] = $data['total_agents'] <= 0 ? '-' : $data['total_agents'];
    $tdata[1] = '<a class="big_data" href="'.$urls['total_agents'].'">'.$tdata[1].'</a>';

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */

    if ($data['total_agents'] > 500 && !enterprise_installed()) {
        $tdata[2] = "<div id='agentsmodal' class='publienterprise' title='Community version' style=''><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
    }

    $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Monitor checks')], false, false, false, true);
    $tdata[4] = $data['monitor_checks'] <= 0 ? '-' : $data['monitor_checks'];
    $tdata[4] = '<a class="big_data" href="'.$urls['monitor_checks'].'">'.$tdata[4].'</a>';

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */
    if ($data['total_agents']) {
        if (($data['monitor_checks'] / $data['total_agents'] > 100) && !enterprise_installed()) {
            $tdata[5] = "<div id='monitorcheckmodal' class='publienterprise' title='Community version' style=''><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
        }
    }

    $table_am->rowclass[] = '';
    $table_am->data[] = $tdata;

    $output = '<fieldset class="databox tactical_set">
                <legend>'.__('Total agents and monitors').'</legend>'.html_print_table($table_am, true).'</fieldset>';

    return $output;
}


function reporting_get_stats_users($data)
{
    global $config;

    // Link URLS
    $urls = [];
    if (check_acl($config['id_user'], 0, 'UM')) {
        $urls['defined_users'] = 'index.php?sec=gusuarios&amp;sec2=godmode/users/user_list';
    } else {
        $urls['defined_users'] = 'javascript:';
    }

    // Users table
    $table_us = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/user_green.png', true, ['title' => __('Defined users')]);
    $tdata[1] = count(get_users());
    $tdata[1] = '<a class="big_data" href="'.$urls['defined_users'].'">'.$tdata[1].'</a>';

    $tdata[2] = $tdata[3] = '&nbsp;';
    $table_us->rowclass[] = '';
    $table_us->data[] = $tdata;

    $output = '<fieldset class="databox tactical_set">
                <legend>'.__('Users').'</legend>'.html_print_table($table_us, true).'</fieldset>';

    return $output;
}


/**
 * Get the average value of an agent module in a period of time.
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The average module value in the interval.
 */
function reporting_get_agentmodule_data_average($id_agent_module, $period=0, $date=0)
{
    global $config;

    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $search_in_history_db = db_search_in_history_db($datelimit);

    $id_module_type = modules_get_agentmodule_type($id_agent_module);
    $module_type = modules_get_moduletype_name($id_module_type);
    $uncompressed_module = is_module_uncompressed($module_type);

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT *
        FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Uncompressed module data
    if ($uncompressed_module) {
        $min_necessary = 1;

        // Compressed module data
    } else {
        // Get previous data
        $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
        if ($previous_data !== false) {
            $previous_data['utimestamp'] = $datelimit;
            array_unshift($interval_data, $previous_data);
        }

        // Get next data
        $next_data = modules_get_next_data($id_agent_module, $date);
        if ($next_data !== false) {
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        } else if (count($interval_data) > 0) {
            // Propagate the last known data to the end of the interval
            $next_data = array_pop($interval_data);
            array_push($interval_data, $next_data);
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        }

        $min_necessary = 2;
    }

    if (count($interval_data) < $min_necessary) {
        return false;
    }

    // Set initial conditions
    $total = 0;
    $count = 0;
    if (! $uncompressed_module) {
        $previous_data = array_shift($interval_data);

        // Do not count the empty start of an interval as 0
        if ($previous_data['utimestamp'] != $datelimit) {
            $period = ($date - $previous_data['utimestamp']);
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            // Do none
        break;

        case 'oracle':
            $previous_data['datos'] = oracle_format_float_to_php($previous_data['datos']);
        break;
    }

    foreach ($interval_data as $data) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                // Do none
            break;

            case 'oracle':
                $data['datos'] = oracle_format_float_to_php($data['datos']);
            break;
        }

        if (! $uncompressed_module) {
            $total += ($previous_data['datos'] * ($data['utimestamp'] - $previous_data['utimestamp']));
            $previous_data = $data;
        } else {
            $total += $data['datos'];
            $count++;
        }
    }

    // Compressed module data
    if (! $uncompressed_module) {
        if ($period == 0) {
            return 0;
        }

        return ($total / $period);
    }

    // Uncompressed module data
    if ($count == 0) {
        return 0;
    }

    return ($total / $count);
}


/**
 * Get the MTTR value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_to_recovery
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The MTTR value in the interval.
 */
function reporting_get_agentmodule_mttr($id_agent_module, $period=0, $date=0)
{
    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    // Read module configuration
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    $module = db_get_row_sql(
        'SELECT max_critical, min_critical, id_tipo_modulo
        FROM tagente_modulo
        WHERE id_agente_modulo = '.(int) $id_agent_module
    );
    if ($module === false) {
        return false;
    }

    $critical_min = $module['min_critical'];
    $critical_max = $module['max_critical'];
    $module_type = $module['id_tipo_modulo'];

    // Set critical_min and critical for proc modules
    $module_type_str = modules_get_type_name($module_type);
    if (strstr($module_type_str, 'proc') !== false
        && ($critical_min == 0 && $critical_max == 0)
    ) {
        $critical_min = 1;
    }

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT * FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Get previous data
    $previous_data = modules_get_previous_data(
        $id_agent_module,
        $datelimit
    );
    if ($previous_data !== false) {
        $previous_data['utimestamp'] = $datelimit;
        array_unshift($interval_data, $previous_data);
    }

    // Get next data
    $next_data = modules_get_next_data($id_agent_module, $date);
    if ($next_data !== false) {
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    } else if (count($interval_data) > 0) {
        // Propagate the last known data to the end of the interval
        $next_data = array_pop($interval_data);
        array_push($interval_data, $next_data);
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    }

    if (count($interval_data) < 2) {
        return false;
    }

    // Set initial conditions
    $critical_period = 0;
    $first_data = array_shift($interval_data);
    $previous_utimestamp = $first_data['utimestamp'];

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            // Do none
        break;

        case 'oracle':
            $first_data['datos'] = oracle_format_float_to_php($first_data['datos']);
        break;
    }

    if ((($critical_max > $critical_min and ($first_data['datos'] > $critical_max or $first_data['datos'] < $critical_min)))
        or ($critical_max <= $critical_min and $first_data['datos'] < $critical_min)
    ) {
        $previous_status = 1;
        $critical_count = 1;
    } else {
        $previous_status = 0;
        $critical_count = 0;
    }

    foreach ($interval_data as $data) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                // Do none
            break;

            case 'oracle':
                $data['datos'] = oracle_format_float_to_php($data['datos']);
            break;
        }

        // Previous status was critical
        if ($previous_status == 1) {
            $critical_period += ($data['utimestamp'] - $previous_utimestamp);
        }

        // Re-calculate previous status for the next data
        if ((($critical_max > $critical_min and ($data['datos'] > $critical_max or $data['datos'] < $critical_min)))
            or ($critical_max <= $critical_min and $data['datos'] < $critical_min)
        ) {
            if ($previous_status == 0) {
                $critical_count++;
            }

            $previous_status = 1;
        } else {
            $previous_status = 0;
        }

        $previous_utimestamp = $data['utimestamp'];
    }

    if ($critical_count == 0) {
        return 0;
    }

    return ($critical_period / $critical_count);
}


/**
 * Get the MTBF value of an agent module in a period of time. See
 * http://en.wikipedia.org/wiki/Mean_time_between_failures
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The MTBF value in the interval.
 */
function reporting_get_agentmodule_mtbf($id_agent_module, $period=0, $date=0)
{
    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    // Read module configuration
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    $module = db_get_row_sql(
        'SELECT max_critical, min_critical, id_tipo_modulo
        FROM tagente_modulo
        WHERE id_agente_modulo = '.(int) $id_agent_module
    );
    if ($module === false) {
        return false;
    }

    $critical_min = $module['min_critical'];
    $critical_max = $module['max_critical'];
    $module_type = $module['id_tipo_modulo'];

    // Set critical_min and critical for proc modules
    $module_type_str = modules_get_type_name($module_type);
    if (strstr($module_type_str, 'proc') !== false
        && ($critical_min == 0 && $critical_max == 0)
    ) {
        $critical_min = 1;
    }

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT * FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Get previous data
    $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
    if ($previous_data !== false) {
        $previous_data['utimestamp'] = $datelimit;
        array_unshift($interval_data, $previous_data);
    }

    // Get next data
    $next_data = modules_get_next_data($id_agent_module, $date);
    if ($next_data !== false) {
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    } else if (count($interval_data) > 0) {
        // Propagate the last known data to the end of the interval
        $next_data = array_pop($interval_data);
        array_push($interval_data, $next_data);
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    }

    if (count($interval_data) < 2) {
        return false;
    }

    // Set initial conditions
    $critical_period = 0;
    $first_data = array_shift($interval_data);
    $previous_utimestamp = $first_data['utimestamp'];

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            // Do none
        break;

        case 'oracle':
            $first_data['datos'] = oracle_format_float_to_php($first_data['datos']);
        break;
    }

    if ((($critical_max > $critical_min and ($first_data['datos'] > $critical_max or $first_data['datos'] < $critical_min)))
        or ($critical_max <= $critical_min and $first_data['datos'] < $critical_min)
    ) {
        $previous_status = 1;
        $critical_count = 1;
    } else {
        $previous_status = 0;
        $critical_count = 0;
    }

    foreach ($interval_data as $data) {
        // Previous status was critical
        if ($previous_status == 1) {
            $critical_period += ($data['utimestamp'] - $previous_utimestamp);
        }

        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                // Do none
            break;

            case 'oracle':
                $data['datos'] = oracle_format_float_to_php($data['datos']);
            break;
        }

        // Re-calculate previous status for the next data
        if ((($critical_max > $critical_min and ($data['datos'] > $critical_max or $data['datos'] < $critical_min)))
            or ($critical_max <= $critical_min and $data['datos'] < $critical_min)
        ) {
            if ($previous_status == 0) {
                $critical_count++;
            }

            $previous_status = 1;
        } else {
            $previous_status = 0;
        }

        $previous_utimestamp = $data['utimestamp'];
    }

    if ($critical_count == 0) {
        return 0;
    }

    return (($period - $critical_period) / $critical_count);
}


/**
 * Get the TTO value of an agent module in a period of time.
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The TTO value in the interval.
 */
function reporting_get_agentmodule_tto($id_agent_module, $period=0, $date=0)
{
    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    // Read module configuration
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    $module = db_get_row_sql(
        'SELECT max_critical, min_critical, id_tipo_modulo
        FROM tagente_modulo
        WHERE id_agente_modulo = '.(int) $id_agent_module
    );
    if ($module === false) {
        return false;
    }

    $critical_min = $module['min_critical'];
    $critical_max = $module['max_critical'];
    $module_type = $module['id_tipo_modulo'];

    // Set critical_min and critical for proc modules
    $module_type_str = modules_get_type_name($module_type);
    if (strstr($module_type_str, 'proc') !== false
        && ($critical_min == 0 && $critical_max == 0)
    ) {
        $critical_min = 1;
    }

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT * FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Get previous data
    $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
    if ($previous_data !== false) {
        $previous_data['utimestamp'] = $datelimit;
        array_unshift($interval_data, $previous_data);
    }

    // Get next data
    $next_data = modules_get_next_data($id_agent_module, $date);
    if ($next_data !== false) {
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    } else if (count($interval_data) > 0) {
        // Propagate the last known data to the end of the interval
        $next_data = array_pop($interval_data);
        array_push($interval_data, $next_data);
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    }

    if (count($interval_data) < 2) {
        return false;
    }

    // Set initial conditions
    $critical_period = 0;
    $first_data = array_shift($interval_data);
    $previous_utimestamp = $first_data['utimestamp'];
    if ((($critical_max > $critical_min and ($first_data['datos'] > $critical_max or $first_data['datos'] < $critical_min)))
        or ($critical_max <= $critical_min and $first_data['datos'] < $critical_min)
    ) {
        $previous_status = 1;
    } else {
        $previous_status = 0;
    }

    foreach ($interval_data as $data) {
        // Previous status was critical
        if ($previous_status == 1) {
            $critical_period += ($data['utimestamp'] - $previous_utimestamp);
        }

        // Re-calculate previous status for the next data
        if ((($critical_max > $critical_min and ($data['datos'] > $critical_max or $data['datos'] < $critical_min)))
            or ($critical_max <= $critical_min and $data['datos'] < $critical_min)
        ) {
            $previous_status = 1;
        } else {
            $previous_status = 0;
        }

        $previous_utimestamp = $data['utimestamp'];
    }

    return ($period - $critical_period);
}


/**
 * Get the TTR value of an agent module in a period of time.
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The TTR value in the interval.
 */
function reporting_get_agentmodule_ttr($id_agent_module, $period=0, $date=0)
{
    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    // Read module configuration
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    $module = db_get_row_sql(
        'SELECT max_critical, min_critical, id_tipo_modulo
        FROM tagente_modulo
        WHERE id_agente_modulo = '.(int) $id_agent_module
    );
    if ($module === false) {
        return false;
    }

    $critical_min = $module['min_critical'];
    $critical_max = $module['max_critical'];
    $module_type = $module['id_tipo_modulo'];

    // Set critical_min and critical for proc modules
    $module_type_str = modules_get_type_name($module_type);
    if (strstr($module_type_str, 'proc') !== false
        && ($critical_min == 0 && $critical_max == 0)
    ) {
        $critical_min = 1;
    }

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT * FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Get previous data
    $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
    if ($previous_data !== false) {
        $previous_data['utimestamp'] = $datelimit;
        array_unshift($interval_data, $previous_data);
    }

    // Get next data
    $next_data = modules_get_next_data($id_agent_module, $date);
    if ($next_data !== false) {
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    } else if (count($interval_data) > 0) {
        // Propagate the last known data to the end of the interval
        $next_data = array_pop($interval_data);
        array_push($interval_data, $next_data);
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    }

    if (count($interval_data) < 2) {
        return false;
    }

    // Set initial conditions
    $critical_period = 0;
    $first_data = array_shift($interval_data);
    $previous_utimestamp = $first_data['utimestamp'];
    if ((($critical_max > $critical_min and ($first_data['datos'] > $critical_max or $first_data['datos'] < $critical_min)))
        or ($critical_max <= $critical_min and $first_data['datos'] < $critical_min)
    ) {
        $previous_status = 1;
    } else {
        $previous_status = 0;
    }

    foreach ($interval_data as $data) {
        // Previous status was critical
        if ($previous_status == 1) {
            $critical_period += ($data['utimestamp'] - $previous_utimestamp);
        }

        // Re-calculate previous status for the next data
        if ((($critical_max > $critical_min and ($data['datos'] > $critical_max or $data['datos'] < $critical_min)))
            or ($critical_max <= $critical_min and $data['datos'] < $critical_min)
        ) {
            $previous_status = 1;
        } else {
            $previous_status = 0;
        }

        $previous_utimestamp = $data['utimestamp'];
    }

    return $critical_period;
}


/**
 * Get a detailed report of the modules of the agent
 *
 * @param integer $id_agent Agent id to get the report for.
 *
 * @return array An array
 */
function reporting_get_agent_module_info($id_agent)
{
    global $config;

    $return = [];
    $return['last_contact'] = 0;
    // Last agent contact.
    $return['status'] = STATUS_AGENT_NO_DATA;
    $return['status_img'] = ui_print_status_image(
        STATUS_AGENT_NO_DATA,
        __('Agent without data'),
        true
    );
    $return['alert_status'] = 'notfired';
    $return['alert_value'] = STATUS_ALERT_NOT_FIRED;
    $return['alert_img'] = ui_print_status_image(
        STATUS_ALERT_NOT_FIRED,
        __('Alert not fired'),
        true
    );

    $return['agent_group'] = '';
    // Important agents_get_all_groups_agent check secondary groups.
    $id_all_groups = agents_get_all_groups_agent($id_agent);
    if (isset($id_all_groups) && is_array($id_all_groups)) {
        foreach ($id_all_groups as $value) {
            if (check_acl($config['id_user'], $value, 'AR')) {
                $return['agent_group'] = $value;
            }
        }
    }

    // If $return['agent_group'] is empty no access.
    if ($return['agent_group'] == '') {
        return $return;
    }

    $filter = ['disabled' => 0];
    $modules = agents_get_modules($id_agent, false, $filter, true, false);

    if ($modules === false) {
        return $return;
    }

    $now = get_system_time();

    // Get modules status for this agent.
    $agent = db_get_row('tagente', 'id_agente', $id_agent);

    $return['total_count'] = $agent['total_count'];
    $return['normal_count'] = $agent['normal_count'];
    $return['warning_count'] = $agent['warning_count'];
    $return['critical_count'] = $agent['critical_count'];
    $return['unknown_count'] = $agent['unknown_count'];
    $return['fired_count'] = $agent['fired_count'];
    $return['notinit_count'] = $agent['notinit_count'];

    if ($return['total_count'] > 0) {
        if ($return['critical_count'] > 0) {
            $return['status'] = STATUS_AGENT_CRITICAL;
            $return['status_img'] = ui_print_status_image(
                STATUS_AGENT_CRITICAL,
                __('At least one module in CRITICAL status'),
                true
            );
        } else if ($return['warning_count'] > 0) {
            $return['status'] = STATUS_AGENT_WARNING;
            $return['status_img'] = ui_print_status_image(
                STATUS_AGENT_WARNING,
                __('At least one module in WARNING status'),
                true
            );
        } else if ($return['unknown_count'] > 0) {
            $return['status'] = STATUS_AGENT_DOWN;
            $return['status_img'] = ui_print_status_image(
                STATUS_AGENT_DOWN,
                __('At least one module is in UKNOWN status'),
                true
            );
        } else {
            $return['status'] = STATUS_AGENT_OK;
            $return['status_img'] = ui_print_status_image(
                STATUS_AGENT_OK,
                __('All Monitors OK'),
                true
            );
        }
    }

    // Alert not fired is by default.
    if ($return['fired_count'] > 0) {
        $return['alert_status'] = 'fired';
        $return['alert_img'] = ui_print_status_image(
            STATUS_ALERT_FIRED,
            __('Alert fired'),
            true
        );
        $return['alert_value'] = STATUS_ALERT_FIRED;
    } else if (groups_give_disabled_group($return['agent_group'])) {
        $return['alert_status'] = 'disabled';
        $return['alert_value'] = STATUS_ALERT_DISABLED;
        $return['alert_img'] = ui_print_status_image(
            STATUS_ALERT_DISABLED,
            __('Alert disabled'),
            true
        );
    }

    return $return;
}


/**
 * Print tiny statistics of the status of one agent, group, etc.
 *
 * @param mixed   $counts_info Array with the counts of the total modules,
 * normal modules, critical modules, warning modules, unknown modules and
 * fired alerts.
 * @param boolean $return      Return or echo flag.
 * @param string  $type        agent or modules or ??.
 * @param string  $separator   Sepearator (classic view).
 * @param boolean $modern      Use modern interfaces or old one.
 *
 * @return string HTML formatted tiny stats of modules/alerts of an agent.
 */
function reporting_tiny_stats(
    $counts_info,
    $return=false,
    $type='agent',
    $separator=':',
    $modern=false
) {
    global $config;

    $out = '';

    // Depend the type of object, the stats will refer agents, modules...
    switch ($type) {
        case 'modules':
            $template_title['total_count'] = __('%d Total modules');
            $template_title['normal_count'] = __('%d Modules in normal status');
            $template_title['critical_count'] = __('%d Modules in critical status');
            $template_title['warning_count'] = __('%d Modules in warning status');
            $template_title['unknown_count'] = __('%d Modules in unknown status');
            $template_title['not_init_count'] = __('%d Modules in not init status');
        break;

        case 'agent':
            $template_title['total_count'] = __('%d Total modules');
            $template_title['normal_count'] = __('%d Normal modules');
            $template_title['critical_count'] = __('%d Critical modules');
            $template_title['warning_count'] = __('%d Warning modules');
            $template_title['unknown_count'] = __('%d Unknown modules');
            $template_title['fired_count'] = __('%d Fired alerts');
        break;

        default:
            $template_title['total_count'] = __('%d Total agents');
            $template_title['normal_count'] = __('%d Normal agents');
            $template_title['critical_count'] = __('%d Critical agents');
            $template_title['warning_count'] = __('%d Warning agents');
            $template_title['unknown_count'] = __('%d Unknown agents');
            $template_title['not_init_count'] = __('%d not init agents');
            $template_title['fired_count'] = __('%d Fired alerts');
        break;
    }

    // Store the counts in a data structure to print hidden divs with titles
    $stats = [];

    if (isset($counts_info['total_count'])) {
        $not_init = isset($counts_info['notinit_count']) ? $counts_info['notinit_count'] : 0;
        $total_count = ($counts_info['total_count'] - $not_init);
        $stats[] = [
            'name'  => 'total_count',
            'count' => $total_count,
            'title' => sprintf($template_title['total_count'], $total_count),
        ];
    }

    if (isset($counts_info['normal_count'])) {
        $normal_count = $counts_info['normal_count'];
        $stats[] = [
            'name'  => 'normal_count',
            'count' => $normal_count,
            'title' => sprintf($template_title['normal_count'], $normal_count),
        ];
    }

    if (isset($counts_info['critical_count'])) {
        $critical_count = $counts_info['critical_count'];
        $stats[] = [
            'name'  => 'critical_count',
            'count' => $critical_count,
            'title' => sprintf($template_title['critical_count'], $critical_count),
        ];
    }

    if (isset($counts_info['warning_count'])) {
        $warning_count = $counts_info['warning_count'];
        $stats[] = [
            'name'  => 'warning_count',
            'count' => $warning_count,
            'title' => sprintf($template_title['warning_count'], $warning_count),
        ];
    }

    if (isset($counts_info['unknown_count'])) {
        $unknown_count = $counts_info['unknown_count'];
        $stats[] = [
            'name'  => 'unknown_count',
            'count' => $unknown_count,
            'title' => sprintf($template_title['unknown_count'], $unknown_count),
        ];
    }

    if (isset($counts_info['not_init_count'])) {
        $not_init_count = $counts_info['not_init_count'];
        $stats[] = [
            'name'  => 'not_init_count',
            'count' => $not_init_count,
            'title' => sprintf($template_title['not_init_count'], $not_init_count),
        ];
    }

    if (isset($template_title['fired_count'])) {
        if (isset($counts_info['fired_count'])) {
            $fired_count = $counts_info['fired_count'];
            $stats[] = [
                'name'  => 'fired_count',
                'count' => $fired_count,
                'title' => sprintf($template_title['fired_count'], $fired_count),
            ];
        }
    }

    $uniq_id = uniqid();

    foreach ($stats as $stat) {
        $params = [
            'id'      => 'forced_title_'.$stat['name'].'_'.$uniq_id,
            'class'   => 'forced_title_layer',
            'content' => $stat['title'],
            'hidden'  => true,
        ];
        $out .= html_print_div($params, true);
    }

    // If total count is less than 0, is an error. Never show negative numbers.
    if ($total_count < 0) {
        $total_count = 0;
    }

    if ($modern === true) {
        $out .= '<div id="bullets_modules">';
        // $out .='<span id="total_count_'.$uniq_id.'" class="forced_title" style="font-size: 13pt">'.$total_count.$separator.'</span>';
        if (isset($fired_count) && $fired_count > 0) {
            $out .= '<div><div id="fired_count_'.$uniq_id.'" class="forced_title bullet_modules orange_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$fired_count.'</span></div>';
        }

        if (isset($critical_count) && $critical_count > 0) {
            $out .= '<div><div id="critical_count_'.$uniq_id.'" class="forced_title bullet_modules red_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$critical_count.'</span></div>';
        }

        if (isset($warning_count) && $warning_count > 0) {
            $out .= '<div><div id="warning_count_'.$uniq_id.'" class="forced_title bullet_modules yellow_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$warning_count.'</span></div>';
        }

        if (isset($unknown_count) && $unknown_count > 0) {
            $out .= '<div><div id="unknown_count_'.$uniq_id.'" class="forced_title bullet_modules grey_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$unknown_count.'</span></div>';
        }

        if (isset($not_init_count) && $not_init_count > 0) {
            $out .= '<div><div id="not_init_count_'.$uniq_id.'" class="forced_title bullet_modules blue_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$not_init_count.'</span></div>';
        }

        if (isset($normal_count) && $normal_count > 0) {
            $out .= '<div><div id="normal_count_'.$uniq_id.'" class="forced_title bullet_modules green_background"></div>';
            $out .= '<span style="font-size: 12pt">'.$normal_count.'</span></div>';
        }

        $out .= '</div>';
    } else {
        // Classic ones.
        $out .= '<b><span id="total_count_'.$uniq_id.'" class="forced_title" style="font-size: 7pt">'.$total_count.'</span>';
        if (isset($fired_count) && $fired_count > 0) {
            $out .= ' '.$separator.' <span class="orange forced_title" id="fired_count_'.$uniq_id.'" style="font-size: 7pt">'.$fired_count.'</span>';
        }

        if (isset($critical_count) && $critical_count > 0) {
            $out .= ' '.$separator.' <span class="red forced_title" id="critical_count_'.$uniq_id.'" style="font-size: 7pt">'.$critical_count.'</span>';
        }

        if (isset($warning_count) && $warning_count > 0) {
            $out .= ' '.$separator.' <span class="yellow forced_title" id="warning_count_'.$uniq_id.'" style="font-size: 7pt">'.$warning_count.'</span>';
        }

        if (isset($unknown_count) && $unknown_count > 0) {
            $out .= ' '.$separator.' <span class="grey forced_title" id="unknown_count_'.$uniq_id.'" style="font-size: 7pt">'.$unknown_count.'</span>';
        }

        if (isset($not_init_count) && $not_init_count > 0) {
            $out .= ' '.$separator.' <span class="blue forced_title" id="not_init_count_'.$uniq_id.'" style="font-size: 7pt">'.$not_init_count.'</span>';
        }

        if (isset($normal_count) && $normal_count > 0) {
            $out .= ' '.$separator.' <span class="green forced_title" id="normal_count_'.$uniq_id.'" style="font-size: 7pt">'.$normal_count.'</span>';
        }

        $out .= '</b>';
    }

    if ($return) {
        return $out;
    } else {
        echo $out;
    }
}


/**
 * Get SLA of a module.
 *
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param array                                                                   $dayWeek  Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string                                                                  $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string                                                                  $timeTo   Time in the day to end to extract in mysql format, by default null.
 *
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function reporting_get_agentmodule_sla(
    $id_agent_module,
    $period=0,
    $min_value=1,
    $max_value=false,
    $date=0,
    $daysWeek=null,
    $timeFrom=null,
    $timeTo=null
) {
    global $config;

    if (empty($id_agent_module)) {
        return false;
    }

    // Set initial conditions
    $bad_period = 0;
    // Limit date to start searching data
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    if ($daysWeek === null) {
        $daysWeek = [];
    }

    // Calculate the SLA for large time without hours
    if ($timeFrom == $timeTo) {
        // Get interval data
        $sql = sprintf(
            'SELECT *
            FROM tagente_datos
            WHERE id_agente_modulo = %d
                AND utimestamp > %d AND utimestamp <= %d',
            $id_agent_module,
            $datelimit,
            $date
        );

        // Add the working times (mon - tue - wed ...) and from time to time
        $days = [];
        // Translate to mysql week days
        if ($daysWeek) {
            foreach ($daysWeek as $key => $value) {
                if (!$value) {
                    if ($key == 'monday') {
                        $days[] = 2;
                    }

                    if ($key == 'tuesday') {
                        $days[] = 3;
                    }

                    if ($key == 'wednesday') {
                        $days[] = 4;
                    }

                    if ($key == 'thursday') {
                        $days[] = 5;
                    }

                    if ($key == 'friday') {
                        $days[] = 6;
                    }

                    if ($key == 'saturday') {
                        $days[] = 7;
                    }

                    if ($key == 'sunday') {
                        $days[] = 1;
                    }
                }
            }
        }

        if (count($days) > 0) {
            $sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN ('.implode(',', $days).')';
        }

        $sql .= "\n";
        $sql .= ' ORDER BY utimestamp ASC';
        $interval_data = db_get_all_rows_sql($sql, $search_in_history_db);

        if ($interval_data === false) {
            $interval_data = [];
        }

        // Calculate planned downtime dates
        $downtime_dates = reporting_get_planned_downtimes_intervals(
            $id_agent_module,
            $datelimit,
            $date
        );

        // Get previous data
        $previous_data = modules_get_previous_data($id_agent_module, $datelimit);

        if ($previous_data !== false) {
            $previous_data['utimestamp'] = $datelimit;
            array_unshift($interval_data, $previous_data);
        }

        // Get next data
        $next_data = modules_get_next_data($id_agent_module, $date);

        if ($next_data !== false) {
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        } else if (count($interval_data) > 0) {
            // Propagate the last known data to the end of the interval
            $next_data = array_pop($interval_data);
            array_push($interval_data, $next_data);
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        }

        if (count($interval_data) < 2) {
            return false;
        }

        $first_data = array_shift($interval_data);

        // Do not count the empty start of an interval as 0
        if ($first_data['utimestamp'] != $datelimit) {
            $period = ($date - $first_data['utimestamp']);
        }

        $previous_utimestamp = $first_data['utimestamp'];
        if ((                (                    $max_value > $min_value and (                        $first_data['datos'] > $max_value
            or $first_data['datos'] < $min_value                    )                )            )
            or (                $max_value <= $min_value
            and $first_data['datos'] < $min_value            )
        ) {
            $previous_status = 1;
            foreach ($downtime_dates as $date_dt) {
                if (($date_dt['date_from'] <= $previous_utimestamp)
                    and ($date_dt['date_to'] >= $previous_utimestamp)
                ) {
                    $previous_status = 0;
                }
            }
        } else {
            $previous_status = 0;
        }

        foreach ($interval_data as $data) {
            // Previous status was critical
            if ($previous_status == 1) {
                $bad_period += ($data['utimestamp'] - $previous_utimestamp);
            }

            if (array_key_exists('datos', $data)) {
                // Re-calculate previous status for the next data
                if ((($max_value > $min_value and ($data['datos'] > $max_value or $data['datos'] < $min_value)))
                    or ($max_value <= $min_value and $data['datos'] < $min_value)
                ) {
                    $previous_status = 1;
                    foreach ($downtime_dates as $date_dt) {
                        if (($date_dt['date_from'] <= $data['utimestamp']) and ($date_dt['date_to'] >= $data['utimestamp'])) {
                            $previous_status = 0;
                        }
                    }
                } else {
                    $previous_status = 0;
                }
            }

            $previous_utimestamp = $data['utimestamp'];
        }

        // Return the percentage of SLA compliance
        return (float) (100 - ($bad_period / $period) * 100);
    } else if ($period <= SECONDS_1DAY) {
        return reporting_get_agentmodule_sla_day(
            $id_agent_module,
            $period,
            $min_value,
            $max_value,
            $date,
            $daysWeek,
            $timeFrom,
            $timeTo
        );
    } else {
        // Extract the data each day
        $sla = 0;

        $i = 0;
        for ($interval = SECONDS_1DAY; $interval <= $period; $interval = ($interval + SECONDS_1DAY)) {
            $sla_day = reporting_get_agentmodule_sla(
                $id_agent_module,
                SECONDS_1DAY,
                $min_value,
                $max_value,
                ($datelimit + $interval),
                $daysWeek,
                $timeFrom,
                $timeTo
            );

            // Avoid to add the period of module not init
            if ($sla_day !== false) {
                $sla += $sla_day;
                $i++;
            }
        }

        $sla = ($sla / $i);

        return $sla;
    }
}


/**
 * Get the time intervals where an agentmodule is affected by the planned downtimes.
 *
 * @param int Agent module to calculate planned downtimes intervals.
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param bool Whether ot not to get the planned downtimes that affect the service associated with the agentmodule.
 *
 * @return array with time intervals.
 */
function reporting_get_planned_downtimes_intervals($id_agent_module, $start_date, $end_date, $check_services=false)
{
    global $config;

    if (empty($id_agent_module)) {
        return false;
    }

    include_once $config['homedir'].'/include/functions_planned_downtimes.php';

    $malformed_planned_downtimes = planned_downtimes_get_malformed();
    if (empty($malformed_planned_downtimes)) {
        $malformed_planned_downtimes = [];
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $tpdr_description = 'tpdr.description';
        break;

        case 'oracle':
            $tpdr_description = 'to_char(tpdr.description)';
        break;
    }

    $sql_downtime = '
        SELECT DISTINCT(tpdr.id),
                tpdr.name,
                '.$tpdr_description.",
                tpdr.date_from,
                tpdr.date_to,
                tpdr.executed,
                tpdr.id_group,
                tpdr.only_alerts,
                tpdr.monday,
                tpdr.tuesday,
                tpdr.wednesday,
                tpdr.thursday,
                tpdr.friday,
                tpdr.saturday,
                tpdr.sunday,
                tpdr.periodically_time_from,
                tpdr.periodically_time_to,
                tpdr.periodically_day_from,
                tpdr.periodically_day_to,
                tpdr.type_downtime,
                tpdr.type_execution,
                tpdr.type_periodicity,
                tpdr.id_user
        FROM (
                SELECT tpd.*
                FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
                WHERE tpd.id = tpda.id_downtime
                    AND tpda.all_modules = 1
                    AND tpda.id_agent = tam.id_agente
                    AND tam.id_agente_modulo = $id_agent_module
            UNION ALL
                SELECT tpd.*
                FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
                WHERE tpd.id = tpdm.id_downtime
                    AND tpdm.id_agent_module = $id_agent_module
        ) tpdr
        ORDER BY tpdr.id";

    $downtimes = db_get_all_rows_sql($sql_downtime);

    if ($downtimes == false) {
        $downtimes = [];
    }

    $downtime_dates = [];
    foreach ($downtimes as $downtime) {
        $downtime_id = $downtime['id'];
        $downtime_type = $downtime['type_execution'];
        $downtime_periodicity = $downtime['type_periodicity'];

        if ($downtime_type == 'once') {
            $dates = [];
            $dates['date_from'] = $downtime['date_from'];
            $dates['date_to'] = $downtime['date_to'];
            $downtime_dates[] = $dates;
        } else if ($downtime_type == 'periodically') {
            // If a planned downtime have malformed dates, its intervals aren't taken account
            $downtime_malformed = false;
            foreach ($malformed_planned_downtimes as $malformed_planned_downtime) {
                if ($downtime_id == $malformed_planned_downtime['id']) {
                    $downtime_malformed = true;
                    break;
                }
            }

            if ($downtime_malformed == true) {
                continue;
            }

            // If a planned downtime have malformed dates, its intervals aren't taken account
            $downtime_time_from = $downtime['periodically_time_from'];
            $downtime_time_to = $downtime['periodically_time_to'];

            $downtime_hour_from = date('H', strtotime($downtime_time_from));
            $downtime_minute_from = date('i', strtotime($downtime_time_from));
            $downtime_second_from = date('s', strtotime($downtime_time_from));
            $downtime_hour_to = date('H', strtotime($downtime_time_to));
            $downtime_minute_to = date('i', strtotime($downtime_time_to));
            $downtime_second_to = date('s', strtotime($downtime_time_to));

            if ($downtime_periodicity == 'monthly') {
                $downtime_day_from = $downtime['periodically_day_from'];
                $downtime_day_to = $downtime['periodically_day_to'];

                $date_aux = strtotime(date('Y-m-01', $start_date));
                $year_aux = date('Y', $date_aux);
                $month_aux = date('m', $date_aux);

                $end_year = date('Y', $end_date);
                $end_month = date('m', $end_date);

                while ($year_aux < $end_year || ($year_aux == $end_year && $month_aux <= $end_month)) {
                    if ($downtime_day_from > $downtime_day_to) {
                        $dates = [];
                        $dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                        $dates['date_to'] = strtotime(date('Y-m-t H:i:s', strtotime("$year_aux-$month_aux-28 23:59:59")));
                        $downtime_dates[] = $dates;

                        $dates = [];
                        if (($month_aux + 1) <= 12) {
                            $dates['date_from'] = strtotime("$year_aux-".($month_aux + 1).'-01 00:00:00');
                            $dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                        } else {
                            $dates['date_from'] = strtotime(($year_aux + 1).'-01-01 00:00:00');
                            $dates['date_to'] = strtotime(($year_aux + 1)."-01-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                        }

                        $downtime_dates[] = $dates;
                    } else {
                        if ($downtime_day_from == $downtime_day_to && strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
                            $date_aux_from = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                            $max_day_num = date('t', $date_aux);

                            $dates = [];
                            $dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                            $dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_from 23:59:59");
                            $downtime_dates[] = $dates;

                            if (($downtime_day_to + 1) > $max_day_num) {
                                $dates = [];
                                if (($month_aux + 1) <= 12) {
                                    $dates['date_from'] = strtotime("$year_aux-".($month_aux + 1).'-01 00:00:00');
                                    $dates['date_to'] = strtotime("$year_aux-".($month_aux + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                                } else {
                                    $dates['date_from'] = strtotime(($year_aux + 1).'-01-01 00:00:00');
                                    $dates['date_to'] = strtotime(($year_aux + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                                }

                                $downtime_dates[] = $dates;
                            } else {
                                $dates = [];
                                $dates['date_from'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1).' 00:00:00');
                                $dates['date_to'] = strtotime("$year_aux-$month_aux-".($downtime_day_to + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                                $downtime_dates[] = $dates;
                            }
                        } else {
                            $dates = [];
                            $dates['date_from'] = strtotime("$year_aux-$month_aux-$downtime_day_from $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                            $dates['date_to'] = strtotime("$year_aux-$month_aux-$downtime_day_to $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                            $downtime_dates[] = $dates;
                        }
                    }

                    $month_aux++;
                    if ($month_aux > 12) {
                        $month_aux = 1;
                        $year_aux++;
                    }
                }
            } else if ($downtime_periodicity == 'weekly') {
                $date_aux = $start_date;
                $active_days = [];
                $active_days[0] = ($downtime['sunday'] == 1) ? true : false;
                $active_days[1] = ($downtime['monday'] == 1) ? true : false;
                $active_days[2] = ($downtime['tuesday'] == 1) ? true : false;
                $active_days[3] = ($downtime['wednesday'] == 1) ? true : false;
                $active_days[4] = ($downtime['thursday'] == 1) ? true : false;
                $active_days[5] = ($downtime['friday'] == 1) ? true : false;
                $active_days[6] = ($downtime['saturday'] == 1) ? true : false;

                while ($date_aux <= $end_date) {
                    $weekday_num = date('w', $date_aux);

                    if ($active_days[$weekday_num]) {
                        $day_num = date('d', $date_aux);
                        $month_num = date('m', $date_aux);
                        $year_num = date('Y', $date_aux);

                        $max_day_num = date('t', $date_aux);

                        if (strtotime($downtime_time_from) > strtotime($downtime_time_to)) {
                            $dates = [];
                            $dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                            $dates['date_to'] = strtotime("$year_num-$month_num-$day_num 23:59:59");
                            $downtime_dates[] = $dates;

                            $dates = [];
                            if (($day_num + 1) > $max_day_num) {
                                if (($month_num + 1) > 12) {
                                    $dates['date_from'] = strtotime(($year_num + 1).'-01-01 00:00:00');
                                    $dates['date_to'] = strtotime(($year_num + 1)."-01-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                                } else {
                                    $dates['date_from'] = strtotime("$year_num-".($month_num + 1).'-01 00:00:00');
                                    $dates['date_to'] = strtotime("$year_num-".($month_num + 1)."-01 $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                                }
                            } else {
                                $dates['date_from'] = strtotime("$year_num-$month_num-".($day_num + 1).' 00:00:00');
                                $dates['date_to'] = strtotime("$year_num-$month_num-".($day_num + 1)." $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                            }

                            $downtime_dates[] = $dates;
                        } else {
                            $dates = [];
                            $dates['date_from'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_from:$downtime_minute_from:$downtime_second_from");
                            $dates['date_to'] = strtotime("$year_num-$month_num-$day_num $downtime_hour_to:$downtime_minute_to:$downtime_second_to");
                            $downtime_dates[] = $dates;
                        }
                    }

                    $date_aux += SECONDS_1DAY;
                }
            }
        }
    }

    if ($check_services) {
        enterprise_include_once('include/functions_services.php');
        if (function_exists('services_get_planned_downtimes_intervals')) {
            services_get_planned_downtimes_intervals($downtime_dates, $start_date, $end_date, false, $id_agent_module);
        }
    }

    return $downtime_dates;
}


/**
 * Get the maximum value of an agent module in a period of time.
 *
 * @param int Agent module id to get the maximum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 *
 * @return float The maximum module value in the interval.
 */
function reporting_get_agentmodule_data_max($id_agent_module, $period=0, $date=0)
{
    global $config;

    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $search_in_history_db = db_search_in_history_db($datelimit);

    $id_module_type = modules_get_agentmodule_type($id_agent_module);
    $module_type = modules_get_moduletype_name($id_module_type);
    $uncompressed_module = is_module_uncompressed($module_type);

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT *
        FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );

    if ($interval_data === false) {
        $interval_data = [];
    }

    // Uncompressed module data
    if ($uncompressed_module) {
        // Compressed module data
    } else {
        // Get previous data
        $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
        if ($previous_data !== false) {
            $previous_data['utimestamp'] = $datelimit;
            array_unshift($interval_data, $previous_data);
        }

        // Get next data
        $next_data = modules_get_next_data($id_agent_module, $date);
        if ($next_data !== false) {
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        } else if (count($interval_data) > 0) {
            // Propagate the last known data to the end of the interval
            $next_data = array_pop($interval_data);
            array_push($interval_data, $next_data);
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        }
    }

    // Set initial conditions
    if (empty($iterval_data)) {
        $max = 0;
    } else {
        if ($uncompressed_module || $interval_data[0]['utimestamp'] == $datelimit) {
            $max = $interval_data[0]['datos'];
        } else {
            $max = 0;
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            // Do none
        break;

        case 'oracle':
            $max = oracle_format_float_to_php($max);
        break;
    }

    foreach ($interval_data as $data) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                // Do none
            break;

            case 'oracle':
                $data['datos'] = oracle_format_float_to_php($data['datos']);
            break;
        }

        if ($data['datos'] > $max) {
            $max = $data['datos'];
        }
    }

    return $max;
}


/**
 * Get the minimum value of an agent module in a period of time.
 *
 * @param int Agent module id to get the minimum value.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values in Unix time. Default current time.
 *
 * @return float The minimum module value of the module
 */
function reporting_get_agentmodule_data_min($id_agent_module, $period=0, $date=0)
{
    global $config;

    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $search_in_history_db = db_search_in_history_db($datelimit);

    $id_module_type = modules_get_agentmodule_type($id_agent_module);
    $module_type = modules_get_moduletype_name($id_module_type);
    $uncompressed_module = is_module_uncompressed($module_type);

    // Get module data
    $interval_data = db_get_all_rows_sql(
        'SELECT *
        FROM tagente_datos 
        WHERE id_agente_modulo = '.(int) $id_agent_module.' AND utimestamp > '.(int) $datelimit.' AND utimestamp < '.(int) $date.' ORDER BY utimestamp ASC',
        $search_in_history_db
    );
    if ($interval_data === false) {
        $interval_data = [];
    }

    // Uncompressed module data
    if ($uncompressed_module) {
        $min_necessary = 1;

        // Compressed module data
    } else {
        // Get previous data
        $previous_data = modules_get_previous_data($id_agent_module, $datelimit);
        if ($previous_data !== false) {
            $previous_data['utimestamp'] = $datelimit;
            array_unshift($interval_data, $previous_data);
        }

        // Get next data
        $next_data = modules_get_next_data($id_agent_module, $date);
        if ($next_data !== false) {
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        } else if (count($interval_data) > 0) {
            // Propagate the last known data to the end of the interval
            $next_data = array_pop($interval_data);
            array_push($interval_data, $next_data);
            $next_data['utimestamp'] = $date;
            array_push($interval_data, $next_data);
        }
    }

    if (count($interval_data) < 1) {
        return false;
    }

    // Set initial conditions
    $min = $interval_data[0]['datos'];

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            // Do none
        break;

        case 'oracle':
            $min = oracle_format_float_to_php($min);
        break;
    }

    foreach ($interval_data as $data) {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                // Do none
            break;

            case 'oracle':
                $data['datos'] = oracle_format_float_to_php($data['datos']);
            break;
        }

        if ($data['datos'] < $min) {
            $min = $data['datos'];
        }
    }

    return $min;
}


/**
 * Get the sum of values of an agent module in a period of time.
 *
 * @param int Agent module id to get the sumatory.
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * @param boolean Show uncompressed data from module
 *
 * @return float The sumatory of the module values in the interval.
 */
function reporting_get_agentmodule_data_sum(
    $id_agent_module,
    $period=0,
    $date=0,
    $uncompressed_module=true
) {
    global $config;

    // Initialize variables.
    if (empty($date)) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    $search_in_history_db = db_search_in_history_db($datelimit);

    $id_module_type = db_get_value(
        'id_tipo_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        $id_agent_module
    );
    $module_name = db_get_value(
        'nombre',
        'ttipo_modulo',
        'id_tipo',
        $id_module_type
    );
    $module_interval = modules_get_interval($id_agent_module);
    // Check if module must be compressed.
    if (!$uncompressed_module) {
        $uncompressed_module = is_module_uncompressed($module_name);
    }

    // Wrong module type
    if (is_module_data_string($module_name)) {
        return 0;
    }

    // Incremental modules are treated differently.
    $module_inc = is_module_inc($module_name);

    if (!$uncompressed_module) {
        // Get module data.
        $interval_data = db_get_all_rows_sql(
            '
            SELECT * FROM tagente_datos
            WHERE id_agente_modulo = '.(int) $id_agent_module.'
                AND utimestamp > '.(int) $datelimit.'
                AND utimestamp < '.(int) $date.'
            ORDER BY utimestamp ASC',
            $search_in_history_db
        );
    } else {
        $interval_data = db_uncompress_module_data((int) $id_agent_module, (int) $datelimit, (int) $date);
    }

    if ($interval_data === false) {
        $interval_data = [];
    }

    $min_necessary = 1;

    if (count($interval_data) < $min_necessary) {
        return false;
    }

    // Set initial conditions.
    $total = 0;
    $partial_total = 0;
    $count_sum = 0;

    foreach ($interval_data as $data) {
        $partial_total = 0;
        $count_sum = 0;

        if (!$uncompressed_module) {
            $total += $data['datos'];
        } else if (!$module_inc) {
            foreach ($data['data'] as $val) {
                if (is_numeric($val['datos'])) {
                    $partial_total += $val['datos'];
                    $count_sum++;
                }
            }

            if ($count_sum === 0) {
                continue;
            }

            $total += $partial_total;
        } else {
            $last = end($data['data']);
            $total += $last['datos'];
        }
    }

    return $total;
}


/**
 * Get the planned downtimes that affect the passed modules on an specific datetime range.
 *
 * @param int Start date in utimestamp.
 * @param int End date in utimestamp.
 * @param array The agent modules ids.
 *
 * @return array with the planned downtimes that are executed in any moment of the range selected and affect the
 * agent modules selected.
 */
function reporting_get_planned_downtimes($start_date, $end_date, $id_agent_modules=false)
{
    global $config;

    $start_time = date('H:i:s', $start_date);
    $end_time = date('H:i:s', $end_date);

    $start_day = date('d', $start_date);
    $end_day = date('d', $end_date);

    $start_month = date('m', $start_date);
    $end_month = date('m', $end_date);

    if ($start_date > $end_date) {
        return false;
    }

    if (($end_date - $start_date) >= SECONDS_1MONTH) {
        // If the date range is larger than 1 month, every monthly planned downtime will be inside
        $periodically_monthly_w = "type_periodicity = 'monthly'";
    } else {
        // Check if the range is larger than the planned downtime execution, or if its start or end
        // is inside the planned downtime execution.
        // The start and end time is very important.
        $periodically_monthly_w = "type_periodicity = 'monthly'
                                    AND (((periodically_day_from > '$start_day'
                                                OR (periodically_day_from = '$start_day'
                                                    AND periodically_time_from >= '$start_time'))
                                            AND (periodically_day_to < '$end_day'
                                                OR (periodically_day_to = '$end_day'
                                                    AND periodically_time_to <= '$end_time')))
                                        OR ((periodically_day_from < '$start_day' 
                                                OR (periodically_day_from = '$start_day'
                                                    AND periodically_time_from <= '$start_time'))
                                            AND (periodically_day_to > '$start_day'
                                                OR (periodically_day_to = '$start_day'
                                                    AND periodically_time_to >= '$start_time')))
                                        OR ((periodically_day_from < '$end_day' 
                                                OR (periodically_day_from = '$end_day'
                                                    AND periodically_time_from <= '$end_time'))
                                            AND (periodically_day_to > '$end_day'
                                                OR (periodically_day_to = '$end_day'
                                                    AND periodically_time_to >= '$end_time'))))";
    }

    $periodically_weekly_days = [];
    $date_aux = $start_date;
    $i = 0;

    if (($end_date - $start_date) >= SECONDS_1WEEK) {
        // If the date range is larger than 7 days, every weekly planned downtime will be inside.
        for ($i = 0; $i < 7; $i++) {
            $weekday_actual = strtolower(date('l', $date_aux));
            $periodically_weekly_days[] = "($weekday_actual = 1)";
            $date_aux += SECONDS_1DAY;
        }
    } else if (($end_date - $start_date) <= SECONDS_1DAY && $start_day == $end_day) {
        // If the date range is smaller than 1 day, the start and end days can be equal or consecutive.
        // If they are equal, the execution times have to be contained in the date range times or contain
        // the start or end time of the date range.
        $weekday_actual = strtolower(date('l', $start_date));
        $periodically_weekly_days[] = "($weekday_actual = 1
            AND ((periodically_time_from > '$start_time' AND periodically_time_to < '$end_time')
                OR (periodically_time_from = '$start_time'
                    OR (periodically_time_from < '$start_time'
                        AND periodically_time_to >= '$start_time'))
                OR (periodically_time_from = '$end_time'
                    OR (periodically_time_from < '$end_time'
                        AND periodically_time_to >= '$end_time'))))";
    } else {
        while ($date_aux <= $end_date && $i < 7) {
            $weekday_actual = strtolower(date('l', $date_aux));
            $day_num_actual = date('d', $date_aux);

            if ($date_aux == $start_date) {
                $periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_to >= '$start_time')";
            } else if ($day_num_actual == $end_day) {
                $periodically_weekly_days[] = "($weekday_actual = 1 AND periodically_time_from <= '$end_time')";
            } else {
                $periodically_weekly_days[] = "($weekday_actual = 1)";
            }

            $date_aux += SECONDS_1DAY;
            $i++;
        }
    }

    if (!empty($periodically_weekly_days)) {
        $periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(' OR ', $periodically_weekly_days).')';
        $periodically_condition = "(($periodically_monthly_w) OR ($periodically_weekly_w))";
    } else {
        $periodically_condition = "($periodically_monthly_w)";
    }

    if ($id_agent_modules !== false) {
        if (empty($id_agent_modules)) {
            return [];
        }

        $id_agent_modules_str = implode(',', $id_agent_modules);

        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $tpdr_description = 'tpdr.description';
            break;

            case 'oracle':
                $tpdr_description = 'to_char(tpdr.description)';
            break;
        }

        $sql_downtime = '
            SELECT
                DISTINCT(tpdr.id),
                tpdr.name,
                '.$tpdr_description.",
                tpdr.date_from,
                tpdr.date_to,
                tpdr.executed,
                tpdr.id_group,
                tpdr.only_alerts,
                tpdr.monday,
                tpdr.tuesday,
                tpdr.wednesday,
                tpdr.thursday,
                tpdr.friday,
                tpdr.saturday,
                tpdr.sunday,
                tpdr.periodically_time_from,
                tpdr.periodically_time_to,
                tpdr.periodically_day_from,
                tpdr.periodically_day_to,
                tpdr.type_downtime,
                tpdr.type_execution,
                tpdr.type_periodicity,
                tpdr.id_user
            FROM (
                    SELECT tpd.*
                    FROM tplanned_downtime tpd, tplanned_downtime_agents tpda, tagente_modulo tam
                    WHERE (tpd.id = tpda.id_downtime
                            AND tpda.all_modules = 1
                            AND tpda.id_agent = tam.id_agente
                            AND tam.id_agente_modulo IN ($id_agent_modules_str))
                        AND ((type_execution = 'periodically'
                                AND $periodically_condition)
                            OR (type_execution = 'once'
                                AND ((date_from >= '$start_date' AND date_to <= '$end_date')
                                    OR (date_from <= '$start_date' AND date_to >= '$end_date')
                                    OR (date_from <= '$start_date' AND date_to >= '$start_date')
                                    OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
                UNION ALL
                    SELECT tpd.*
                    FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
                    WHERE (tpd.id = tpdm.id_downtime
                            AND tpdm.id_agent_module IN ($id_agent_modules_str))
                        AND ((type_execution = 'periodically'
                                AND $periodically_condition)
                            OR (type_execution = 'once'
                                AND ((date_from >= '$start_date' AND date_to <= '$end_date')
                                    OR (date_from <= '$start_date' AND date_to >= '$end_date')
                                    OR (date_from <= '$start_date' AND date_to >= '$start_date')
                                    OR (date_from <= '$end_date' AND date_to >= '$end_date'))))
            ) tpdr
            ORDER BY tpdr.id";
    } else {
        $sql_downtime = "SELECT *
                        FROM tplanned_downtime tpd, tplanned_downtime_modules tpdm
                        WHERE (type_execution = 'periodically'
                                    AND $periodically_condition)
                                OR (type_execution = 'once'
                                    AND ((date_from >= '$start_date' AND date_to <= '$end_date')
                                        OR (date_from <= '$start_date' AND date_to >= '$end_date')
                                        OR (date_from <= '$start_date' AND date_to >= '$start_date')
                                        OR (date_from <= '$end_date' AND date_to >= '$end_date')))";
    }

    $downtimes = db_get_all_rows_sql($sql_downtime);
    if ($downtimes == false) {
        $downtimes = [];
    }

    return $downtimes;
}


/**
 * Get SLA of a module.
 *
 * @param int Agent module to calculate SLA
 * @param int Period to check the SLA compliance.
 * @param int Minimum data value the module in the right interval
 * @param int Maximum data value the module in the right interval. False will
 * ignore max value
 * @param int Beginning date of the report in UNIX time (current date by default).
 * @param array                                                                   $dayWeek  Array of days week to extract as array('monday' => false, 'tuesday' => true....), and by default is null.
 * @param string                                                                  $timeFrom Time in the day to start to extract in mysql format, by default null.
 * @param string                                                                  $timeTo   Time in the day to end to extract in mysql format, by default null.
 *
 * @return float SLA percentage of the requested module. False if no data were
 * found
 */
function reporting_get_agentmodule_sla_day($id_agent_module, $period=0, $min_value=1, $max_value=false, $date=0, $daysWeek=null, $timeFrom=null, $timeTo=null)
{
    global $config;

    if (empty($id_agent_module)) {
        return false;
    }

    // Initialize variables
    if (empty($date)) {
        $date = get_system_time();
    }

    if ($daysWeek === null) {
        $daysWeek = [];
    }

    // Limit date to start searching data
    $datelimit = ($date - $period);

    // Substract the not working time
    // Initialize the working time status machine ($wt_status)
    // Search the first data at worktime start
    $array_sla_report = reporting_get_agentmodule_sla_day_period($period, $date, $timeFrom, $timeTo);

    $period_reduced = $array_sla_report[0];
    $wt_status = $array_sla_report[1];
    $datelimit_increased = $array_sla_report[2];

    if ($period_reduced <= 0) {
        return false;
    }

    $wt_points = reporting_get_agentmodule_sla_working_timestamp($period, $date, $timeFrom, $timeTo);

    $search_in_history_db = db_search_in_history_db($datelimit);

    // Get interval data
    $sql = sprintf(
        'SELECT *
        FROM tagente_datos
        WHERE id_agente_modulo = %d
            AND utimestamp > %d
            AND utimestamp <= %d',
        $id_agent_module,
        $datelimit,
        $date
    );

    // Add the working times (mon - tue - wed ...) and from time to time
    $days = [];
    // Translate to mysql week days
    if ($daysWeek) {
        foreach ($daysWeek as $key => $value) {
            if (!$value) {
                if ($key == 'monday') {
                    $days[] = 2;
                }

                if ($key == 'tuesday') {
                    $days[] = 3;
                }

                if ($key == 'wednesday') {
                    $days[] = 4;
                }

                if ($key == 'thursday') {
                    $days[] = 5;
                }

                if ($key == 'friday') {
                    $days[] = 6;
                }

                if ($key == 'saturday') {
                    $days[] = 7;
                }

                if ($key == 'sunday') {
                    $days[] = 1;
                }
            }
        }
    }

    /*
        The not working time consideration is now doing in foreach loop above
        switch ($config["dbtype"]) {
        case "mysql":
        case "postgresql":
            if (count($days) > 0) {
                $sql .= ' AND DAYOFWEEK(FROM_UNIXTIME(utimestamp)) NOT IN (' . implode(',', $days) . ')';
            }
            if ($timeFrom < $timeTo) {
                $sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" AND TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
            }
            elseif ($timeFrom > $timeTo) {
                $sql .= ' AND (TIME(FROM_UNIXTIME(utimestamp)) >= "' . $timeFrom . '" OR TIME(FROM_UNIXTIME(utimestamp)) <= "'. $timeTo . '")';
            }
            break;
        case "oracle":
            break;
        }
    * */

    $sql .= ' ORDER BY utimestamp ASC';
    $interval_data = db_get_all_rows_sql($sql, $search_in_history_db);

    if ($interval_data === false) {
        $interval_data = [];
    }

    // Calculate planned downtime dates
    $downtime_dates = reporting_get_planned_downtimes_intervals($id_agent_module, $datelimit, $date);

    // Get previous data
    $previous_data = modules_get_previous_data($id_agent_module, ($datelimit + $datelimit_increased));

    if ($previous_data !== false) {
        $previous_data['utimestamp'] = ($datelimit + $datelimit_increased);
        array_unshift($interval_data, $previous_data);
    } else if (count($interval_data) > 0) {
        // Propagate undefined status to first time point
        $first_interval_time = array_shift($interval_data);
        $previous_point = ($datelimit + $datelimit_increased);
        $point = ($datelimit + $datelimit_increased);
        // Remove rebased points and substract time only on working time
        while ($wt_points[0] <= $first_interval_time['utimestamp']) {
            $point = array_shift($wt_points);
            if ($wt_status) {
                $period_reduced -= ($point - $previous_point);
            }

            $wt_status = !$wt_status;
            $previous_point = $point;
        }

        if ($wt_status) {
            $period_reduced -= ($first_interval_time['utimestamp'] - $point);
        }

        array_unshift($interval_data, $first_interval_time);
    }

    if (count($wt_points) < 2) {
        return false;
    }

    // Get next data
    $next_data = modules_get_next_data($id_agent_module, $date);

    if ($next_data !== false) {
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    } else if (count($interval_data) > 0) {
        // Propagate the last known data to the end of the interval
        $next_data = array_pop($interval_data);
        array_push($interval_data, $next_data);
        $next_data['utimestamp'] = $date;
        array_push($interval_data, $next_data);
    }

    if (count($interval_data) < 2) {
        return false;
    }

    // Set initial conditions
    $bad_period = 0;
    $first_data = array_shift($interval_data);

    // Do not count the empty start of an interval as 0
    if ($first_data['utimestamp'] != $datelimit) {
        $period = ($date - $first_data['utimestamp']);
    }

    $previous_utimestamp = $first_data['utimestamp'];
    if ((($max_value > $min_value and ($first_data['datos'] > $max_value or $first_data['datos'] < $min_value)))
        or ($max_value <= $min_value and $first_data['datos'] < $min_value)
    ) {
        $previous_status = 1;
        foreach ($downtime_dates as $date_dt) {
            if (($date_dt['date_from'] <= $previous_utimestamp) and ($date_dt['date_to'] >= $previous_utimestamp)) {
                $previous_status = 0;
            }
        }
    } else {
        $previous_status = 0;
    }

    foreach ($interval_data as $data) {
        // Test if working time is changed
        while ($wt_points[0] <= $data['utimestamp']) {
            $intermediate_point = array_shift($wt_points);
            if ($wt_status && ($previous_status == 1)) {
                $bad_period += ($intermediate_point - $previous_utimestamp);
            }

            $previous_utimestamp = $intermediate_point;
            $wt_status = !$wt_status;
        }

        // Increses bad_period only if it is working time
        if ($wt_status && ($previous_status == 1)) {
            $bad_period += ($data['utimestamp'] - $previous_utimestamp);
        }

        if (array_key_exists('datos', $data)) {
            // Re-calculate previous status for the next data
            if ((($max_value > $min_value and ($data['datos'] > $max_value or $data['datos'] < $min_value)))
                or ($max_value <= $min_value and $data['datos'] < $min_value)
            ) {
                $previous_status = 1;
                foreach ($downtime_dates as $date_dt) {
                    if (($date_dt['date_from'] <= $data['utimestamp']) and ($date_dt['date_to'] >= $data['utimestamp'])) {
                        $previous_status = 0;
                    }
                }
            } else {
                $previous_status = 0;
            }
        }

        $previous_utimestamp = $data['utimestamp'];
    }

    // Return the percentage of SLA compliance
    return (float) (100 - ($bad_period / $period_reduced) * 100);
}


function reporting_get_stats_servers()
{
    global $config;

    $server_performance = servers_get_performance();

    // Alerts table
    $table_srv = html_get_predefined_table();

    $table_srv->style[0] = $table_srv->style[2] = 'text-align: right; padding: 5px;';
    $table_srv->style[1] = $table_srv->style[3] = 'text-align: left; padding: 5px;';

    $tdata = [];
    '<span class="big_data">'.format_numeric($server_performance['total_local_modules']).'</span>';
    $tdata[0] = html_print_image('images/module.png', true, ['title' => __('Total running modules')]);
    $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_modules']).'</span>';
    $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['total_modules_rate'], 2).'</span>';
    $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

    $table_srv->rowclass[] = '';
    $table_srv->data[] = $tdata;

    $tdata = [];
    $tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
    $table_srv->colspan[count($table_srv->data)][0] = 4;
    $table_srv->rowclass[] = '';
    $table_srv->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image('images/database.png', true, ['title' => __('Local modules')]);
    $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_local_modules']).'</span>';
    $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['local_modules_rate'], 2).'</span>';
    $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

    $table_srv->rowclass[] = '';
    $table_srv->data[] = $tdata;

    if (isset($server_performance['total_network_modules'])) {
        $tdata = [];
        $tdata[0] = html_print_image('images/network.png', true, ['title' => __('Network modules')]);
        $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_network_modules']).'</span>';

        $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['network_modules_rate'], 2).'</span>';

        $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

        if ($server_performance['total_remote_modules'] > 10000 && !enterprise_installed()) {
            $tdata[4] = "<div id='remotemodulesmodal' class='publienterprise' title='Community version' style='text-align:left;'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
        } else {
            $tdata[4] = '&nbsp;';
        }

        $table_srv->rowclass[] = '';
        $table_srv->data[] = $tdata;
    }

    if (isset($server_performance['total_plugin_modules'])) {
        $tdata = [];
        $tdata[0] = html_print_image('images/plugin.png', true, ['title' => __('Plugin modules')]);
        $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_plugin_modules']).'</span>';

        $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['plugin_modules_rate'], 2).'</span>';
        $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

        $table_srv->rowclass[] = '';
        $table_srv->data[] = $tdata;
    }

    if (isset($server_performance['total_prediction_modules'])) {
        $tdata = [];
        $tdata[0] = html_print_image('images/chart_bar.png', true, ['title' => __('Prediction modules')]);
        $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_prediction_modules']).'</span>';

        $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['prediction_modules_rate'], 2).'</span>';
        $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

        $table_srv->rowclass[] = '';
        $table_srv->data[] = $tdata;
    }

    if (isset($server_performance['total_wmi_modules'])) {
        $tdata = [];
        $tdata[0] = html_print_image('images/wmi.png', true, ['title' => __('WMI modules')]);
        $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_wmi_modules']).'</span>';

        $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['wmi_modules_rate'], 2).'</span>';
        $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

        $table_srv->rowclass[] = '';
        $table_srv->data[] = $tdata;
    }

    if (isset($server_performance['total_web_modules'])) {
        $tdata = [];
        $tdata[0] = html_print_image('images/world.png', true, ['title' => __('Web modules')]);
        $tdata[1] = '<span class="big_data">'.format_numeric($server_performance['total_web_modules']).'</span>';

        $tdata[2] = '<span class="med_data">'.format_numeric($server_performance['web_modules_rate'], 2).'</span>';
        $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Ratio').': '.__('Modules by second')]).'/sec </span>';

        $table_srv->rowclass[] = '';
        $table_srv->data[] = $tdata;
    }

    $tdata = [];
    $tdata[0] = '<hr style="border: 0; height: 1px; background: #DDD">';
    $table_srv->colspan[count($table_srv->data)][0] = 4;
    $table_srv->rowclass[] = '';
    $table_srv->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image(
        'images/lightning_go.png',
        true,
        [
            'title' => __('Total events'),
        ]
    );
    $tdata[1] = '<span class="big_data" id="total_events">'.html_print_image('images/spinner.gif', true).'</span>';

        /*
            Hello there! :)
            We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
            You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
        */

    if ($system_events > 50000 && !enterprise_installed()) {
        $tdata[2] = "<div id='monitoreventsmodal' class='publienterprise' title='Community version' style='text-align:left'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
    } else {
        $tdata[3] = '&nbsp;';
    }

    $table_srv->colspan[count($table_srv->data)][1] = 2;
    $table_srv->rowclass[] = '';
    $table_srv->data[] = $tdata;

    $output = '<fieldset class="databox tactical_set">
                <legend>'.__('Server performance').'</legend>'.html_print_table($table_srv, true).'</fieldset>';

    $public_hash = get_parameter('hash', false);
    if ($public_hash === false) {
        $output .= '<script type="text/javascript">';
            $output .= '$(document).ready(function () {';
                $output .= 'var parameters = {};';
                $output .= 'parameters["page"] = "include/ajax/events";';
                $output .= 'parameters["total_events"] = 1;';

                $output .= '$.ajax({type: "GET",url: "/pandora_console/ajax.php",data: parameters,';
                    $output .= 'success: function(data) {';
                        $output .= '$("#total_events").text(data);';
                    $output .= '}';
                $output .= '});';
            $output .= '});';
        $output .= '</script>';
    } else {
        // This is for public link on the dashboard
        $sql_count_event = 'SELECT SQL_NO_CACHE COUNT(id_evento) FROM tevento  ';
        if ($config['event_view_hr']) {
            $sql_count_event .= 'WHERE utimestamp > (UNIX_TIMESTAMP(NOW()) - '.($config['event_view_hr'] * SECONDS_1HOUR).')';
        }

        $system_events = db_get_value_sql($sql_count_event);

        $output .= '<script type="text/javascript">';
            $output .= '$(document).ready(function () {';
                $output .= '$("#total_events").text("'.$system_events.'");';
            $output .= '});';
        $output .= '</script>';
    }

        return $output;
}


/**
 * Get all the template graphs a user can see.
 *
 * @param $id_user User id to check.
 * @param $only_names Wheter to return only graphs names in an associative array
 * or all the values.
 * @param $returnAllGroup Wheter to return graphs of group All or not.
 * @param $privileges Privileges to check in user group
 *
 * @return template graphs of a an user. Empty array if none.
 */
function reporting_template_graphs_get_user($id_user=0, $only_names=false, $returnAllGroup=true, $privileges='RR')
{
    global $config;

    if (!$id_user) {
        $id_user = $config['id_user'];
    }

    $groups = users_get_groups($id_user, $privileges, $returnAllGroup);

    $all_templates = db_get_all_rows_in_table('tgraph_template', 'name');
    if ($all_templates === false) {
        return [];
    }

    $templates = [];
    foreach ($all_templates as $template) {
        if (!in_array($template['id_group'], array_keys($groups))) {
            continue;
        }

        if ($template['id_user'] != $id_user && $template['private']) {
            continue;
        }

        if ($template['id_group'] > 0) {
            if (!isset($groups[$template['id_group']])) {
                continue;
            }
        }

        if ($only_names) {
            $templates[$template['id_graph_template']] = $template['name'];
        } else {
            $templates[$template['id_graph_template']] = $template;
            $templatesCount = db_get_value_sql('SELECT COUNT(id_gs_template) FROM tgraph_source_template WHERE id_template = '.$template['id_graph_template']);
            $templates[$template['id_graph_template']]['graphs_template_count'] = $templatesCount;
        }
    }

    return $templates;
}


/**
 * Get a human readable representation of the planned downtime date.
 *
 * @param array $planned_downtime Planned downtime row.
 *
 * @return string Representation of the date.
 */
function reporting_format_planned_downtime_dates($planned_downtime)
{
    $dates = '';

    if (!isset($planned_downtime) || !isset($planned_downtime['type_execution'])) {
        return '';
    }

    switch ($planned_downtime['type_execution']) {
        case 'once':
            $dates = date('Y-m-d H:i', $planned_downtime['date_from']).'&nbsp;'.__('to').'&nbsp;'.date('Y-m-d H:i', $planned_downtime['date_to']);
        break;

        case 'periodically':
            if (!isset($planned_downtime['type_periodicity'])) {
                return '';
            }

            switch ($planned_downtime['type_periodicity']) {
                case 'weekly':
                    $dates = __('Weekly:');
                    $dates .= '&nbsp;';
                    if ($planned_downtime['monday']) {
                        $dates .= __('Mon');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['tuesday']) {
                        $dates .= __('Tue');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['wednesday']) {
                        $dates .= __('Wed');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['thursday']) {
                        $dates .= __('Thu');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['friday']) {
                        $dates .= __('Fri');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['saturday']) {
                        $dates .= __('Sat');
                        $dates .= '&nbsp;';
                    }

                    if ($planned_downtime['sunday']) {
                        $dates .= __('Sun');
                        $dates .= '&nbsp;';
                    }

                    $dates .= '&nbsp;('.$planned_downtime['periodically_time_from'];
                    $dates .= '-'.$planned_downtime['periodically_time_to'].')';
                break;

                case 'monthly':
                    $dates = __('Monthly:').'&nbsp;';
                    $dates .= __('From day').'&nbsp;'.$planned_downtime['periodically_day_from'];
                    $dates .= '&nbsp;'.strtolower(__('To day')).'&nbsp;';
                    $dates .= $planned_downtime['periodically_day_to'];
                    $dates .= '&nbsp;('.$planned_downtime['periodically_time_from'];
                    $dates .= '-'.$planned_downtime['periodically_time_to'].')';
                break;
            }
        break;
    }

    return $dates;
}


/**
 * Get real period in SLA subtracting worktime period.
 * Get if is working in the first point
 * Get time between first point and
 *
 * @param int Period to check the SLA compliance.
 * @param int Date_end date end the sla compliace interval
 * @param int Working Time start
 * @param int Working Time end
 *
 * @return array (int fixed SLA period, bool inside working time)
 * found
 */
function reporting_get_agentmodule_sla_day_period($period, $date_end, $wt_start='00:00:00', $wt_end='23:59:59')
{
    $date_start = ($date_end - $period);
    // Converts to timestamp
    $human_date_end = date('H:i:s', $date_end);
    $human_date_start = date('H:i:s', $date_start);
    // Store into an array the points
    // "s" start SLA interval point
    // "e" end SLA interval point
    // "f" start worktime interval point (from)
    // "t" end worktime interval point (to)
    $tp = [
        's' => strtotime($human_date_start),
        'e' => strtotime($human_date_end),
        'f' => strtotime($wt_start),
        't' => strtotime($wt_end),
    ];

    asort($tp);
    $order = '';
    foreach ($tp as $type => $time) {
        $order .= $type;
    }

    $period_reduced = $period;
    $start_working = true;
    $datelimit_increased = 0;

    // Special case. If $order = "seft" and start time == end time it should be treated like "esft"
    if (($period > 0) and ($human_date_end == $human_date_start) and ($order == 'seft')) {
        $order = 'esft';
    }

    // Discriminates the cases depends what time point is higher than other
    switch ($order) {
        case 'setf':
        case 'etfs':
        case 'tfse':
        case 'fset':
            // Default $period_reduced
            // Default $start_working
            // Default $datelimit_increased
        break;

        case 'stef':
        case 'tefs':
        case 'fste':
            $period_reduced = ($period - ($tp['e'] - $tp['t']));
            // Default $start_working
            // Default $datelimit_increased
        break;

        case 'stfe':
        case 'estf':
        case 'tfes':
            $period_reduced = ($period - ($tp['f'] - $tp['t']));
            // Default $start_working
            // Default $datelimit_increased
        break;

        case 'tsef':
        case 'seft':
        case 'ftse':
        case 'efts':
            $period_reduced = -1;
            $start_working = false;
            // Default $datelimit_increased
        break;

        case 'tsfe':
        case 'etsf':
        case 'sfet':
            $period_reduced = ($period - ($tp['f'] - $tp['s']));
            $start_working = false;
            $datelimit_increased = ($tp['f'] - $tp['s']);
        break;

        case 'efst':
            $period_reduced = ($tp['t'] - $tp['s']);
            // Default $start_working
            // Default $datelimit_increased
        break;

        case 'fest':
            $period_reduced = (($tp['t'] - $tp['s']) + ($tp['e'] - $tp['f']));
            // Default $start_working
            // Default $datelimit_increased
        break;

        case 'tesf':
            $period_reduced = (SECONDS_1DAY - ($tp['f'] - $tp['t']));
            $start_working = false;
            $datelimit_increased = ($tp['f'] - $tp['s']);
        break;

        case 'sfte':
        case 'esft':
            $period_reduced = ($tp['t'] - $tp['f']);
            $start_working = false;
            $datelimit_increased = ($tp['f'] - $tp['s']);
        break;

        case 'ftes':
            $period_reduced = ($tp['t'] - $tp['f']);
            $start_working = false;
            $datelimit_increased = ($tp['f'] + SECONDS_1DAY - $tp['s']);
        break;

        case 'fets':
            $period_reduced = ($tp['e'] - $tp['f']);
            $start_working = false;
            $datelimit_increased = ($tp['f'] + SECONDS_1DAY - $tp['s']);
        break;

        default:
            // Default $period_reduced
            // Default $start_working
            // Default $datelimit_increased
        break;
    }

    return [
        $period_reduced,
        $start_working,
        $datelimit_increased,
    ];
}


/**
 * Get working time SLA in timestamp form. Get all items and discard previous not necessaries
 *
 * @param int Period to check the SLA compliance.
 * @param int Date_end date end the sla compliace interval
 * @param int Working Time start
 * @param int Working Time end
 *
 * @return array work time points
 * found
 */
function reporting_get_agentmodule_sla_working_timestamp($period, $date_end, $wt_start='00:00:00', $wt_end='23:59:59')
{
    $date_previous_day = ($date_end - SECONDS_1DAY);
    $wt = [];

    // Calculate posibles data points
    $relative_date_end = strtotime(date('H:i:s', $date_end));
    $relative_00_00_00 = strtotime('00:00:00');
    $relative_wt_start = (strtotime($wt_start) - $relative_00_00_00);
    $relative_wt_end = (strtotime($wt_end) - $relative_00_00_00);

    $absolute_previous_00_00_00 = ($date_previous_day - ($relative_date_end - $relative_00_00_00));
    $absolute_00_00_00 = ($date_end - ($relative_date_end - $relative_00_00_00));
    array_push($wt, $absolute_previous_00_00_00);
    if ($relative_wt_start < $relative_wt_end) {
        array_push($wt, ($absolute_previous_00_00_00 + $relative_wt_start));
        array_push($wt, ($absolute_previous_00_00_00 + $relative_wt_end));
        array_push($wt, ($absolute_00_00_00 + $relative_wt_start));
        array_push($wt, ($absolute_00_00_00 + $relative_wt_end));
    } else {
        array_push($wt, ($absolute_previous_00_00_00 + $relative_wt_end));
        array_push($wt, ($absolute_previous_00_00_00 + $relative_wt_start));
        array_push($wt, ($absolute_00_00_00 + $relative_wt_end));
        array_push($wt, ($absolute_00_00_00 + $relative_wt_start));
    }

    array_push($wt, ($absolute_00_00_00 + SECONDS_1DAY));

    // Discard outside period time points
    $date_start = ($date_end - $period);

    $first_time = array_shift($wt);
    while ($first_time < $date_start) {
        if (empty($wt)) {
            return $wt;
        }

        $first_time = array_shift($wt);
    }

    array_unshift($wt, $first_time);

    return $wt;
}


/**
 * Convert macros for value.
 * Item content:
 *      type
 *      id_agent
 *      id_agent_module
 *      agent_description
 *      agent_group
 *      agent_address
 *      agent_alias
 *      module_name
 *      module_description.
 *
 * @param array  $item  Data to replace in the macros.
 * @param string $label String check macros.
 *
 * @return string
 */
function reporting_label_macro($item, $label)
{
    if (preg_match('/_agent_/', $label)) {
        $label = str_replace(
            '_agent_',
            $item['agent_alias'],
            $label
        );
    }

    if (preg_match('/_agentdescription_/', $label)) {
        $label = str_replace(
            '_agentdescription_',
            $item['agent_description'],
            $label
        );
    }

    if (preg_match('/_agentgroup_/', $label)) {
        $label = str_replace(
            '_agentgroup_',
            $item['agent_group'],
            $label
        );
    }

    if (preg_match('/_address_/', $label)) {
        $label = str_replace(
            '_address_',
            $item['agent_address'],
            $label
        );
    }

    if (preg_match('/_module_/', $label)) {
        $label = str_replace(
            '_module_',
            $item['module_name'],
            $label
        );
    }

    if (preg_match('/_moduledescription_/', $label)) {
        $label = str_replace(
            '_moduledescription_',
            $item['module_description'],
            $label
        );
    }

    return $label;
}


/**
 * Convert macro in sql string to value
 *
 * @param array  $report
 * @param string $sql
 *
 * @return string
 */
function reporting_sql_macro(array $report, string $sql): string
{
    if (preg_match('/_timefrom_/', $sql)) {
        $sql = str_replace(
            '_timefrom_',
            $report['datetime'],
            $sql
        );
    }

    return $sql;
}


/**
 * @brief Calculates the SLA compliance value given an sla array
 *
 * @param  Array With keys time_ok, time_error, time_downtime and time_unknown
 * @return SLA Return the compliance value.
 */
function reporting_sla_get_compliance_from_array($sla_array)
{
    $time_compliance = ($sla_array['time_ok'] + $sla_array['time_unknown'] + $sla_array['time_downtime']);
    $time_total_working = ($time_compliance + $sla_array['time_error']);
    return $time_compliance == 0 ? 0 : (($time_compliance / $time_total_working) * 100);
}


/**
 * @brief Calculates if an SLA array is not init
 *
 * @param  Array With keys time_ok, time_error, time_downtime and time_unknown
 * @return boolean True if not init
 */
function reporting_sla_is_not_init_from_array($sla_array)
{
    if ($sla_array['time_total'] == 0) {
        return false;
    }

    return $sla_array['time_not_init'] == $sla_array['time_total'];
}


/**
 * @brief Calculates if an SLA array is ignored
 *
 * @param  Array With keys time_ok, time_error, time_downtime and time_unknown
 * @return boolean True if igonred time
 */
function reporting_sla_is_ignored_from_array($sla_array)
{
    if ($sla_array['time_total'] > 0) {
        return false;
    }

    return $sla_array['time_not_init'] == 0;
}


/**
 * @brief Given a period, get the SLA status of the period.
 *
 * @param Array An array with all times to calculate the SLA
 * @param int Priority mode. Setting this parameter to REPORT_PRIORITY_MODE_OK
 * and there is no critical in this period, return an OK without look for
 * not init, downtimes, unknown and others...
 *
 * @return integer Status
 */
function reporting_sla_get_status_period(
    $sla,
    $priority_mode=REPORT_PRIORITY_MODE_OK
) {
    if ($sla['time_error'] > 0) {
        return REPORT_STATUS_ERR;
    }

    if ($priority_mode == REPORT_PRIORITY_MODE_OK && $sla['time_ok'] > 0) {
        return REPORT_STATUS_OK;
    }

    if ($sla['time_out'] > 0) {
        return REPORT_STATUS_IGNORED;
    }

    if ($sla['time_downtime'] > 0) {
        return REPORT_STATUS_DOWNTIME;
    }

    if ($sla['time_unknown'] > 0) {
        return REPORT_STATUS_UNKNOWN;
    }

    if ($sla['time_not_init'] > 0) {
        return REPORT_STATUS_NOT_INIT;
    }

    if ($sla['time_ok'] > 0) {
        return REPORT_STATUS_OK;
    }

    return REPORT_STATUS_IGNORED;
}


/**
 * @brief Given a period, get the SLA status
 * of the period compare with sla_limit.
 *
 * @param Array An array with all times to calculate the SLA.
 * @param int Limit SLA pass for user.
 * Only used for monthly, weekly And hourly report.
 *
 * @return integer Status
 */
function reporting_sla_get_status_period_compliance(
    $sla,
    $sla_limit
) {
    global $config;

    $time_compliance = (
        $sla['time_ok'] + $sla['time_unknown'] + $sla['time_downtime']
    );

    $time_total_working = (
        $time_compliance + $sla['time_error']
    );

    $time_compliance = ($time_compliance == 0) ? 0 : (($time_compliance / $time_total_working) * 100);

    if ($sla['time_error'] > 0 && ($time_compliance < $sla_limit)) {
        return REPORT_STATUS_ERR;
    }

    if ($priority_mode == REPORT_PRIORITY_MODE_OK
        && $sla['time_ok'] > 0 && ($time_compliance >= $sla_limit)
    ) {
        return REPORT_STATUS_OK;
    }

    if ($sla['time_out'] > 0 && ($time_compliance < $sla_limit)) {
        return REPORT_STATUS_IGNORED;
    }

    if ($sla['time_downtime'] > 0 && ($time_compliance < $sla_limit)) {
        return REPORT_STATUS_DOWNTIME;
    }

    if ($sla['time_unknown'] > 0 && ($time_compliance < $sla_limit)) {
        return REPORT_STATUS_UNKNOWN;
    }

    if ($sla['time_not_init'] > 0 && ($time_compliance < $sla_limit)) {
        return REPORT_STATUS_NOT_INIT;
    }

    if ($sla['time_ok'] > 0 && ($time_compliance >= $sla_limit)) {
        return REPORT_STATUS_OK;
    }

    return REPORT_STATUS_IGNORED;
}


/**
 * @brief Translate the status to the color to graph_sla_slicebar function
 *
 * @param  int The status in number
 * @return integer The index of color array to graph_sla_slicebar function
 */
function reporting_translate_sla_status_for_graph($status)
{
    $sts = [
        REPORT_STATUS_ERR      => 3,
        REPORT_STATUS_OK       => 1,
        REPORT_STATUS_UNKNOWN  => 4,
        REPORT_STATUS_NOT_INIT => 6,
        REPORT_STATUS_DOWNTIME => 5,
        REPORT_STATUS_IGNORED  => 7,
    ];
    return $sts[$status];
}


/**
 * Print header to report pdf and add page break
 *
 * @param string $title       Title of report.
 * @param string $description Description of report.
 *
 * @return html Return table of header.
 */
function reporting_header_table_for_pdf($title='', $description='')
{
    $result_pdf .= '<pagebreak>';
    $result_pdf .= '<table class="header_table databox">';
    $result_pdf .= '<thead class="header_tr"><tr>';
    $result_pdf .= '<th class="th_first" colspan="2">';
    $result_pdf .= $title;
    $result_pdf .= '</th><th style="font-size: 15px;" align="right">';
    $result_pdf .= '</th></tr><tr><th colspan="3" class="th_description">';
    $result_pdf .= $description;
    $result_pdf .= '</th></tr></thead></table>';

    return $result_pdf;
}


/**
 * Build the required data to build network traffic top N report
 *
 * @param int Period (time window).
 * @param array Information about the item of report.
 * @param bool Pdf or not
 *
 * @return array With report presentation info and report data.
 */
function reporting_nt_top_n_report($period, $content, $pdf)
{
    $return = [];
    $return['type'] = 'nt_top_n';
    $return['title'] = $content['name'];
    $return['description'] = $content['description'];

    // Get the data sent and received
    $return['data'] = [];
    $start_time = ($period['datetime'] - (int) $content['period']);
    $return['data']['send'] = network_matrix_get_top(
        $content['top_n_value'],
        true,
        $start_time,
        $period['datetime']
    );
    $return['data']['recv'] = network_matrix_get_top(
        $content['top_n_value'],
        false,
        $start_time,
        $period['datetime']
    );
    return $return;
}
