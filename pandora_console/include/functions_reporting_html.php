<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
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

require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_events.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_users.php';
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('include/functions_inventory.php');
require_once $config['homedir'].'/include/functions_inventory.php';
require_once $config['homedir'].'/include/functions_forecast.php';
require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_netflow.php';


/**
 * Header function.
 *
 * @param object  $table    Table.
 * @param boolean $mini     Mini.
 * @param string  $title    Title.
 * @param string  $subtitle Subtitle.
 * @param integer $period   Period.
 * @param string  $date     Date.
 * @param string  $from     From.
 * @param string  $to       To.
 * @param string  $label    Label.
 *
 * @return void
 */
function reporting_html_header(
    &$table,
    $mini,
    $title,
    $subtitle,
    $period,
    $date,
    $from,
    $to,
    $label=''
) {
    global $config;

    if ($mini) {
        $sizh = '';
        $sizhfin = '';
    } else {
        $sizh = '<h4>';
        $sizhfin = '</h4>';
    }

    $date_text = '';
    if (!empty($date)) {
        $date_text = date($config['date_format'], $date);
    } else if (!empty($from) && !empty($to)) {
        $date_text = '('.human_time_description_raw($period).') '.__('From:').' '.date($config['date_format'], $from).'<br />'.__('To:').' '.date($config['date_format'], $to);
    } else if ($period > 0) {
        $date_text = human_time_description_raw($period);
    } else if ($period === 0) {
        $date_text = __('Last data');
    }

    $data = [];
    if (empty($subtitle) && (empty($date_text))) {
        $title = $sizh.$title.$sizhfin;
        $data[] = $title;
        $table->colspan[0][0] = 3;
    } else if (empty($subtitle)) {
        $data[] = $sizh.$title.$sizhfin;
        $data[] = "<div class='right'>".$sizh.$date_text.$sizhfin.'</div>';
        $table->colspan[0][1] = 2;
    } else if (empty($date_text)) {
        $data[] = $sizh.$title.$sizhfin;
        $data[] = $sizh.$subtitle.$sizhfin;
        $table->colspan[0][1] = 2;
    } else {
        $title = $sizh.$title;
        if ($label != '') {
            $title .= '<br >'.__('Label: ').$label;
        }

        $data[] = $title.$sizhfin;
        $data[] = $sizh.$subtitle.$sizhfin;
        $data[] = "<div class='right'>".$sizh.$date_text.$sizhfin.'</div>';
    }

    array_push($table->data, $data);
}


function html_do_report_info($report)
{
    global $config;

    if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
        $background_color = '#222';
    } else {
        $background_color = '#f5f5f5';
    }

    $date_today = date($config['date_format']);

    $date_today = preg_split('/[\s,]+/', io_safe_output($date_today));

    $date_today = __($date_today[0]).' '.$date_today[1].' '.$date_today[2].' '.$date_today[3].' '.$date_today[4];
    $html = '<div class="report_info" style="background: '.$background_color.'"><table>
            <tr>
                <td><b>'.__('Generated').': </b></td><td>'.$date_today.'</td>
            </tr>
            <tr>
                <td><b>'.__('Report date').': </b></td>';

    $date_before = date($config['date_format'], $report['datetime']);
    $date_before = preg_split('/[\s,]+/', io_safe_output($date_before));
    $date_before = __($date_before[0]).' '.$date_before[1].' '.$date_before[2].' '.$date_before[3].' '.$date_before[4];
    if (is_numeric($report['datetime']) && is_numeric($report['period']) && ($report['period'] != 0)) {
        $html .= '<td>'.__('From').' <b>'.date($config['date_format'], ($report['datetime'] - $report['period'])).'</b></td>';
        $html .= '<td>'.__('to').' <b>'.date($config['date_format'], $report['datetime']).'</b></td>';
    } else {
        $html .= '<td>'.__('Items period before').' <b>'.$date_before.'</b></td>';
    }

    $html .= '</tr>
            <tr>
                <td valign="top"><b>'.__('Description').': </b></td><td>'.htmlspecialchars($report['description']).'</td>
            </tr>
        </table>'.'</div>';

    echo $html;
}


/**
 * Print html report.
 *
 * @param array   $report      Info.
 * @param boolean $mini        Type.
 * @param integer $report_info Show info.
 *
 * @return array
 */
function reporting_html_print_report($report, $mini=false, $report_info=1)
{
    if ($report_info == 1) {
        html_do_report_info($report);
    }

    foreach ($report['contents'] as $key => $item) {
        $table = new stdClass();
        $table->size = [];
        $table->style = [];
        $table->width = '100%';
        $table->class = 'databox filters';
        $table->rowclass = [];
        $table->rowclass[0] = 'datos5';
        $table->data = [];
        $table->head = [];
        $table->colspan = [];
        $table->rowstyle = ['background-color: #686868'];

        if (isset($item['label']) && $item['label'] != '') {
            $id_agent = $item['id_agent'];
            $id_agent_module = $item['id_agent_module'];

            // Add macros name.
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
                'type'               => $item['type'],
                'id_agent'           => $id_agent,
                'id_agent_module'    => $id_agent_module,
                'agent_description'  => $agent_description,
                'agent_group'        => $agent_group,
                'agent_address'      => $agent_address,
                'agent_alias'        => $agent_alias,
                'module_name'        => $module_name,
                'module_description' => $module_description,
            ];

            $label = reporting_label_macro(
                $items_label,
                ($item['label'] ?? '')
            );
        } else {
            $label = '';
        }

        reporting_html_header(
            $table,
            $mini,
            $item['title'],
            $item['subtitle'],
            $item['date']['period'],
            $item['date']['date'],
            $item['date']['from'],
            $item['date']['to'],
            $label
        );

        $table->data['description_row']['description'] = $item['description'];

        if ($item['type'] == 'event_report_agent'
            || $item['type'] == 'event_report_group'
            || $item['type'] == 'event_report_module'
        ) {
            $table->data['count_row']['count'] = 'Total events: '.$item['total_events'];
        }

        $table->colspan['description_row']['description'] = 3;

        switch ($item['type']) {
            case 'availability':
            default:
                reporting_html_availability($table, $item);
            break;

            case 'event_report_log':
                reporting_html_log($table, $item);
            break;

            case 'permissions_report':
                reporting_html_permissions($table, $item);
            break;

            case 'availability_graph':
                reporting_html_availability_graph($table, $item);
            break;

            case 'general':
                reporting_html_general($table, $item);
            break;

            case 'sql':
                reporting_html_sql($table, $item);
            break;

            case 'simple_baseline_graph':
            case 'simple_graph':
                reporting_html_graph($table, $item);
            break;

            case 'custom_graph':
                reporting_html_graph($table, $item);
            break;

            case 'text':
                reporting_html_text($table, $item);
            break;

            case 'url':
                reporting_html_url($table, $item, $key);
            break;

            case 'max_value':
                reporting_html_max_value($table, $item, $mini);
            break;

            case 'avg_value':
                reporting_html_avg_value($table, $item, $mini);
            break;

            case 'increment':
                reporting_html_increment($table, $item);
            break;

            case 'min_value':
                reporting_html_min_value($table, $item, $mini);
            break;

            case 'sumatory':
                reporting_html_sum_value($table, $item, $mini);
            break;

            case 'agent_configuration':
                reporting_html_agent_configuration($table, $item);
            break;

            case 'projection_graph':
                reporting_html_graph($table, $item);
            break;

            case 'prediction_date':
                reporting_html_prediction_date($table, $item, $mini);
            break;

            case 'netflow_area':
            case 'netflow_data':
            case 'netflow_summary':
            case 'netflow_top_N':
                reporting_html_graph($table, $item);
            break;

            case 'monitor_report':
                reporting_html_monitor_report($table, $item, $mini);
            break;

            case 'sql_graph_vbar':
            case 'sql_graph_hbar':
            case 'sql_graph_pie':
                reporting_html_sql_graph($table, $item);
            break;

            case 'alert_report_group':
            case 'alert_report_module':
            case 'alert_report_agent':
                reporting_html_alert_report($table, $item);
            break;

            case 'network_interfaces_report':
                reporting_html_network_interfaces_report($table, $item);
            break;

            case 'custom_render':
                reporting_html_custom_render($table, $item);
            break;

            case 'group_configuration':
                reporting_html_group_configuration($table, $item);
            break;

            case 'historical_data':
                reporting_html_historical_data($table, $item);
            break;

            case 'database_serialized':
                reporting_html_database_serialized($table, $item);
            break;

            case 'last_value':
                reporting_html_last_value($table, $item);
            break;

            case 'group_report':
                reporting_html_group_report($table, $item);
            break;

            case 'exception':
                reporting_html_exception($table, $item);
            break;

            case 'agent_module':
                reporting_html_agent_module($table, $item);
            break;

            case 'agent_module_status':
                reporting_html_agent_module_status($table, $item);
            break;

            case 'alert_report_actions':
                reporting_html_alert_report_actions($table, $item);
            break;

            case 'agents_inventory':
                reporting_html_agents_inventory($table, $item);
            break;

            case 'modules_inventory':
                reporting_html_modules_inventory($table, $item);
            break;

            case 'inventory':
                reporting_html_inventory($table, $item);
            break;

            case 'inventory_changes':
                reporting_html_inventory_changes($table, $item);
            break;

            case 'IPAM_network':
                reporting_enterprise_html_ipam($table, $item, $mini);
            break;

            case 'agent_detailed_event':
            case 'event_report_agent':
                reporting_html_event_report_agent($table, $item);
            break;

            case 'event_report_module':
                reporting_html_event_report_module($table, $item);
            break;

            case 'event_report_group':
                reporting_html_event_report_group($table, $item);
            break;

            case 'top_n':
                reporting_html_top_n($table, $item);
            break;

            case 'SLA':
                reporting_html_SLA($table, $item, $mini);
            break;

            case 'SLA_monthly':
                reporting_enterprise_html_SLA_monthly($table, $item, $mini);
            break;

            case 'SLA_weekly':
                reporting_enterprise_html_SLA_weekly($table, $item, $mini);
            break;

            case 'SLA_hourly':
                reporting_enterprise_html_SLA_hourly($table, $item, $mini);
            break;

            case 'SLA_services':
                reporting_enterprise_html_SLA_services($table, $item, $mini);
            break;

            case 'module_histogram_graph':
                reporting_enterprise_html_module_histogram_graph(
                    $table,
                    $item,
                    $mini
                );
            break;

            case 'ncm':
                reporting_html_ncm_config($table, $item);
            break;
        }

        if ($item['type'] == 'agent_module') {
            echo '<div class="overflow w100p">';
        }

        html_print_table($table);

        if ($item['type'] == 'agent_module') {
            echo '</div>';
        }
    }
}


/**
 * Function to print to HTML SLA report.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $mini  If true or false letter mini.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_SLA($table, $item, $mini, $pdf=0)
{
    $return_pdf = '';

    $style = db_get_value(
        'style',
        'treport_content',
        'id_rc',
        $item['id_rc']
    );

    $style = json_decode(io_safe_output($style), true);

    global $config;

    $interval_description = ($config['interval_description'] ?? null);

    if ($mini === true) {
        $font_size = '1.5em';
    } else {
        $font_size = $config['font_size_item_report'].'em';
    }

    $metaconsole_on = is_metaconsole();
    if ($metaconsole_on === true) {
        $src = '../../';
    } else {
        $src = $config['homeurl'];
    }

    if (empty($item['failed']) === false) {
        $table->colspan['sla']['cell'] = 3;
        $table->data['sla']['cell'] = $item['failed'];
    } else {
        if (empty($item['planned_downtimes']) === false) {
            $downtimes_table = reporting_html_planned_downtimes_table(
                $item['planned_downtimes']
            );

            if (empty($downtimes_table) === false) {
                $table->colspan['planned_downtime']['cell'] = 3;
                $table->data['planned_downtime']['cell'] = $downtimes_table;
            }
        }

        if (isset($item['data']) === true) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->class = 'info_table';

            $table1->align = [];
            $table1->align[0] = 'left';
            $table1->align[1] = 'left';
            $table1->align[2] = 'right';
            $table1->align[3] = 'right';
            $table1->align[4] = 'right';
            $table1->align[5] = 'right';

            $table1->data = [];

            $table1->head = [];
            $table1->head[0] = __('Agent');
            $table1->head[1] = __('Module');
            $table1->head[2] = __('Max/Min Values');
            $table1->head[3] = __('SLA Limit');
            $table1->head[4] = __('SLA Compliance');
            $table1->head[5] = __('Status');

            $table1->headstyle = [];
            $table1->headstyle[0] = 'text-align: left';
            $table1->headstyle[1] = 'text-align: left';
            $table1->headstyle[2] = 'text-align: right';
            $table1->headstyle[3] = 'text-align: right';
            $table1->headstyle[4] = 'text-align: right';
            $table1->headstyle[5] = 'text-align: right';

            $table1->style = [];
            $table1->style[0] = 'page-break-before: always;';

            $table1->rowstyle = [];

            // Second_table for time globals.
            $table2 = new stdClass();
            $table2->width = '99%';
            $table2->class = 'info_table';

            $table2->align = [];
            $table2->align[0] = 'left';
            $table2->align[1] = 'left';
            $table2->align[2] = 'right';
            $table2->align[3] = 'right';
            $table2->align[4] = 'right';
            $table2->align[5] = 'right';
            $table2->align[6] = 'right';

            $table2->data = [];

            $table2->head = [];
            $table2->head[0] = __('Global Time');
            $table2->head[1] = __('Time Total');
            $table2->head[2] = __('Time Failed');
            $table2->head[3] = __('Time OK');
            $table2->head[4] = __('Time Unknown');
            $table2->head[5] = __('Time Not Init');
            $table2->head[6] = __('Downtime');

            $table2->headstyle = [];
            $table2->headstyle[0] = 'text-align: left';
            $table2->headstyle[1] = 'text-align: left';
            $table2->headstyle[2] = 'text-align: right';
            $table2->headstyle[3] = 'text-align: right';
            $table2->headstyle[4] = 'text-align: right';
            $table2->headstyle[5] = 'text-align: right';
            $table2->headstyle[6] = 'text-align: right';

            // Third_table for time globals.
            $table3 = new stdClass();
            $table3->width = '99%';
            $table3->class = 'info_table';

            $table3->align = [];
            $table3->align[0] = 'left';
            $table3->align[1] = 'right';
            $table3->align[2] = 'right';
            $table3->align[3] = 'right';
            $table3->align[4] = 'right';
            $table3->align[5] = 'right';
            $table3->align[6] = 'right';

            $table3->data = [];

            $table3->head = [];
            $table3->head[0] = __('Checks Time');
            $table3->head[1] = __('Checks Total');
            $table3->head[2] = __('Checks Failed');
            $table3->head[3] = __('Checks OK');
            $table3->head[4] = __('Checks Unknown');

            $table3->headstyle = [];
            $table3->headstyle[0] = 'text-align: left';
            $table3->headstyle[1] = 'text-align: right';
            $table3->headstyle[2] = 'text-align: right';
            $table3->headstyle[3] = 'text-align: right';
            $table3->headstyle[4] = 'text-align: right';
            $table3->headstyle[5] = 'text-align: right';

            foreach ($item['data'] as $sla) {
                if (isset($sla) === true) {
                    // First_table.
                    $row = [];
                    $row[] = $sla['agent'];
                    if ((bool) $sla['compare'] === false) {
                        $row[] = $sla['module'];
                    } else {
                        $row[] = $sla['module'].' ('.__('24 x 7').')';
                    }

                    if (is_numeric($sla['dinamic_text'])) {
                        $row[] = sla_truncate(
                            $sla['max'],
                            $config['graph_precision']
                        ).' / '.sla_truncate(
                            $sla['min'],
                            $config['graph_precision']
                        );
                    } else {
                        $row[] = $sla['dinamic_text'];
                    }

                    $row[] = round($sla['sla_limit'], 2).'%';

                    if (reporting_sla_is_not_init_from_array($sla)) {
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_NOTINIT.';">'.__('N/A').'</span>';
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_NOTINIT.';">'.__('Not init').'</span>';
                    } else if (reporting_sla_is_ignored_from_array($sla)) {
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_IGNORED.';">'.__('N/A').'</span>';
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_IGNORED.';">'.__('No data').'</span>';
                        // Normal calculation.
                    } else if ($sla['sla_status']) {
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_NORMAL.';">'.sla_truncate($sla['sla_value'], $config['graph_precision']).'%</span>';
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_NORMAL.';">'.__('OK').'</span>';
                    } else {
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_CRITICAL.';">'.sla_truncate($sla['sla_value'], $config['graph_precision']).'%</span>';
                        $row[] = '<span style="font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_CRITICAL.';">'.__('Fail').'</span>';
                    }

                    // Second table for time globals.
                    $row2 = [];
                    if ((bool) $sla['compare'] === false) {
                        $row2[] = $sla['agent'].' -- ['.$sla['module'].']';
                    } else {
                        $name_agent_module = $sla['agent'];
                        $name_agent_module .= ' -- ['.$sla['module'];
                        $name_agent_module .= ' ('.__('24 x 7').')]';
                        $row2[] = $name_agent_module;
                    }

                    if ($sla['time_total'] != 0) {
                        $row2[] = human_time_description_raw(
                            $sla['time_total'],
                            false,
                            $interval_description
                        );
                    } else {
                        $row2[] = '--';
                    }

                    if ($sla['time_error'] != 0) {
                        $row2[] = '<span style="color: '.COL_CRITICAL.';">'.human_time_description_raw(
                            $sla['time_error'],
                            true,
                            $interval_description
                        ).'</span>';
                    } else {
                        $row2[] = '--';
                    }

                    if ($sla['time_ok'] != 0) {
                        $row2[] = '<span style="color: '.COL_NORMAL.';">'.human_time_description_raw(
                            $sla['time_ok'],
                            true,
                            $interval_description
                        ).'</span>';
                    } else {
                        $row2[] = '--';
                    }

                    if ($sla['time_unknown'] != 0) {
                        $row2[] = '<span style="color: '.COL_UNKNOWN.';">'.human_time_description_raw(
                            $sla['time_unknown'],
                            true,
                            $interval_description
                        ).'</span>';
                    } else {
                        $row2[] = '--';
                    }

                    if ($sla['time_not_init'] != 0) {
                        $row2[] = '<span style="color: '.COL_NOTINIT.';">'.human_time_description_raw(
                            $sla['time_not_init'],
                            true,
                            $interval_description
                        ).'</span>';
                    } else {
                        $row2[] = '--';
                    }

                    if ($sla['time_downtime'] != 0) {
                        $row2[] = '<span style="color: '.COL_DOWNTIME.';">'.human_time_description_raw(
                            $sla['time_downtime'],
                            true,
                            $interval_description
                        ).'</span>';
                    } else {
                        $row2[] = '--';
                    }

                    // Third table for checks globals.
                    $row3 = [];
                    if ((bool) $sla['compare'] === false) {
                        $row3[] = $sla['agent'].' -- ['.$sla['module'].']';
                    } else {
                        $name_agent_module = $sla['agent'];
                        $name_agent_module .= ' -- ['.$sla['module'];
                        $name_agent_module .= ' ('.__('24 x 7').')]';
                        $row3[] = $name_agent_module;
                    }

                    $row3[] = $sla['checks_total'];
                    $row3[] = '<span style="color: '.COL_CRITICAL.';">'.$sla['checks_error'].'</span>';
                    $row3[] = '<span style="color: '.COL_NORMAL.';">'.$sla['checks_ok'].'</span>';
                    $row3[] = '<span style="color: '.COL_UNKNOWN.';">'.$sla['checks_unknown'].'</span>';

                    $table1->rowstyle[] = 'page-break-before: always;';
                    $table1->data[] = $row;
                    $table2->data[] = $row2;
                    $table3->data[] = $row3;
                }
            }

            if ($pdf === 0) {
                $table->colspan['sla']['cell'] = 2;
                $table->data['sla']['cell'] = html_print_table(
                    $table1,
                    true
                );
                $table->colspan['time_global']['cell'] = 2;
                $table->data['time_global']['cell'] = html_print_table(
                    $table2,
                    true
                );
                $table->colspan['checks_global']['cell'] = 2;
                $table->data['checks_global']['cell'] = html_print_table(
                    $table3,
                    true
                );
            } else {
                // $table1->title = $item['title'];
                // $table1->titleclass = 'title_table_pdf';
                // $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table1,
                    true
                );
                // $table2->title = $item['title'];
                // $table2->titleclass = 'title_table_pdf';
                // $table2->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table2,
                    true
                );
                // $table3->title = $item['title'];
                // $table3->titleclass = 'title_table_pdf';
                // $table3->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table3,
                    true
                );
            }
        } else {
            $table->colspan['error']['cell'] = 3;
            $table->data['error']['cell'] = __(
                'There are no Agent/Modules defined'
            );
        }

        if (empty($item['charts']) === false) {
            $table1 = new stdClass();
            $table1->width = '100%';
            $table1->size = [];
            $table1->size[0] = '10%';
            if ($pdf !== 0) {
                $table1->size[0] = '20%';
            }

            $table1->data = [];

            foreach ($item['charts'] as $chart) {
                $name_agent_module = $chart['agent'];
                $name_agent_module .= '<br />';
                $name_agent_module .= $chart['module'];
                if ((bool) $chart['compare'] === true) {
                    $name_agent_module .= ' ('.__('24 x 7').')';
                }

                $table1->data[] = [
                    $name_agent_module,
                    $chart['chart'],
                ];
            }

            if ($pdf === 0) {
                $table->colspan['charts']['cell'] = 2;
                $table->data['charts']['cell'] = html_print_table(
                    $table1,
                    true
                );
            } else {
                // $table1->title = $item['title'];
                // $table1->titleclass = 'title_table_pdf';
                // $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table1,
                    true
                );
            }

            // Table_legend_graphs.
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->data = [];
            $table1->size = [];
            $table1->size[0] = '2%';
            $table1->data[0][0] = '<img src ="'.$src.'images/square_green.png">';
            $table1->size[1] = '14%';
            $table1->data[0][1] = '<span>'.__('OK').'</span>';

            $table1->size[2] = '2%';
            $table1->data[0][2] = '<img src ="'.$src.'images/square_red.png">';
            $table1->size[3] = '14%';
            $table1->data[0][3] = '<span>'.__('Critical').'</span>';

            $table1->size[4] = '2%';
            $table1->data[0][4] = '<img src ="'.$src.'images/square_gray.png">';
            $table1->size[5] = '14%';
            $table1->data[0][5] = '<span>'.__('Unknow').'</span>';

            $table1->size[6] = '2%';
            $table1->data[0][6] = '<img src ="'.$src.'images/square_blue.png">';
            $table1->size[7] = '14%';
            $table1->data[0][7] = '<span>'.__('Not Init').'</span>';

            $table1->size[8] = '2%';
            $table1->data[0][8] = '<img src ="'.$src.'images/square_violet.png">';
            $table1->size[9] = '14%';
            $table1->data[0][9] = '<span>'.__('Downtimes').'</span>';

            $table1->size[10] = '2%';
            $table1->data[0][10] = '<img src ="'.$src.'images/square_light_gray.png">';
            $table1->size[11] = '15%';
            $table1->data[0][11] = '<span>'.__('Scheduled Downtime').'</span>';

            if ($pdf === 0) {
                $table->colspan['legend']['cell'] = 2;
                $table->data['legend']['cell'] = html_print_table(
                    $table1,
                    true
                );
            } else {
                $return_pdf .= html_print_table(
                    $table1,
                    true
                );
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Function to print html report top N.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_top_n($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (!empty($item['failed'])) {
        if ($pdf !== 0) {
            $return_pdf .= $item['failed'];
        } else {
            $table->colspan['top_n']['cell'] = 3;
            $table->data['top_n']['cell'] = $item['failed'];
        }
    } else {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->class = 'info_table';

        $table1->align = [];
        $table1->align[0] = 'left';
        $table1->align[1] = 'left';
        $table1->align[2] = 'right';

        $table1->data = [];

        $table1->headstyle = [];
        $table1->headstyle[0] = 'text-align: left';
        $table1->headstyle[1] = 'text-align: left';
        $table1->headstyle[2] = 'text-align: right';

        $table1->head = [];
        $table1->head[0] = __('Agent');
        $table1->head[1] = __('Module');
        $table1->head[2] = __('Value');

        foreach ($item['data'] as $top) {
            $row = [];
            $row[] = $top['agent'];
            $row[] = $top['module'];
            $row[] = $top['formated_value'];
            $table1->data[] = $row;
        }

        $table->colspan['top_n']['cell'] = 3;
        if ($pdf !== 0) {
            $table1->title = $item['title'];
            $table1->titleclass = 'title_table_pdf';
            $table1->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table($table1, true);
        } else {
            $table->data['top_n']['cell'] = html_print_table($table1, true);
        }

        if (empty($item['charts']['pie']) === false) {
            if ($pdf !== 0) {
                $return_pdf .= $item['charts']['pie'];
            } else {
                $table->colspan['char_pie'][0] = 2;
                $table->data['char_pie'][0] = $item['charts']['pie'];
            }
        }

        if (empty($item['charts']['bars']) === false) {
            if ($pdf !== 0) {
                $return_pdf .= $item['charts']['bars'];
            } else {
                $table->data['char_pie'][1] = $item['charts']['bars'];
            }
        }

        if (empty($item['resume']) === false) {
            $table1 = new stdClass();
            $table1->width = '99%';

            $table1->align = [];
            $table1->align[0] = 'center';
            $table1->align[1] = 'center';
            $table1->align[2] = 'center';

            $table1->data = [];

            $table1->headstyle = [];
            $table1->headstyle[0] = 'text-align: center';
            $table1->headstyle[1] = 'text-align: center';
            $table1->headstyle[2] = 'text-align: center';

            $table1->head = [];
            $table1->head[0] = __('Min Value');
            $table1->head[1] = __('Average Value');
            $table1->head[2] = __('Max Value');

            $row = [];
            $row[] = $item['resume']['min']['formated_value'];
            $row[] = $item['resume']['avg']['formated_value'];
            $row[] = $item['resume']['max']['formated_value'];
            $table1->data[] = $row;

            if ($pdf !== 0) {
                $table1->title = $item['title'];
                $table1->titleclass = 'title_table_pdf';
                $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table($table1, true);
            } else {
                $table->colspan['resume']['cell'] = 3;
                $table->data['resume']['cell'] = html_print_table($table1, true);
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


function reporting_html_event_report_group($table, $item, $pdf=0)
{
    global $config;

    $show_extended_events = $item['show_extended_events'];
    $show_custom_data = (bool) $item['show_custom_data'];

    if ($item['total_events']) {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->class = 'info_table';

        $table1->align = [];
        $table1->align[0] = 'left';
        if ($item['show_summary_group']) {
            $table1->align[3] = 'left';
        } else {
            $table1->align[2] = 'left';
        }

        $table1->data = [];

        $table1->head = [];
        if ($item['show_summary_group']) {
            $table1->head[0] = __('Status');
            $table1->head[1] = __('Type');
            $table1->head[2] = __('Count');
            $table1->head[3] = __('Name');
            $table1->head[4] = __('Agent');
            $table1->head[5] = __('Severity');
            $table1->head[6] = __('Val. by');
            $table1->head[7] = __('Timestamp');
        } else {
            $table1->head[0] = __('Status');
            $table1->head[1] = __('Type');
            $table1->head[2] = __('Name');
            $table1->head[3] = __('Agent');
            $table1->head[4] = __('Severity');
            $table1->head[5] = __('Val. by');
            $table1->head[6] = __('Timestamp');
        }

        if ($show_custom_data === true) {
            $table1->head[8] = __('Custom data');
        }

        foreach ($item['data'] as $k => $event) {
            $data = [];

            // Colored box.
            switch ($event['estado']) {
                case 1:
                    $img_st = 'images/tick.png';
                    $title_st = __('Event validated');
                break;

                case 2:
                    $img_st = 'images/hourglass.png';
                    $title_st = __('Event in process');
                break;

                default:
                case 0:
                    $img_st = 'images/star.png';
                    $title_st = __('New event');
                break;
            }

            $data[] = html_print_image(
                $img_st,
                true,
                [
                    'class' => 'image_status invert_filter',
                    'width' => 16,
                    'title' => $title_st,
                    'id'    => 'status_img_'.$event['id_evento'],
                ]
            );

            if ($pdf) {
                $data[] = events_print_type_img_pdf($event['event_type'], true);
            } else {
                $data[] = events_print_type_img($event['event_type'], true);
            }

            if ($item['show_summary_group']) {
                $data[] = $event['event_rep'];
            }

            $data[] = ui_print_truncate_text(
                io_safe_output($event['evento']),
                140,
                false,
                true
            );

            if (empty($event['alias']) === false) {
                $alias = $event['alias'];
                if (is_metaconsole() === true) {
                    $alias = '('.$event['server_name'].') '.$event['alias'];
                }

                $data[] = $alias;
            } else {
                $data[] = __('%s System', get_product_name());
            }

            $data[] = get_priority_name($event['criticity']);
            if (empty($event['id_usuario']) === true
                && $event['estado'] == EVENT_VALIDATE
            ) {
                $data[] = '<i>'.__('System').'</i>';
            } else {
                $user_name = db_get_value(
                    'fullname',
                    'tusuario',
                    'id_user',
                    $event['id_usuario']
                );
                $data[] = io_safe_output($user_name);
            }

            if ($item['show_summary_group']) {
                $data[] = '<font class="font_6pt">'.date($config['date_format'], $event['timestamp_last']).'</font>';
            } else {
                $data[] = '<font class="font_6pt">'.date($config['date_format'], strtotime($event['timestamp'])).'</font>';
            }

            if ($show_custom_data === true) {
                $custom_data_text = '';
                if (empty($event['custom_data']) === false) {
                    $custom_data = json_decode($event['custom_data'], true);
                    if (empty($custom_data) === false) {
                        foreach ($custom_data as $key => $value) {
                            $custom_data_text .= $key.' = '.$value.'<br>';
                        }
                    }
                }

                $data[] = $custom_data_text;
            }

            array_push($table1->data, $data);

            if ($show_extended_events == 1 && events_has_extended_info($event['id_evento'])) {
                $extended_events = events_get_extended_events($event['id_evento']);

                foreach ($extended_events as $extended_event) {
                    $extended_data = [];

                    $extended_data[] = "<td colspan='5'><font class='italic'>".io_safe_output($extended_event['description'])."</font></td><td><font class='font_6pt italic'>".date($config['date_format'], $extended_event['utimestamp']).'</font></td>';
                    array_push($table1->data, $extended_data);
                }
            }
        }

        if ($pdf) {
            $table0 = new stdClass();
            $table0->width = '99%';
            $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
            $pdf_export = html_print_table($table0, true);

            $pdf_export .= html_print_table($table1, true);
            $pdf_export .= '<br>';
        } else {
            $table->colspan['events']['cell'] = 3;
            $table->data['events']['cell'] = html_print_table($table1, true);
        }

        if (!empty($item['chart']['by_agent'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events by agent');
            $table1->data[0][0] = $item['chart']['by_agent'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_by_agent']['cell'] = 3;
                $table->cellstyle['chart_by_agent']['cell'] = 'text-align: center;';
                $table->data['chart_by_agent']['cell'] = html_print_table($table1, true);
            }
        }

        if (!empty($item['chart']['by_user_validator'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events by user validator');
            $table1->data[0][0] = $item['chart']['by_user_validator'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_by_user_validator']['cell'] = 3;
                $table->cellstyle['chart_by_user_validator']['cell'] = 'text-align: center;';
                $table->data['chart_by_user_validator']['cell'] = html_print_table($table1, true);
            }
        }

        if (!empty($item['chart']['by_criticity'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events by Severity');
            $table1->data[0][0] = $item['chart']['by_criticity'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_by_criticity']['cell'] = 3;
                $table->cellstyle['chart_by_criticity']['cell'] = 'text-align: center;';
                $table->data['chart_by_criticity']['cell'] = html_print_table($table1, true);
            }
        }

        if (!empty($item['chart']['validated_vs_unvalidated'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events validated vs unvalidated');
            $table1->data[0][0] = $item['chart']['validated_vs_unvalidated'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_validated_vs_unvalidated']['cell'] = 3;
                $table->cellstyle['chart_validated_vs_unvalidated']['cell'] = 'text-align: center;';
                $table->data['chart_validated_vs_unvalidated']['cell'] = html_print_table($table1, true);
            }
        }

        if ($pdf) {
            return $pdf_export;
        }
    } else {
        if ($pdf) {
            $table0 = new stdClass();
            $table0->width = '99%';
            $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
            $pdf_export = html_print_table($table0, true);

            return $pdf_export;
        }
    }
}


function reporting_html_event_report_module($table, $item, $pdf=0)
{
    global $config;
    $show_extended_events = $item['show_extended_events'];

    $show_summary_group = $item['show_summary_group'];
    $show_custom_data = (bool) $item['show_custom_data'];
    if ($item['total_events']) {
        if (!empty($item['failed'])) {
            $table->colspan['events']['cell'] = 3;
            $table->data['events']['cell'] = $item['failed'];
        } else {
            foreach ($item['data'] as $item) {
                $table1 = new stdClass();
                $table1->width = '99%';
                $table1->class = 'info_table';
                $table1->data = [];
                $table1->head = [];
                $table1->align[0] = 'left';

                if ($show_summary_group) {
                    $table1->head[0]  = __('Status');
                    $table1->head[1]  = __('Type');
                    $table1->head[2]  = __('Event name');
                    $table1->head[3]  = __('Severity');
                    $table1->head[4]  = __('Count');
                    $table1->head[5]  = __('Timestamp');
                    $table1->style[0] = 'text-align: center;';
                } else {
                    $table1->head[0]  = __('Status');
                    $table1->head[1]  = __('Type');
                    $table1->head[2]  = __('Event name');
                    $table1->head[3]  = __('Severity');
                    $table1->head[4]  = __('Timestamp');
                    $table1->style[0] = 'text-align: center;';
                }

                if ($show_custom_data === true) {
                    $table1->head[6]  = __('Custom data');
                }

                if (is_array($item['data']) || is_object($item['data'])) {
                    $item_data = array_reverse($item['data']);
                }

                if (is_array($item_data) || is_object($item_data)) {
                    foreach ($item_data as $i => $event) {
                        $data = [];
                        // Colored box.
                        switch ($event['estado']) {
                            case 0:
                            default:
                                $img_st   = 'images/star.png';
                                $title_st = __('New event');
                            break;

                            case 1:
                                $img_st   = 'images/tick.png';
                                $title_st = __('Event validated');
                            break;

                            case 2:
                                $img_st   = 'images/hourglass.png';
                                $title_st = __('Event in process');
                            break;
                        }

                        $data[0] = html_print_image(
                            $img_st,
                            true,
                            [
                                'class' => 'image_status invert_filter',
                                'width' => 16,
                                'title' => $title_st,
                                'id'    => 'status_img_'.$event['id_evento'],
                            ]
                        );

                        if ($pdf) {
                            $data[1] = events_print_type_img_pdf($event['event_type'], true);
                        } else {
                            $data[1] = events_print_type_img($event['event_type'], true);
                        }

                        $data[2] = io_safe_output($event['evento']);
                        $data[3] = get_priority_name($event['criticity']);
                        if ($show_summary_group) {
                            $data[4] = $event['event_rep'];
                            $data[5] = date($config['date_format'], $event['timestamp_last']);
                        } else {
                            $data[4] = date($config['date_format'], strtotime($event['timestamp']));
                        }

                        if ($show_custom_data === true) {
                            $custom_data = json_decode($event['custom_data'], true);
                            $custom_data_text = '';
                            foreach ($custom_data as $key => $value) {
                                if (is_array($value)) {
                                    $custom_data_text .= $key.' = ';
                                    foreach ($value as $action) {
                                        $custom_data_text .= $action.', ';
                                    }

                                    $custom_data_text = rtrim($custom_data_text, ', ').'<br>';
                                } else {
                                    $custom_data_text .= $key.' = '.$value.'<br>';
                                }
                            }

                            $data[6] = $custom_data_text;
                        }

                        $table1->data[] = $data;

                        if ($show_extended_events == 1 && events_has_extended_info($event['id_evento'])) {
                            $extended_events = events_get_extended_events($event['id_evento']);

                            foreach ($extended_events as $extended_event) {
                                $extended_data = [];

                                $extended_data[] = "<td colspan='3'><font class='italic'>".io_safe_output($extended_event['description'])."</font></td><td><font class='italic'>".date($config['date_format'], $extended_event['utimestamp']).'</font></td>';
                                array_push($table1->data, $extended_data);
                            }
                        }
                    }
                }

                if ($pdf) {
                    $table0 = new stdClass();
                    $table0->width = '99%';
                    $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
                    $pdf_export = html_print_table($table0, true);

                    $pdf_export .= html_print_table($table1, true);
                    $pdf_export .= '<br>';
                } else {
                    $table->colspan['events']['cell'] = 3;
                    $table->data['events']['cell'] = html_print_table($table1, true);
                }

                if (!empty($item['chart']['by_agent'])) {
                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->head = [];
                    $table1->head[0] = __('Events by agent');
                    $table1->data[0][0] = $item['chart']['by_agent'];

                    if ($pdf) {
                        $pdf_export .= html_print_table($table1, true);
                        $pdf_export .= '<br>';
                    } else {
                        $table->colspan['chart_by_agent']['cell'] = 3;
                        $table->cellstyle['chart_by_agent']['cell'] = 'text-align: center;';
                        $table->data['chart_by_agent']['cell'] = html_print_table($table1, true);
                    }
                }

                if (!empty($item['chart']['by_user_validator'])) {
                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->head = [];
                    $table1->head[0] = __('Events by user validator');
                    $table1->data[0][0] = $item['chart']['by_user_validator'];

                    if ($pdf) {
                        $pdf_export .= html_print_table($table1, true);
                        $pdf_export .= '<br>';
                    } else {
                        $table->colspan['chart_by_user_validator']['cell'] = 3;
                        $table->cellstyle['chart_by_user_validator']['cell'] = 'text-align: center;';
                        $table->data['chart_by_user_validator']['cell'] = html_print_table($table1, true);
                    }
                }

                if (!empty($item['chart']['by_criticity'])) {
                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->head = [];
                    $table1->head[0] = __('Events by Severity');
                    $table1->data[0][0] = $item['chart']['by_criticity'];

                    if ($pdf) {
                        $pdf_export .= html_print_table($table1, true);
                        $pdf_export .= '<br>';
                    } else {
                        $table->colspan['chart_by_criticity']['cell'] = 3;
                        $table->cellstyle['chart_by_criticity']['cell'] = 'text-align: center;';
                        $table->data['chart_by_criticity']['cell'] = html_print_table($table1, true);
                    }
                }

                if (!empty($item['chart']['validated_vs_unvalidated'])) {
                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->head = [];
                    $table1->head[0] = __('Events validated vs unvalidated');
                    $table1->data[0][0] = $item['chart']['validated_vs_unvalidated'];

                    if ($pdf) {
                        $pdf_export .= html_print_table($table1, true);
                        $pdf_export .= '<br>';
                    } else {
                        $table->colspan['chart_validated_vs_unvalidated']['cell'] = 3;
                        $table->cellstyle['chart_validated_vs_unvalidated']['cell'] = 'text-align: center;';
                        $table->data['chart_validated_vs_unvalidated']['cell'] = html_print_table($table1, true);
                    }
                }

                if ($pdf) {
                    return $pdf_export;
                }
            }
        }
    } else {
        if ($pdf) {
            $table0 = new stdClass();
            $table0->width = '99%';
            $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
            $pdf_export = html_print_table($table0, true);

            return $pdf_export;
        }
    }
}


/**
 * Print in html agents inventory
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   Print pdf true or false.
 *
 * @return string HTML code.
 */
function reporting_html_agents_inventory($table, $item, $pdf=0)
{
    global $config;

    $table1 = new stdClass();
    $table1->width = '100%';

    $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->class = 'info_table';
    $table1->cellpadding = 1;
    $table1->cellspacing = 1;
    $table1->styleTable = 'overflow: wrap; table-layout: fixed;';

    $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[1] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[2] = 'text-align: left;vertical-align: top; min-width: 100px';
    $table1->style[3] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[4] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[5] = 'text-align: left;vertical-align: top; min-width: 100px';
    $table1->style[6] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[7] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[8] = 'text-align: left;vertical-align: top; min-width: 100px';
    $table1->style[9] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[10] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[11] = 'text-align: left;vertical-align: top; min-width: 100px';

    $table1->head = [];

    // Sort array columns.
    $tmp_sort_array = [];
    foreach ($item['data'] as $data_key => $data_value) {
        if (array_key_exists('alias', $data_value) === true) {
            $tmp_sort_array['alias'] = $data_value['alias'];
        }

        if (array_key_exists('direccion', $data_value) === true) {
            $tmp_sort_array['direccion'] = $data_value['direccion'];
        }

        if (array_key_exists('id_os', $data_value) === true) {
            $tmp_sort_array['id_os'] = $data_value['id_os'];
        }

        if (array_key_exists('agent_version', $data_value) === true) {
            $tmp_sort_array['agent_version'] = $data_value['agent_version'];
        }

        if (array_key_exists('id_grupo', $data_value) === true) {
            $tmp_sort_array['id_grupo'] = $data_value['id_grupo'];
        }

        if (array_key_exists('comentarios', $data_value) === true) {
            $tmp_sort_array['comentarios'] = $data_value['comentarios'];
        }

        if (array_key_exists('url_address', $data_value) === true) {
            $tmp_sort_array['url_address'] = $data_value['url_address'];
        }

        if (array_key_exists('remote', $data_value) === true) {
            $tmp_sort_array['remote'] = $data_value['remote'];
        }

        if (array_key_exists('secondary_groups', $data_value) === true) {
            $tmp_sort_array['secondary_groups'] = $data_value['secondary_groups'];
        }

        if (array_key_exists('custom_fields', $data_value) === true) {
            $tmp_sort_array['custom_fields'] = $data_value['custom_fields'];
        }

        if (array_key_exists('estado', $data_value) === true) {
            $tmp_sort_array['estado'] = $data_value['estado'];
        }

        unset($item['data'][$data_key]);
        $item['data'][$data_key] = $tmp_sort_array;
    }

    foreach ($item['data'][0] as $field_key => $field_value) {
        switch ($field_key) {
            case 'alias':
                $table1->head[] = __('Alias');
            break;

            case 'direccion':
                $table1->head[] = __('IP Address');
            break;

            case 'id_os':
                $table1->head[] = __('OS');
            break;

            case 'id_grupo':
                $table1->head[] = __('Group');
            break;

            case 'comentarios':
                $table1->head[] = __('Description');
            break;

            case 'secondary_groups':
                $table1->head[] = __('Sec. groups');
            break;

            case 'url_address':
                $table1->head[] = __('URL');
            break;

            case 'custom_fields':
                $table1->head[] = __('Custom fields');
            break;

            case 'estado':
                $table1->head[] = __('Status');
            break;

            case 'agent_version':
                $table1->head[] = __('Version');
            break;

            case 'remote':
                $table1->head[] = __('Remote conf.');
            break;
        }
    }

    $table1->headstyle[0] = 'text-align: left';
    $table1->headstyle[1] = 'text-align: left';
    $table1->headstyle[2] = 'text-align: left';
    $table1->headstyle[3] = 'text-align: left';
    $table1->headstyle[4] = 'text-align: left';
    $table1->headstyle[5] = 'text-align: left';
    $table1->headstyle[6] = 'text-align: left';
    $table1->headstyle[7] = 'text-align: left';
    $table1->headstyle[8] = 'text-align: left';
    $table1->headstyle[9] = 'text-align: left';
    $table1->headstyle[10] = 'text-align: left';
    $table1->headstyle[11] = 'text-align: left';

    $table1->data = [];

    foreach ($item['data'] as $data) {
        $row = [];

        foreach ($data as $data_field_key => $data_field_value) {
            $column_value = $data_field_value;

            $show_link = ($pdf === 0) ? true : false;

            // Necessary transformations of data prior to represent it.
            if ($data_field_key === 'id_os') {
                $column_value = get_os_name((int) $data_field_value);
            } else if ($data_field_key === 'remote' && $pdf === 0) {
                $column_value = ((int) $data_field_value === 1) ? __('Yes') : __('No');
            } else if ($data_field_key === 'url_address' && $pdf === 0) {
                $column_value = ui_print_truncate_text($data_field_value, 10);
            } else if ($data_field_key === 'estado') {
                $column_value = ($pdf === 0) ? ui_print_module_status((int) $data_field_value, true) : modules_get_modules_status((int) $data_field_value);
            } else if ($data_field_key === 'id_grupo') {
                $column_value = groups_get_name((int) $data_field_value);
            } else if ($data_field_key === 'custom_fields') {
                $custom_fields_value = [];

                if (is_array($data_field_value)) {
                    foreach ($data_field_value as $value) {
                        $custom_fields_value[] = $value['name'].': '.$value['description'];
                    }
                }

                $column_value = implode(' / ', $custom_fields_value);
            } else if ($data_field_key === 'secondary_groups') {
                $custom_fields_value = [];

                if (is_array($data_field_value)) {
                    foreach ($data_field_value as $value) {
                        $custom_fields_value[] = groups_get_name((int) $value['id_group']);
                    }
                }

                $column_value = implode(' / ', $custom_fields_value);
            }

            $row[] = $column_value;
        }

        $table1->data[] = $row;

        if ($pdf !== 0) {
            $table1->data[] = '<br />';
        }
    }

    if ($pdf === 0) {
        $table->colspan['permissions']['cell'] = 3;
        $table->cellstyle['permissions']['cell'] = 'text-align: center;';
        $table->data['permissions']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        return html_print_table(
            $table1,
            true
        );
    }
}


/**
 * Print html modules inventory
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   Print pdf true or false.
 *
 * @return string HTML code.
 */
function reporting_html_modules_inventory($table, $item, $pdf=0)
{
    global $config;

    $table1 = new stdClass();
    $table1->width = '100%';

    $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->class = 'info_table';
    $table1->cellpadding = 1;
    $table1->cellspacing = 1;
    $table1->styleTable = 'overflow: wrap; table-layout: fixed;';

    $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[1] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[2] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[3] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[4] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[5] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[6] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->style[7] = 'text-align: left;vertical-align: top;min-width: 100px;';

    $table1->head = [];
    $first_index = array_key_first($item['data']);

    foreach ($item['data'][$first_index] as $field_key => $field_value) {
        switch ($field_key) {
            case 'alias':
                $table1->head[] = __('Alias');
            break;

            case 'nombre':
                $table1->head[] = __('Name');
            break;

            case 'descripcion':
                $table1->head[] = __('Description');
            break;

            case 'id_module_group':
                $table1->head[] = __('Module group');
            break;

            case 'id_tag':
                $table1->head[] = __('Tags');
            break;

            case 'group_id':
                $table1->head[] = __('Agent group');
            break;

            case 'sec_group_id':
                $table1->head[] = __('Agent secondary groups');
            break;

            case 'last_status_change':
                $table1->head[] = __('Last status change');
        }
    }

    $table1->headstyle[0] = 'text-align: left';
    $table1->headstyle[1] = 'text-align: left';
    $table1->headstyle[2] = 'text-align: left';
    $table1->headstyle[3] = 'text-align: left';
    $table1->headstyle[4] = 'text-align: left';
    $table1->headstyle[5] = 'text-align: left';
    $table1->headstyle[6] = 'text-align: left';
    $table1->headstyle[7] = 'text-align: left';

    $table1->data = [];

    foreach ($item['data'] as $module_id => $module_data) {
        unset($module_data['server_id']);
        $row = [];
        $first_item = array_pop(array_reverse($module_data));

        foreach ($module_data as $data_field_key => $data_field_value) {
            if ($data_field_key === 'alias') {
                $column_value = $data_field_value;
            } else if ($data_field_key === 'nombre') {
                $column_value = $data_field_value;
            } else if ($data_field_key === 'descripcion') {
                $column_value = $data_field_value;
            } else if ($data_field_key === 'id_module_group') {
                $module_group_name = modules_get_modulegroup_name($data_field_value);

                if ($module_group_name === '') {
                    $module_group_name = '-';
                }

                $column_value = $module_group_name;
            } else if ($data_field_key === 'id_tag') {
                if (empty($data_field_value[0]) === false) {
                    $sql = 'SELECT name
                                FROM ttag
                                WHERE id_tag IN ('.$data_field_value[0].')';

                    $tags_rows = db_get_all_rows_sql($sql);
                    $tags_names = [];
                    foreach ($tags_rows as $tag_row) {
                        array_push($tags_names, $tag_row['name']);
                    }

                    $column_value = implode('<br>', $tags_names);
                } else {
                    $tags_names = array_map(
                        function ($tag_id) {
                            return db_get_value('name', 'ttag', 'id_tag', $tag_id);
                        },
                        $data_field_value
                    );
                    $column_value = implode('<br>', $tags_names);
                }
            } else if ($data_field_key === 'group_id') {
                $column_value = groups_get_name($data_field_value[0]);
            } else if ($data_field_key === 'sec_group_id') {
                $sec_groups_names = array_map(
                    function ($group_id) {
                        return groups_get_name($group_id);
                    },
                    $data_field_value
                );

                $column_value = implode('<br>', $sec_groups_names);
            } else if ($data_field_key === 'last_status_change') {
                $column_value = $data_field_value;
            }

            $row[] = $column_value;
        }

        $table1->data[] = $row;

        if ($pdf !== 0) {
            $table1->data[] = '<br />';
        }
    }

    if ($pdf === 0) {
        $table->colspan['permissions']['cell'] = 3;
        $table->cellstyle['permissions']['cell'] = 'text-align: center;';
        $table->data['permissions']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        return html_print_table(
            $table1,
            true
        );
    }
}


/**
 * Print in html inventory changes reports
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   Print pdf true or false.
 *
 * @return string HTML code.
 */
function reporting_html_inventory_changes($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (!empty($item['failed'])) {
        if ($pdf === 0) {
            $table->colspan['failed']['cell'] = 3;
            $table->cellstyle['failed']['cell'] = 'text-align: center;';
            $table->data['failed']['cell'] = $item['failed'];
        } else {
            $return_pdf .= $item['failed'];
        }
    } else {
        foreach ($item['data'] as $module_item) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->class = 'info_table';
            $table1->cellstyle = [];

            $table1->cellstyle[0][0] = 'background: #373737; color: #FFF;';
            $table1->cellstyle[0][1] = 'background: #373737; color: #FFF;';
            $table1->data[0][0] = $module_item['agent'];
            $table1->data[0][1] = $module_item['module'];

            $table1->cellstyle[1][0] = 'background: #373737; color: #FFF;';
            $table1->data[1][0] = $module_item['date'];
            $table1->colspan[1][0] = 2;

            $table1->cellstyle[2][0] = 'background: #373737; color: #FFF; text-align: center;';
            $table1->data[2][0] = __('Added');
            $table1->colspan[2][0] = 2;

            if (count($module_item['added'])) {
                $table1->data = array_merge(
                    $table1->data,
                    $module_item['added']
                );
            }

            $table1->cellstyle[(3 + count($module_item['added']))][0] = 'background: #373737; color: #FFF; text-align: center;';
            $table1->data[(3 + count($module_item['added']))][0] = __('Deleted');
            $table1->colspan[(3 + count($module_item['added']))][0] = 2;

            if (count($module_item['deleted'])) {
                $table1->data = array_merge(
                    $table1->data,
                    $module_item['deleted']
                );
            }

            if ($pdf === 0) {
                $table->colspan[$module_item['agent'].'_'.$module_item['module']]['cell'] = 3;
                $table->data[$module_item['agent'].'_'.$module_item['module']]['cell'] = html_print_table(
                    $table1,
                    true
                );
            } else {
                $table1->title = $item['title'];
                $table1->titleclass = 'title_table_pdf';
                $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table1,
                    true
                );
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Print in html inventory reportd
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   Print pdf true or false.
 *
 * @return string HTML code.
 */
function reporting_html_inventory($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (empty($item['failed']) === false) {
        if ($pdf === 0) {
            $table->colspan['failed']['cell'] = 3;
            $table->cellstyle['failed']['cell'] = 'text-align: center;';
            $table->data['failed']['cell'] = $item['failed'];
        } else {
            $return_pdf .= $item['failed'];
        }
    } else {
        // Grouped type inventory.
        $type_modules = array_reduce(
            $item['data'],
            function ($carry, $it) {
                $carry[$it['name']][] = $it;
                return $carry;
            },
            []
        );

        if (isset($type_modules) === true
            && is_array($type_modules) === true
        ) {
            foreach ($type_modules as $key_type_module => $type_module) {
                $print_table = true;
                foreach ($type_module as $key => $module) {
                    if (count($module['data']) == 0) {
                        unset($type_module[$key]);
                    }
                }

                $table1 = new stdClass();
                $table1->width = '99%';
                $table1->class = 'info_table';
                $table1->data = [];
                $table1->head = [];
                $table1->cellstyle = [];
                $table1->headstyle = [];
                if (isset($type_module) === true
                    && is_array($type_module) === true
                ) {
                    if (count($type_module) > 0) {
                        foreach ($type_module as $key_type => $module) {
                            if (isset($module['data']) === true
                                && is_array($module['data']) === true
                            ) {
                                foreach ($module['data'] as $k_module => $v_module) {
                                    $str_key = $key_type_module.'-'.$key_type.'-'.$k_module;
                                    $table1->head[0] = __('Agent');
                                    $table1->head[1] = __('Module');
                                    $table1->head[2] = __('Date');
                                    $table1->headstyle[0] = 'text-align: left';
                                    $table1->headstyle[1] = 'text-align: left';
                                    $table1->headstyle[2] = 'text-align: left';
                                    $table1->cellstyle[$str_key][0] = 'text-align: left;';
                                    $table1->cellstyle[$str_key][1] = 'text-align: left;';
                                    $table1->cellstyle[$str_key][2] = 'text-align: left;';
                                    $table1->data[$str_key][0] = $module['agent_name'];
                                    $table1->data[$str_key][1] = $key_type_module;
                                    $dateModule = explode(' ', $module['timestamp']);
                                    $table1->data[$str_key][2] = $dateModule[0];
                                    if (isset($v_module) === true
                                        && is_array($v_module) === true
                                    ) {
                                        foreach ($v_module as $k => $v) {
                                            $table1->head[$k] = $k;
                                            $table1->headstyle[$k] = 'text-align: left';
                                            $table1->cellstyle[$str_key][$k] = 'text-align: left;';
                                            if ($pdf === 0) {
                                                $table1->data[$str_key][$k] = $v;
                                            } else {
                                                // Workaround to prevent table columns from growing indefinitely in PDFs.
                                                $table1->data[$str_key][$k] = preg_replace(
                                                    '/([^\s]{30})(?=[^\s])/',
                                                    '$1'.'<br>',
                                                    $v
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $print_table = false;
                    }
                } else {
                    $print_table = false;
                }

                if ($pdf === 0 && $print_table === true) {
                    $table->colspan[$key_type_module]['cell'] = 3;
                    $table->data[$key_type_module]['cell'] = html_print_table(
                        $table1,
                        true
                    );
                } else if ($print_table === true) {
                    $return_pdf .= html_print_table(
                        $table1,
                        true
                    );
                }
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Print in html the agent / module report
 * showing the status of these modules.
 *
 * @param object $table Head table or false if it comes from pdf.
 * @param array  $item  Items data.
 *
 * @return void
 */
function reporting_html_agent_module($table, $item)
{
    $table->colspan['agent_module']['cell'] = 3;
    $table->cellstyle['agent_module']['cell'] = 'text-align: center;';

    if (!empty($item['failed'])) {
        $table->data['agent_module']['cell'] = $item['failed'];
    } else {
        $table_data = '<table class="info_table" cellpadding="0" cellspacing="0" cellspacing="0">';
        $table_data .= '<tr class="border_th">';
        $table_data .= '<th class="bg_th">'.__('Agents').' / '.__('Modules').'</th>';

        $first = reset($item['data']);
        $list_modules = $first['modules'];

        foreach ($list_modules as $module_name => $module) {
            $file_name = ui_print_truncate_text(
                $module_name,
                'module_small',
                false,
                true,
                false,
                '...'
            );

            $table_data .= '<th class="pdd_10px bg_th">'.$file_name.'</th>';
        }

        $table_data .= '</tr>';

        foreach ($item['data'] as $row) {
            $table_data .= "<tr class='height_35px border_tr'>";
            switch ($row['agent_status']) {
                case AGENT_STATUS_ALERT_FIRED:
                    $rowcolor = COL_ALERTFIRED;
                    $textcolor = '#000';
                break;

                case AGENT_STATUS_CRITICAL:
                    $rowcolor = COL_CRITICAL;
                    $textcolor = '#FFF';
                break;

                case AGENT_STATUS_WARNING:
                    $rowcolor = COL_WARNING;
                    $textcolor = '#000';
                break;

                case AGENT_STATUS_NORMAL:
                    $rowcolor = COL_NORMAL;
                    $textcolor = '#FFF';
                break;

                case AGENT_STATUS_UNKNOWN:
                case AGENT_STATUS_ALL:
                default:
                    $rowcolor = COL_UNKNOWN;
                    $textcolor = '#FFF';
                break;
            }

            $file_name = ui_print_truncate_text(
                $row['agent_name'],
                'agent_small',
                false,
                true,
                false,
                '...'
            );
            $table_data .= '<td class="pdd_6px left">'.$file_name.'</td>';

            foreach ($row['modules'] as $module_name => $module) {
                if ($module === null) {
                    $table_data .= '<td></td>';
                } else {
                    $table_data .= '<td style="text-align: left;">';
                    if (isset($row['show_type']) === true && $row['show_type'] === '1') {
                        $table_data .= $module;
                    } else {
                        switch ($module) {
                            case AGENT_STATUS_CRITICAL:
                                $table_data .= ui_print_status_image(
                                    'module_critical.png',
                                    __(
                                        '%s in %s : CRITICAL',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;

                            case AGENT_STATUS_WARNING:
                                $table_data .= ui_print_status_image(
                                    'module_warning.png',
                                    __(
                                        '%s in %s : WARNING',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;

                            case AGENT_STATUS_UNKNOWN:
                                $table_data .= ui_print_status_image(
                                    'module_unknown.png',
                                    __(
                                        '%s in %s : UNKNOWN',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;

                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                                $table_data .= ui_print_status_image(
                                    'module_alertsfired.png',
                                    __(
                                        '%s in %s : ALERTS FIRED',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;

                            case 4:
                                $table_data .= ui_print_status_image(
                                    'module_no_data.png',
                                    __(
                                        '%s in %s : Not initialize',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;

                            default:
                            case AGENT_STATUS_NORMAL:
                                $table_data .= ui_print_status_image(
                                    'module_ok.png',
                                    __(
                                        '%s in %s : NORMAL',
                                        $module_name,
                                        $row['agent_name']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ],
                                    'images/status_sets/default/'
                                );
                            break;
                        }
                    }

                    $table_data .= '</td>';
                }
            }
        }

        $table_data .= '</table>';

        if (isset($row['show_type']) === false) {
            $table_data .= "<div class='legend_basic w96p'>";
            $table_data .= '<table>';
            $table_data .= "<tr><td colspan='2' class='pdd_b_10px'><b>".__('Legend').'</b></td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_ALERTFIRED.";'></div></td><td>".__('Orange cell when the module has fired alerts').'</td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_CRITICAL.";'></div></td><td>".__('Red cell when the module has a critical status').'</td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_WARNING.";'></div></td><td>".__('Yellow cell when the module has a warning status').'</td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_NORMAL.";'></div></td><td>".__('Green cell when the module has a normal status').'</td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_UNKNOWN.";'></div></td><td>".__('Grey cell when the module has an unknown status').'</td></tr>';
            $table_data .= "<tr><td class='legend_square_simple'><div style='background-color: ".COL_NOTINIT.";'></div></td><td>".__("Cell turns grey when the module is in 'not initialize' status").'</td></tr>';
            $table_data .= '</table>';
            $table_data .= '</div>';
        }

        $table->data['agent_module']['cell'] = $table_data;
    }
}


/**
 * Html report agent modules status.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param integer $pdf   Pdf output.
 *
 * @return mixed
 */
function reporting_html_agent_module_status($table, $item, $pdf=0)
{
    global $config;

    $return_pdf = '';

    if (empty($item['data']) === true) {
        if ($pdf !== 0) {
            $return_pdf .= __('No items');
        } else {
            $table->colspan['group_report']['cell'] = 3;
            $table->cellstyle['group_report']['cell'] = 'text-align: center;';
            $table->data['group_report']['cell'] = __('No items');
        }
    } else {
        $table_info = new stdClass();
        $table_info->width = '99%';

        $table_info->align = [];
        if (is_metaconsole() === true) {
            $table_info->align['server'] = 'left';
        }

        $table_info->align['name_group'] = 'left';
        $table_info->align['name_agent'] = 'left';
        $table_info->align['name_module'] = 'left';
        $table_info->align['status_module'] = 'left';
        $table_info->align['data_module'] = 'left';
        $table_info->align['data_time_module'] = 'left';

        $table_info->headstyle = [];
        if (is_metaconsole() === true) {
            $table_info->headstyle['server'] = 'text-align: left';
        }

        $table_info->headstyle['name_group'] = 'text-align: left';
        $table_info->headstyle['name_agent'] = 'text-align: left';
        $table_info->headstyle['name_module'] = 'text-align: left';
        $table_info->headstyle['status_module'] = 'text-align: left';
        $table_info->headstyle['data_module'] = 'text-align: left';
        $table_info->headstyle['data_time_module'] = 'text-align: left';

        $table_info->head = [];
        if (is_metaconsole() === true) {
            $table_info->head['server'] = __('Server');
        }

        $table_info->head['name_agent'] = __('Agent');
        $table_info->head['name_module'] = __('Module');
        $table_info->head['name_group'] = __('Group');
        $table_info->head['status_module'] = __('Status');
        $table_info->head['data_module'] = __('Data');
        $table_info->head['data_time_module'] = __('Last time');

        $table_info->data = [];

        foreach ($item['data'] as $server => $info) {
            foreach ($info as $data) {
                $row = [];
                if (is_metaconsole() === true) {
                    $row['server'] = $server;
                }

                $row['name_agent'] = $data['name_agent'];
                $row['name_module'] = $data['name_module'];
                $row['name_group'] = $data['name_group'];
                $row['status_module'] = ui_print_module_status(
                    $data['status_module'],
                    true,
                    'status_rounded_rectangles',
                    null,
                    ($pdf === 1) ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : ''
                );

                if (is_numeric($data['data_module']) === true) {
                    $row['data_module'] = remove_right_zeros(
                        number_format(
                            $data['data_module'],
                            $config['graph_precision'],
                            $config['decimal_separator'],
                            $config['thousand_separator']
                        )
                    );
                } else {
                    $row['data_module'] = (empty($data['data_module']) === true) ? '--' : $data['data_module'];
                }

                $row['data_module'] .= $data['unit_module'];
                $row['data_time_module'] = $data['data_time_module'];

                $table_info->data[] = $row;
            }
        }

        if ($pdf !== 0) {
            $table_info->title = $item['title'];
            $table_info->titleclass = 'title_table_pdf';
            $table_info->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table($table_info, true);
        } else {
            $table->colspan['data']['cell'] = 3;
            $table->cellstyle['data']['cell'] = 'text-align: center;';
            $table->data['data']['cell'] = html_print_table($table_info, true);
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Function to print to HTML Exception report.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_exception($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (!empty($item['failed'])) {
        if ($pdf !== 0) {
            $return_pdf .= $item['failed'];
        } else {
            $table->colspan['group_report']['cell'] = 3;
            $table->cellstyle['group_report']['cell'] = 'text-align: center;';
            $table->data['group_report']['cell'] = $item['failed'];
        }
    } else {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->class = 'info_table';

        $table1->align = [];
        $table1->align['agent'] = 'left';
        $table1->align['module'] = 'left';
        $table1->align['operation'] = 'left';
        $table1->align['value'] = 'right';

        $table1->data = [];

        $table1->headstyle = [];
        $table1->headstyle['agent'] = 'text-align: left';
        $table1->headstyle['module'] = 'text-align: left';
        $table1->headstyle['operation'] = 'text-align: left';
        $table1->headstyle['value'] = 'text-align: right';

        $table1->head = [];
        $table1->head['agent'] = __('Agent');
        $table1->head['module'] = __('Module');
        $table1->head['operation'] = __('Operation');
        $table1->head['value'] = __('Value');

        foreach ($item['data'] as $data) {
            $row = [];
            $row['agent'] = $data['agent'];
            $row['module'] = $data['module'];
            $row['operation'] = $data['operation'];
            $row['value'] = $data['formated_value'];

            $table1->data[] = $row;
        }

        if ($pdf !== 0) {
            $table1->title = $item['title'];
            $table1->titleclass = 'title_table_pdf';
            $table1->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table($table1, true);
        } else {
            $table->colspan['data']['cell'] = 3;
            $table->cellstyle['data']['cell'] = 'text-align: center;';
            $table->data['data']['cell'] = html_print_table($table1, true);
        }

        if (!empty($item['chart'])) {
            if ($pdf !== 0) {
                $return_pdf .= $item['chart']['pie'];
                $return_pdf .= $item['chart']['hbar'];
            } else {
                $table->colspan['chart_pie']['cell'] = 3;
                $table->cellstyle['chart_pie']['cell'] = 'text-align: center;';
                $table->data['chart_pie']['cell'] = $item['chart']['pie'];

                $table->colspan['chart_hbar']['cell'] = 3;
                $table->cellstyle['chart_hbar']['cell'] = 'text-align: center;';
                $table->data['chart_hbar']['cell'] = $item['chart']['hbar'];
            }
        }

        if (!empty($item['resume'])) {
            $table1 = new stdClass();
            $table1->width = '99%';

            $table1->align = [];
            $table1->align['min'] = 'right';
            $table1->align['avg'] = 'right';
            $table1->align['max'] = 'right';

            $table1->headstyle = [];
            $table1->headstyle['min'] = 'text-align: right';
            $table1->headstyle['avg'] = 'text-align: right';
            $table1->headstyle['max'] = 'text-align: right';

            $table1->head = [];
            $table1->head['min'] = __('Min Value');
            $table1->head['avg'] = __('Average Value');
            $table1->head['max'] = __('Max Value');

            $table1->data = [];
            $table1->data[] = [
                'min' => $item['resume']['min']['formated_value'],
                'avg' => $item['resume']['avg']['formated_value'],
                'max' => $item['resume']['max']['formated_value'],
            ];

            if ($pdf !== 0) {
                $table1->title = $item['title'];
                $table1->titleclass = 'title_table_pdf';
                $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table($table1, true);
            } else {
                $table->colspan['resume']['cell'] = 3;
                $table->cellstyle['resume']['cell'] = 'text-align: center;';
                $table->data['resume']['cell'] = html_print_table($table1, true);
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Function to print to HTML group report.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_group_report($table, $item, $pdf=0)
{
    global $config;

    $table->colspan['group_report']['cell'] = 3;
    $table->cellstyle['group_report']['cell'] = 'text-align: center;';
    $data = "<table class='info_table' width='100%'>
        <tbody><tr>
            <td></td>
            <td colspan='3' class='cellBold cellCenter'>".__('Total')."</td>
            <td colspan='3' class='cellBold cellCenter'>".__('Unknown')."</td>
        </tr>
        <tr>
            <td class='cellBold cellCenter'>".__('Agents')."</td>
            <td colspan='3' class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$item['data']['group_stats']['total_agents']."</td>
            <td colspan='3' class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>".$item['data']['group_stats']['agents_unknown']."</td>
        </tr>
        <tr>
            <td></td>
            <td class='cellBold cellCenter'>".__('Total')."</td>
            <td class='cellBold cellCenter'>".__('Normal')."</td>
            <td class='cellBold cellCenter'>".__('Critical')."</td>
            <td class='cellBold cellCenter'>".__('Warning')."</td>
            <td class='cellBold cellCenter'>".__('Unknown')."</td>
            <td class='cellBold cellCenter'>".__('Not init')."</td>
        </tr>
        <tr>
            <td class='cellBold cellCenter'>".__('Monitors')."</td>
            <td class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_checks']."</td>
            <td class='cellBold cellCenter cellNormal cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_ok']."</td>
            <td class='cellBold cellCenter cellCritical cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_critical']."</td>
            <td class='cellBold cellCenter cellWarning cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_warning']."</td>
            <td class='cellBold cellCenter cellUnknown cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_unknown']."</td>
            <td class='cellBold cellCenter cellNotInit cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_not_init']."</td>
        </tr>
        <tr>
            <td></td>
            <td colspan='3' class='cellBold cellCenter'>".__('Defined')."</td>
            <td colspan='3' class='cellBold cellCenter'>".__('Fired')."</td>
        </tr>
        <tr>
            <td class='cellBold cellCenter'>".__('Alerts')."</td>
            <td colspan='3' class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_alerts']."</td>
            <td colspan='3' class='cellBold cellCenter cellAlert cellBorder1 cellBig'>".$item['data']['group_stats']['monitor_alerts_fired']."</td>
        </tr>
        <tr>
            <td class='cellBold cellCenter'>".__('Events (not validated)')."</td>
            <td colspan='6' class='cellBold cellCenter cellWhite cellBorder1 cellBig'>".$item['data']['count_events'].'</td>
        </tr></tbody>
    </table>';

    $table->data['group_report']['cell'] = $data;

    if ($pdf !== 0) {
        return $data;
    }
}


function reporting_html_event_report_agent($table, $item, $pdf=0)
{
    global $config;

    $show_extended_events = $item['show_extended_events'];

    if ($item['total_events'] != 0) {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->class = 'info_table';
        $table1->align = [];
        $table1->align[0] = 'left';
        $table1->align[1] = 'left';
        $table1->align[2] = 'left';
        $table1->align[3] = 'left';
        $table1->align[4] = 'left';
        $table1->align[5] = 'left';
        $table1->align[6] = 'left';
        $table1->align[7] = 'left';

        $table1->data = [];

        $table1->head = [];
        $table1->head[0] = __('Status');
        $table1->head[3] = __('Type');
        if ($item['show_summary_group']) {
            $table1->head[1] = __('Count');
        }

        $table1->head[2] = __('Name');
        $table1->head[4] = __('Severity');
        $table1->head[5] = __('Val. by');
        $table1->head[6] = __('Timestamp');
        if ((bool) $item['show_custom_data'] === true) {
            $table1->head[7] = __('Custom data');
        }

        foreach ($item['data'] as $i => $event) {
            $data = [];
            // Colored box.
            switch ($event['status']) {
                case 0:
                default:
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
                    'class' => 'image_status invert_filter',
                    'width' => 16,
                    'title' => $title_st,
                ]
            );

            if ($pdf) {
                $data[] = events_print_type_img_pdf($event['type'], true);
            } else {
                $data[] = events_print_type_img($event['type'], true);
            }

            if ($item['show_summary_group']) {
                $data[] = $event['count'];
            }

            $data[] = ui_print_truncate_text(
                io_safe_output($event['name']),
                140,
                false,
                true
            );

            $data[] = get_priority_name($event['criticity']);
            if (empty($event['validated_by']) && $event['status'] == EVENT_VALIDATE) {
                $data[] = '<i>'.__('System').'</i>';
            } else {
                $user_name = db_get_value('fullname', 'tusuario', 'id_user', $event['validated_by']);
                $data[] = io_safe_output($user_name);
            }

            if ($item['show_summary_group']) {
                $data[] = '<font class="font_6pt">'.date($config['date_format'], $event['timestamp']).'</font>';
            } else {
                $data[] = '<font class="font_6pt">'.date($config['date_format'], strtotime($event['timestamp'])).'</font>';
            }

            if ((bool) $item['show_custom_data'] === true) {
                $custom_data = json_decode($event['custom_data'], true);
                $custom_data_text = '';
                foreach ($custom_data as $key => $value) {
                    if (is_array($value)) {
                        $custom_data_text .= $key.' = ';
                        foreach ($value as $action) {
                            $custom_data_text .= $action.', ';
                        }

                        $custom_data_text = rtrim($custom_data_text, ', ').'<br>';
                    } else {
                        $custom_data_text .= $key.' = '.$value.'<br>';
                    }
                }

                $data[] = $custom_data_text;
            }

            array_push($table1->data, $data);

            if ($show_extended_events == 1 && events_has_extended_info($event['id_evento'])) {
                $extended_events = events_get_extended_events($event['id_evento']);

                foreach ($extended_events as $extended_event) {
                    $extended_data = [];

                    $extended_data[] = "<td colspan='4'><font class='italic'>".io_safe_output($extended_event['description'])."</font></td><td><font class='font_6pt italic'>".date($config['date_format'], $extended_event['utimestamp']).'</font></td>';
                    array_push($table1->data, $extended_data);
                }
            }
        }

        if ($pdf) {
            $table0 = new stdClass();
            $table0->width = '99%';
            $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
            $pdf_export = html_print_table($table0, true);

            $pdf_export .= html_print_table($table1, true);
            $pdf_export .= '<br>';
        } else {
            $table->colspan['event_list']['cell'] = 3;
            $table->cellstyle['event_list']['cell'] = 'text-align: center;';
            $table->data['event_list']['cell'] = html_print_table($table1, true);
        }

        if (!empty($item['chart']['by_user_validator'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events validated by user');
            $table1->data[0][0] = $item['chart']['by_user_validator'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_by_user_validator']['cell'] = 3;
                $table->cellstyle['chart_by_user_validator']['cell'] = 'text-align: center;';
                $table->data['chart_by_user_validator']['cell'] = html_print_table($table1, true);
            }
        }

        if (!empty($item['chart']['by_criticity'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Events by severity');
            $table1->data[0][0] = $item['chart']['by_criticity'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_by_criticity']['cell'] = 3;
                $table->cellstyle['chart_by_criticity']['cell'] = 'text-align: center;';
                $table->data['chart_by_criticity']['cell'] = html_print_table($table1, true);
            }
        }

        if (!empty($item['chart']['validated_vs_unvalidated'])) {
            $table1 = new stdClass();
            $table1->width = '99%';
            $table1->head = [];
            $table1->head[0] = __('Amount events validated');
            $table1->data[0][0] = $item['chart']['validated_vs_unvalidated'];

            if ($pdf) {
                $pdf_export .= html_print_table($table1, true);
                $pdf_export .= '<br>';
            } else {
                $table->colspan['chart_validated_vs_unvalidated']['cell'] = 3;
                $table->cellstyle['chart_validated_vs_unvalidated']['cell'] = 'text-align: center;';
                $table->data['chart_validated_vs_unvalidated']['cell'] = html_print_table($table1, true);
            }
        }

        if ($pdf) {
            return $pdf_export;
        }
    } else {
        if ($pdf) {
            $table0 = new stdClass();
            $table0->width = '99%';
            $table0->data['count_row']['count'] = 'Total events: '.$item['total_events'];
            $pdf_export = html_print_table($table0, true);

            return $pdf_export;
        }
    }
}


/**
 * Function to print to HTML historical data report.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_historical_data($table, $item, $pdf=0)
{
    global $config;

    $table1 = new stdClass();
    $table1->width = '100%';
    $table1->class = 'info_table';
    $table1->head = [
        __('Date'),
        __('Data'),
    ];
    $table1->data = [];
    foreach ($item['data'] as $data) {
        if (!is_numeric($data[__('Data')])) {
            if (is_snapshot_data($data[__('Data')])) {
                if ($config['command_snapshot']) {
                    $row = [
                        $data[__('Date')],
                        '<img class="w300px" src="'.io_safe_input($data[__('Data')]).'"></a>',
                    ];
                } else {
                    $row = [
                        $data[__('Date')],
                        wordwrap(io_safe_input($data[__('Data')]), 60, "<br>\n", true),
                    ];
                }
            } else {
                // Command line snapshot.
                if (is_text_to_black_string($data[__('Data')])) {
                    $table1->style[1] = 'text-align: left;';
                    $row = [
                        $data[__('Date')],
                        '<pre>'.$data[__('Data')].'</pre>',
                    ];
                } else {
                    $row = [
                        $data[__('Date')],
                        $data[__('Data')],
                    ];
                }
            }
        } else {
            $row = [
                $data[__('Date')],
                remove_right_zeros(number_format($data[__('Data')], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])),
            ];
        }

        $table1->data[] = $row;
    }

    if ($pdf === 0) {
        $table->colspan['database_serialized']['cell'] = 3;
        $table->cellstyle['database_serialized']['cell'] = 'text-align: center;';
        $table->data['database_serialized']['cell'] = html_print_table(
            $table1,
            true
        );

        return html_print_table($table, true);
    } else {
        $table1->title = $item['title'];
        $table1->titleclass = 'title_table_pdf';
        $table1->titlestyle = 'text-align:left;';

        return html_print_table($table1, true);
    }

}


/**
 * It displays an item in the table format report from
 * the data stored within the table named 'tagente_datos_stringin'
 * the Pandora FMS Database.
 * For it, the agent should serialize the data separating
 * them with a line-separating character and another
 * which separates the fields. All lines should contain all fields.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_database_serialized($table, $item, $pdf=0)
{
    global $config;

    $table1 = new stdClass();
    $table1->width = '100%';
    $table1->class = 'info_table';
    $table1->head = [
        __('Date'),
        __('Data'),
    ];
    if (!empty($item['keys'])) {
        $table1->head = array_merge($table1->head, $item['keys']);
    }

    $table1->style[0] = 'text-align: center';

    $table1->data = [];
    foreach ($item['data'] as $data) {
        foreach ($data['data'] as $data_unserialized) {
            $row = [$data['date']];
            foreach ($data_unserialized as $key => $data_value) {
                if (is_snapshot_data($data_unserialized[$key])) {
                    if ($config['command_snapshot']) {
                        $data_unserialized[$key] = '<img class="w300px" src="'.io_safe_input($data_value).'"></a>';
                    } else {
                        $data_unserialized[$key] = wordwrap(io_safe_input($data_value), 60, "<br>\n", true);
                    }
                } else if (is_text_to_black_string($data_unserialized[$key])) {
                    $table1->style[1] = 'white-space: pre-wrap;';
                    $table1->style[1] .= 'text-align: left';
                    $data_unserialized[$key] = '<pre>'.$data_value.'</pre>';
                }
            }

            $row = array_merge($row, $data_unserialized);
            $table1->data[] = $row;
        }
    }

    if ($pdf === 0) {
        $table->colspan['database_serialized']['cell'] = 3;
        $table->cellstyle['database_serialized']['cell'] = 'text-align: center;';
        $table->data['database_serialized']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        $table1->title = $item['title'];
        $table1->titleclass = 'title_table_pdf';
        $table1->titlestyle = 'text-align:left;';
        return html_print_table(
            $table1,
            true
        );
    }
}


/**
 * Show last value and state of module.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string Html code.
 */
function reporting_html_last_value($table, $item, $pdf=0)
{
    global $config;

    if (empty($item['data']) === false) {
        $table_data = new stdClass();
        $table_data->width = '100%';
        $table_data->class = 'info_table';
        $table_data->headstyle = [];
        $table_data->headstyle[0] = 'text-align: left;';
        $table_data->style = [];
        $table_data->style[0] = 'text-align: left;';
        $table_data->head = [
            __('Name'),
            __('Date'),
            __('Data'),
            __('Status'),
        ];

        $table_data->data = [];
        $table_data->data[1][0] = $item['data']['agent_name'];
        $table_data->data[1][0] .= ' / ';
        $table_data->data[1][0] .= $item['data']['module_name'];

        $table_data->data[1][1] = date(
            'Y-m-d H:i:s',
            $item['data']['utimestamp']
        );

        if (is_numeric($item['data']['datos']) === true) {
            $dataDatos = remove_right_zeros(
                number_format(
                    $item['data']['datos'],
                    $config['graph_precision'],
                    $config['decimal_separator'],
                    $config['thousand_separator']
                )
            );
        } else {
            $dataDatos = trim($item['data']['datos']);
        }

        $table_data->data[1][2] = $dataDatos;

        switch ($item['data']['estado']) {
            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                $img_status = ui_print_status_image(
                    'module_critical.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;

            case AGENT_MODULE_STATUS_WARNING:
                $img_status = ui_print_status_image(
                    'module_warning.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;

            case AGENT_MODULE_STATUS_UNKNOWN:
                $img_status = ui_print_status_image(
                    'module_unknown.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;

            case AGENT_MODULE_STATUS_NORMAL_ALERT:
            case AGENT_MODULE_STATUS_WARNING_ALERT:
            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                $img_status = ui_print_status_image(
                    'module_alertsfired.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;

            case 4:
                $img_status = ui_print_status_image(
                    'module_no_data.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;

            default:
            case AGENT_MODULE_STATUS_NORMAL:
                $img_status = ui_print_status_image(
                    'module_ok.png',
                    $item['data']['datos'],
                    true,
                    [
                        'width'  => '50px',
                        'height' => '20px',
                        'style'  => 'border-radius:5px;',
                    ],
                    'images/status_sets/default/'
                );
            break;
        }

        $table_data->data[1][3] = $img_status;

        if ($pdf === 0) {
            $table->colspan['last_value']['cell'] = 3;
            $table->cellstyle['last_value']['cell'] = 'text-align: center;';
            $table->data['last_value']['cell'] = html_print_table(
                $table_data,
                true
            );
        } else {
            return html_print_table(
                $table_data,
                true
            );
        }
    } else {
        // TODO:XXX
    }
}


/**
 * Shows the data of a group and the agents that are part of them.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_group_configuration($table, $item, $pdf=0)
{
    $cell = '';
    foreach ($item['data'] as $agent) {
        if ($pdf === 0) {
            $table2 = new stdClass();
            $table2->width = '100%';
            $table2->class = 'info_table';
            $table2->data = [];
            reporting_html_agent_configuration(
                $table2,
                ['data' => $agent],
                $pdf
            );
            $cell .= html_print_table(
                $table2,
                true
            );
        } else {
            $cell .= reporting_html_agent_configuration(
                false,
                ['data' => $agent],
                $pdf,
                $item['title']
            );
        }
    }

    if ($pdf === 0) {
        $table->colspan['group_configuration']['cell'] = 3;
        $table->cellstyle['group_configuration']['cell'] = 'text-align: center;';
        $table->data['group_configuration']['cell'] = $cell;
    } else {
        return $cell;
    }
}


/**
 * Html output report alert actions
 *
 * @param object  $table Table.
 * @param array   $item  Data for draw report.
 * @param integer $pdf   PDF output.
 *
 * @return string Html output.
 */
function reporting_html_alert_report_actions($table, $item, $pdf=0)
{
    $data = $item['data'];
    $groupsBy = $item['groupsBy'];

    $output = '';
    if (isset($data['data']) === true
        && empty($data['data']) === false
    ) {
        foreach ($data['data'] as $period => $data_array) {
            if (empty($period) === false) {
                $output .= '<h1 class="h1-report-alert-actions">';
                $output .= __('From').' ';
                $output .= date(
                    'd-m-Y H:i:s',
                    $period
                );
                $output .= ' '.__('to').' ';
                $output .= date('d-m-Y H:i:s', ($period + (int) $groupsBy['lapse']));
                $output .= '</h1>';
            }

            $output .= get_alert_table($data_array);
        }

        if (isset($data['summary']) === true
            && empty($data['summary']) === false
        ) {
            $output .= '<h1 class="h1-report-alert-actions">';
            $output .= __('Total summary');
            $output .= '</h1>';

            $output .= get_alert_table($data['summary']);
        }
    } else {
        $output .= ui_print_empty_data(
            __('No alerts fired'),
            '',
            true
        );
    }

    if ($pdf === 0) {
        $table->colspan['alert_report_action']['cell'] = 3;
        $table->cellstyle['alert_report_action']['cell'] = 'text-align: center;';
        $table->data['alert_report_action']['cell'] = $output;
    } else {
        return $output;
    }

}


/**
 * Draw alert action table.
 *
 * @param array $data Data.
 *
 * @return string Html output.
 */
function get_alert_table($data)
{
    $table = new StdCLass();
    $table->width = '99%';
    $table->class = 'info_table';
    $table->data = [];
    $table->head = [];
    $table->headstyle = [];
    $table->cellstyle = [];
    $table->headstyle[0] = 'text-align:left;';
    $table->size[0] = '25%';
    $table->size[1] = '12%';
    $table->size[2] = '12%';
    $table->size[3] = '12%';
    $table->size[4] = '12%';
    $table->size[5] = '12%';
    $table->size[6] = '12%';

    $head = reset($data);
    foreach (array_reverse(array_keys($head)) as $name) {
        $table->head[] = ucfirst($name);
    }

    foreach ($data as $key => $params) {
        $table->cellstyle[$key][0] = 'text-align:left;';
        foreach (array_reverse($params) as $name => $value) {
            $table->data[$key][] = $value;
        }
    }

    return html_print_table($table, true);
}


/**
 * This type of report element will generate the interface graphs
 * of all those devices that belong to the selected group.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_network_interfaces_report($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (empty($item['failed']) === false) {
        if ($pdf === 0) {
            $table->colspan['interfaces']['cell'] = 3;
            $table->cellstyle['interfaces']['cell'] = 'text-align: left;';
            $table->data['interfaces']['cell'] = $item['failed'];
        } else {
            $return_pdf .= $item['failed'];
        }
    } else {
        foreach ($item['data'] as $agent) {
            $table_agent = new StdCLass();
            $table_agent->width = '100%';
            $table_agent->class = 'info_table';
            $table_agent->data = [];
            $table_agent->head = [];
            $table_agent->head[0] = __('Agent').' '.$agent['agent'];
            $table_agent->headstyle = [];
            $table_agent->style[0] = 'text-align: center';

            $table_agent->data['interfaces'] = '';

            foreach ($agent['interfaces'] as $interface) {
                $table_interface = new StdClass();
                $table_interface->width = '100%';
                $table_interface->data = [];
                $table_interface->rowstyle = [];
                $table_interface->head = [];
                $table_interface->cellstyle = [];
                $table_interface->title = sprintf(
                    __("Interface '%s' throughput graph"),
                    $interface['name']
                );
                $table_interface->head['ip'] = __('IP');
                $table_interface->head['mac'] = __('Mac');
                $table_interface->head['status'] = __('Actual status');
                $table_interface->style['ip'] = 'text-align: center';
                $table_interface->style['mac'] = 'text-align: center';
                $table_interface->style['status'] = 'width: 150px; text-align: center';

                $data = [];
                $data['ip'] = !empty($interface['ip']) ? $interface['ip'] : '--';
                $data['mac'] = !empty($interface['mac']) ? $interface['mac'] : '--';
                $data['status'] = $interface['status_image'];
                $table_interface->data['data'] = $data;

                if (!empty($interface['chart'])) {
                    $table_interface->data['graph'] = $interface['chart'];
                    $table_interface->colspan['graph'][0] = 3;
                    $table_interface->cellstyle['graph'][0] = 'text-align: center;';
                }

                if ($pdf !== 0) {
                    $table_interface->title = $item['title'].' '.__('Agents').': '.$agent['agent'];
                    $table_interface->titleclass = 'title_table_pdf';
                    $table_interface->titlestyle = 'text-align:left;';
                    $table_interface->styleTable = 'page-break-inside:avoid;';

                    $return_pdf .= html_print_table(
                        $table_interface,
                        true
                    );
                }

                $table_agent->data['interfaces'] .= html_print_table(
                    $table_interface,
                    true
                );
                $table_agent->colspan[$interface_name][0] = 3;
            }

            $id = uniqid();
            $table->colspan[$id][0] = 3;
            $table->data[$id] = html_print_table(
                $table_agent,
                true
            );
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * This type of report element will generate the interface graphs
 * of all those devices that belong to the selected group.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_custom_render($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (empty($item['failed']) === false) {
        if ($pdf === 0) {
            $table->colspan['interfaces']['cell'] = 3;
            $table->cellstyle['interfaces']['cell'] = 'text-align: left;';
            $table->data['interfaces']['cell'] = $item['failed'];
        } else {
            $return_pdf .= $item['failed'];
        }
    } else {
        $output = '<div id="reset-styles">';
        $output .= $item['data'];
        $output .= '</div>';

        if ($pdf === 1) {
            $return_pdf .= $output;
        } else {
            $id = uniqid();
            $table->colspan[$id][0] = 3;
            $table->data[$id] = $output;
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Unified alert report HTML
 */
function reporting_html_alert_report($table, $item, $pdf=0)
{
    $table->colspan['alerts']['cell'] = 3;
    $table->cellstyle['alerts']['cell'] = 'text-align: left;';

    $table1 = new stdClass();
    $table1->width   = '99%';
    $table1->class = 'info_table';
    $table1->head    = [];
    $table1->data    = [];
    $table1->rowspan = [];
    $table1->valign  = [];

    if ($item['data'] == null) {
        $table->data['alerts']['cell'] = ui_print_empty_data(
            __('No alerts defined'),
            '',
            true
        );
    }

    $table1->head['agent']    = __('Agent');
    $table1->head['module']   = __('Module');
    $table1->head['template'] = __('Template');
    $table1->head['actions']  = __('Actions');
    $table1->head['fired']    = __('Action').' '.__('Fired');
    $table1->head['tfired']   = __('Template').' '.__('Fired');
    $table1->valign['agent']    = 'top';
    $table1->valign['module']   = 'top';
    $table1->valign['template'] = 'top';
    $table1->valign['actions']  = 'top';
    $table1->valign['fired']    = 'top';
    $table1->valign['tfired']   = 'top';

    $td = 0;
    foreach ($item['data'] as $information) {
        $row = [];

        $td = count($information['alerts']);

        $row['agent'] = $information['agent'];
        $row['module'] = $information['module'];

        foreach ($information['alerts'] as $alert) {
            $row['template'] = $alert['template'];
            $row['actions']  = '';
            $row['fired']    = '';
            foreach ($alert['actions'] as $action) {
                if ($action['name'] == '') {
                    // Removed from retrieved hash.
                    continue;
                }

                $row['actions'] .= '<div class="w100p">'.$action['name'].'</div>';
                if (is_numeric($action['fired'])) {
                    $row['fired'] .= '<div class="w100p">'.date('Y-m-d H:i:s', $action['fired']).'</div>';
                } else {
                    $row['fired'] .= '<div class="w100p">'.$action['fired'].'</div>';
                }
            }

            $row['tfired'] = '';
            foreach ($alert['template_fired'] as $fired) {
                $row['tfired'] .= '<div class="w100p">'.$fired.'</div>'."\n";
            }

            // Skip first td's to avoid repeat the agent and module names.
            $table1->data[] = $row;
            if ($td > 1) {
                for ($i = 0; $i < $td; $i++) {
                    $row['agent']  = '';
                    $row['module'] = '';
                }
            }
        }
    }

    $table->data['alerts']['cell'] = html_print_table($table1, true);
    if ($pdf) {
        $table1->class = 'info_table';
        return html_print_table($table1, true);
    }
}


/**
 * This type of report element allows custom graphs to be defined
 * for use in reports.
 * These graphs will be created using SQL code entered by the user.
 * This SQL code should always return a variable called "label"
 * for the text labels or name of the elements to be displayed
 * and a field called "value" to store the numerical value to be represented.
 *
 * @param object $table Parameters table.
 * @param array  $item  Items data.
 *
 * @return void
 */
function reporting_html_sql_graph($table, $item)
{
    $table->colspan['chart']['cell'] = 3;
    $table->cellstyle['chart']['cell'] = 'text-align: center;';
    $table->data['chart']['cell'] = $item['chart'];
}


/**
 * It shows the percentage of time a module has been
 * right or wrong within a predefined period.
 *
 * @param object  $table Parameters table.
 * @param array   $item  Items data.
 * @param boolean $mini  True or flase.
 * @param integer $pdf   Values 0 or 1.
 *
 * @return mixed
 */
function reporting_html_monitor_report($table, $item, $mini, $pdf=0)
{
    global $config;

    if ($mini) {
        $font_size = '1.5em';
    } else {
        $font_size = $config['font_size_item_report'].'em';
    }

    $table->colspan['module']['cell'] = 3;
    $table->cellstyle['module']['cell'] = 'text-align: center;';

    $table1 = new stdClass();
    $table1->width = '99%';
    $table1->class = 'info_table';
    $table1->head = [];
    $table1->data = [];
    if ($item['data']['unknown'] == 1) {
        $table1->data['data']['unknown'] = '<p class="bolder" style="font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_UNKNOWN.';">';
        $table1->data['data']['unknown'] .= __('Unknown').'</p>';
    } else {
        $table1->data['data']['ok'] = '<p class="bolder" style="font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_NORMAL.';">';
        $table1->data['data']['ok'] .= html_print_image(
            'images/module_ok.png',
            true
        ).' '.__('OK').': '.remove_right_zeros(
            number_format(
                $item['data']['ok']['value'],
                $config['graph_precision'],
                $config['decimal_separator'],
                $config['thousand_separator']
            )
        ).' %</p>';

        $table1->data['data']['fail'] = '<p class="bolder" style="font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.COL_CRITICAL.';">';
        $table1->data['data']['fail'] .= html_print_image(
            'images/module_critical.png',
            true
        ).' '.__('Not OK').': '.remove_right_zeros(
            number_format(
                $item['data']['fail']['value'],
                $config['graph_precision'],
                $config['decimal_separator'],
                $config['thousand_separator']
            )
        ).' % '.'</p>';
    }

    if ($pdf === 0) {
        $table->data['module']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        return html_print_table(
            $table1,
            true
        );
    }
}


/**
 * Print report html.
 *
 * @param object $table Parameters table.
 * @param array  $item  Items data.
 *
 * @return mixed
 */
function reporting_html_graph($table, $item)
{
    $table->colspan['chart']['cell'] = 3;
    $table->cellstyle['chart']['cell'] = 'text-align: center;';
    $table->data['chart']['cell'] = $item['chart'];
}


/**
 * Print report prediction date.
 *
 * @param object  $table Parameters table.
 * @param array   $item  Items data.
 * @param boolean $mini  True or False.
 *
 * @return mixed
 */
function reporting_html_prediction_date($table, $item, $mini)
{
    reporting_html_value($table, $item, $mini, true);
}


/**
 * Shows the data of agents and modules.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 * @param string  $title Show title pdf.
 *
 * @return string HTML code.
 */
function reporting_html_agent_configuration(
    $table,
    $item,
    $pdf=0,
    $title=''
) {
    $return_pdf = '';

    $table1 = new stdClass();
    $table1->width = '99%';
    $table1->head = [];
    $table1->class = 'info_table';
    $table1->head['name'] = __('Agent name');
    $table1->head['group'] = __('Group');
    $table1->head['os'] = __('OS');
    $table1->head['address'] = __('IP');
    $table1->head['description'] = __('Description');
    $table1->head['status'] = __('Status');
    $table1->data = [];

    $row = [];
    $row['name'] = $item['data']['name'];
    $row['group'] = groups_get_name($item['data']['group'], true);
    $row['address'] = $item['data']['os_icon'];
    $row['os'] = $item['data']['address'];
    $row['description'] = $item['data']['description'];
    if ($item['data']['enabled']) {
        $row['status'] = __('Enabled');
    } else {
        $row['status'] = __('Disabled');
    }

    $table1->data[] = $row;

    if ($pdf === 0) {
        $table->colspan['agent']['cell'] = 3;
        $table->cellstyle['agent']['cell'] = 'text-align: left;';
        $table->data['agent']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        $return_pdf .= html_print_table(
            $table1,
            true
        );
    }

    if ($pdf === 0) {
        $table->colspan['modules']['cell'] = 3;
        $table->cellstyle['modules']['cell'] = 'text-align: left;';
    }

    if (empty($item['data']['modules'])) {
        if ($pdf === 0) {
            $table->data['modules']['cell'] = __('Empty modules');
        } else {
            $return_pdf .= __('Empty modules');
        }
    } else {
        $table1->width = '99%';
        $table1->head = [];
        $table1->head['name'] = __('Name');
        $table1->head['threshold'] = __('Threshold');
        $table1->head['description'] = __('Description');
        $table1->head['interval'] = __('Interval');
        $table1->head['unit'] = __('Unit');
        $table1->head['status'] = __('Status');
        $table1->head['tags'] = __('Tags');
        $table1->align = [];
        $table1->align[] = 'left';
        $table1->data = [];

        foreach ($item['data']['modules'] as $module) {
            $row = [];

            $row['name'] = $module['name'];
            $row['threshold'] = $module['threshold'];
            $row['description'] = $module['description'];
            $row['interval'] = $module['interval'];
            $row['unit'] = $module['unit'];
            $row['status'] = ($pdf === 0) ? $module['status_icon'] : explode(':', $module['status'])[0];
            $row['tags'] = implode(',', $module['tags']);

            $table1->data[] = $row;
        }

        if ($pdf === 0) {
            $table->data['modules']['cell'] = html_print_table($table1, true);
        } else {
            if ($title !== '') {
                $item['title'] = $title;
            }

            $table1->title = $item['title'].' '.__('Agent').': '.$item['data']['name'];
            $table1->titleclass = 'title_table_pdf';
            $table1->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table(
                $table1,
                true
            );
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


function reporting_html_sum_value(&$table, $item, $mini)
{
    reporting_html_value($table, $item, $mini);
}


function reporting_html_avg_value(&$table, $item, $mini)
{
    reporting_html_value($table, $item, $mini);
}


function reporting_html_max_value(&$table, $item, $mini)
{
    reporting_html_value($table, $item, $mini);
}


function reporting_html_min_value(&$table, $item, $mini)
{
    reporting_html_value($table, $item, $mini);
}


/**
 * Htlm report AVg, min, Max, Only.
 *
 * @param array   $table       Table.
 * @param array   $item        Data.
 * @param boolean $mini        Is mini.
 * @param boolean $only_value  Only value.
 * @param boolean $check_empty Empty.
 * @param integer $pdf         PDF Mode.
 *
 * @return string Html output.
 */
function reporting_html_value(
    $table,
    $item,
    $mini,
    $only_value=false,
    $check_empty=false,
    $pdf=0
) {
    global $config;

    if ($mini) {
        $font_size = '1.5em';
    } else {
        $font_size = $config['font_size_item_report'].'em';
    }

    $return_pdf = '';

    if (isset($item['visual_format']) === true && $item['visual_format'] != 0
        && ($item['type'] == 'max_value'
        || $item['type'] == 'min_value'
        || $item['type'] == 'avg_value')
    ) {
        $table2 = new stdClass();
        $table2->width = '100%';
        $table2->class = 'info_table';
        switch ($item['type']) {
            case 'max_value':
                $table2->head = [
                    __('Agent'),
                    __('Module'),
                    __('Maximun'),
                ];
            break;

            case 'min_value':
                $table2->head = [
                    __('Agent'),
                    __('Module'),
                    __('Minimun'),
                ];
            break;

            case 'avg_value':
            default:
                $table2->head = [
                    __('Agent'),
                    __('Module'),
                    __('Average'),
                ];
            break;
        }

        $table2->data = [];

        $data = $item['data'][0];

        $row = [
            $data[__('Agent')],
            $data[__('Module')],
            $data[__('Maximun')],
        ];

        $table2->data[] = $row;

        $table2->title = $item['title'];
        $table2->titleclass = 'title_table_pdf';
        $table2->titlestyle = 'text-align:left;';
        $table->colspan[1][0] = 3;
        $table->colspan[2][0] = 3;
        $table->colspan[3][0] = 3;

        if ($pdf === 0) {
            array_push($table->data, html_print_table($table2, true));
        } else {
            $return_pdf .= html_print_table($table2, true);
        }

        unset($item['data'][0]);

        if ($item['visual_format'] != 1) {
            $value = $item['data'][1]['value'];
            if ($pdf === 0) {
                array_push($table->data, $value);
            } else {
                $style_div_pdf = 'text-align:center;margin-bottom:20px;';
                $return_pdf .= '<div style="'.$style_div_pdf.'">';
                $return_pdf .= $value;
                $return_pdf .= '</div>';
            }
        }

        unset($item['data'][1]);

        if ($item['visual_format'] != 2) {
            $table1 = new stdClass();
            $table1->width = '100%';
            $table1->headstyle[0] = 'text-align:left';
            $table1->headstyle[1] = 'text-align:left';
            switch ($item['type']) {
                case 'max_value':
                    $table1->head = [
                        __('Lapse'),
                        __('Maximun'),
                    ];
                break;

                case 'min_value':
                    $table1->head = [
                        __('Lapse'),
                        __('Minimun'),
                    ];
                break;

                case 'avg_value':
                default:
                    $table1->head = [
                        __('Lapse'),
                        __('Average'),
                    ];
                break;
            }

            $table1->data = [];
            $row = [];
            foreach ($item['data'] as $data) {
                if (is_numeric($data[__('Maximun')]) === false) {
                    $row = [
                        $data[__('Lapse')],
                        $data[__('Maximun')],
                    ];
                } else {
                    $row = [
                        $data[__('Lapse')],
                        remove_right_zeros(
                            number_format(
                                $data[__('Maximun')],
                                $config['graph_precision'],
                                $config['decimal_separator'],
                                $config['thousand_separator']
                            )
                        ),
                    ];
                }

                $table1->data[] = $row;
            }

            $table1->title = $item['title'];
            $table1->titleclass = 'title_table_pdf';
            $table1->titlestyle = 'text-align:left;';
            if ($pdf === 0) {
                array_push($table->data, html_print_table($table1, true));
            } else {
                $return_pdf .= html_print_table($table1, true);
            }
        }

        if ($pdf !== 0) {
            return $return_pdf;
        }
    } else {
        if ($pdf !== 0) {
            $table = new stdClass();
            $table->width = '100%';
        }

        $table->colspan['data']['cell'] = 3;
        $table->cellstyle['data']['cell'] = 'text-align: left;';

        $table->data['data']['cell'] = '<p class="bolder" style="font-size: '.$font_size.'; color: #000000;">';

        if ($check_empty && empty($item['data']['value'])) {
            $table->data['data']['cell'] .= __('Unknown');
        } else if ($only_value) {
            $table->data['data']['cell'] .= $item['data']['value'];
        } else {
            $table->data['data']['cell'] .= $item['data']['formated_value'];
        }

        $table->data['data']['cell'] .= '</p>';

        if ($pdf !== 0) {
            return html_print_table($table, true);
        }
    }
}


/**
 * Show a brief analysis in which the variation of the value
 * of the indicated module is indicated.
 *
 * @param string  $table Reference table in pdf a false.
 * @param array   $item  Parameters for item pdf.
 * @param boolean $pdf   Send pdf.
 *
 * @return string HTML code.
 */
function reporting_html_increment($table, $item, $pdf=0)
{
    global $config;

    $return_pdf = '';

    if (isset($item['data']['error'])) {
        if ($pdf === 0) {
            $table->colspan['error']['cell'] = 3;
            $table->data['error']['cell'] = $item['data']['message'];
        } else {
            $return_pdf .= $item['data']['message'];
        }
    } else {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->data = [];

        $table1->head = [];
        $table1->head[0] = __('Agent');
        $table1->head[1] = __('Module');
        $table1->head[2] = __('From');
        $table1->head[3] = __('To');
        $table1->head[4] = __('From data');
        $table1->head[5] = __('To data');
        $table1->head[6] = __('Increment');

        $table1->headstyle = [];
        $table1->headstyle[0]  = 'text-align: left';
        $table1->headstyle[1]  = 'text-align: left';
        $table1->headstyle[2]  = 'text-align: left';
        $table1->headstyle[3]  = 'text-align: left';
        $table1->headstyle[4]  = 'text-align: right';
        $table1->headstyle[5]  = 'text-align: right';
        $table1->headstyle[6]  = 'text-align: right';

        $table1->style[0]  = 'text-align: left';
        $table1->style[1]  = 'text-align: left';
        $table1->style[2]  = 'text-align: left';
        $table1->style[3]  = 'text-align: left';
        $table1->style[4]  = 'text-align: right';
        $table1->style[5]  = 'text-align: right';
        $table1->style[6]  = 'text-align: right';

        $table1_row = [];
        $table1_row[0] = agents_get_alias($item['id_agent']);
        $table1_row[1] = modules_get_agentmodule_name($item['id_agent_module']);
        $table1_row[2] = date('F j, Y, G:i', $item['from']);
        $table1_row[3] = date('F j, Y, G:i', $item['to']);
        $table1_row[4] = $item['data']['old'];
        $table1_row[5] = $item['data']['now'];
        if ($item['data']['inc'] == 'negative') {
            $table1_row[6] = __('Negative increase: ').$item['data']['inc_data'];
        } else if ($item['data']['inc'] == 'positive') {
            $table1_row[6] = __('Positive increase: ').$item['data']['inc_data'];
        } else {
            $table1_row[6] = __('Neutral increase: ').$item['data']['inc_data'];
        }

        $table1->data[] = $table1_row;

        if ($pdf === 0) {
            $data = [];
            $data[0] = html_print_table($table1, true);
            array_push($table->data, $data);
        } else {
            $return_pdf = html_print_table($table1, true);
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


function reporting_html_url(&$table, $item, $key)
{
    $table->colspan['data']['cell'] = 3;
    $table->cellstyle['data']['cell'] = 'text-align: left;';
    $table->data['data']['cell'] = '
        <iframe id="item_'.$key.'" src ="'.$item['url'].'" width="100%" height="100%">
        </iframe>';
    // TODO: make this dynamic and get the height if the iframe to resize this item.
    $table->data['data']['cell'] .= '
        <script type="text/javascript">
            $(document).ready (function () {
                $("#item_'.$key.'").height(500);
            });
        </script>';
}


function reporting_html_text(&$table, $item)
{
    $table->colspan['data']['cell'] = 3;
    $table->cellstyle['data']['cell'] = 'text-align: left;';
    $table->data['data']['cell'] = $item['data'];
}


/**
 * Report availability
 *
 * @param string  $table Reference table in pdf a false.
 * @param array   $item  Parameters for item pdf.
 * @param boolean $pdf   Send pdf.
 *
 * @return string HTML code.
 */
function reporting_html_availability($table, $item, $pdf=0)
{
    $return_pdf = '';

    $style = db_get_value(
        'style',
        'treport_content',
        'id_rc',
        $item['id_rc']
    );

    $style = json_decode(
        io_safe_output($style),
        true
    );
    $same_agent_in_resume = '';

    global $config;

    $font_size = $config['font_size_item_report'].'em';
    $interval_description = $config['interval_description'];

    if (empty($item['data']) === false) {
        $table1 = new stdClass();
        $table1->width = '99%';
        $table1->class = 'info_table';
        $table1->data = [];

        $table1->head = [];
        if (isset($item['data'][0]['failover']) === true) {
            $table1->head[-1] = __('Failover');
        }

        $table1->head[0] = __('Agent');
        // HACK it is saved in show_graph field.
        // Show interfaces instead the modules.
        if ($item['kind_availability'] == 'address') {
            $table1->head[1] = __('IP Address');
        } else {
            $table1->head[1] = __('Module');
        }

        if ($item['fields']['total_time']) {
            $table1->head[2] = __('Total time');
        } else {
            $table1->head[2] = __('');
        }

        if ($item['fields']['time_failed']) {
            $table1->head[3] = __('Time failed');
        } else {
            $table1->head[3] = __('');
        }

        if ($item['fields']['time_in_ok_status']) {
            $table1->head[4] = __('Time OK');
        } else {
            $table1->head[4] = __('');
        }

        if ($item['fields']['time_in_warning_status']) {
            $table1->head[5] = __('Time in warning status');
        } else {
            $table1->head[5] = __('');
        }

        if ($item['fields']['time_in_unknown_status']) {
            $table1->head[6] = __('Time Unknown');
        } else {
            $table1->head[6] = __('');
        }

        if ($item['fields']['time_of_not_initialized_module']) {
            $table1->head[7] = __('Time Not Init Module');
        } else {
            $table1->head[7] = __('');
        }

        if ($item['fields']['time_of_downtime']) {
            $table1->head[8] = __('Time Downtime');
        } else {
            $table1->head[8] = __('');
        }

        $table1->head[9] = __('% Ok');

        $table1->headstyle = [];
        if (isset($item['data'][0]['failover']) === true) {
            $table1->headstyle[-1]  = 'text-align: left';
        }

        $table1->headstyle[0]  = 'text-align: left';
        $table1->headstyle[1]  = 'text-align: left';
        $table1->headstyle[2]  = 'text-align: center';
        $table1->headstyle[3]  = 'text-align: center';
        $table1->headstyle[4]  = 'text-align: center';
        $table1->headstyle[5]  = 'text-align: center';
        $table1->headstyle[6]  = 'text-align: center';
        $table1->headstyle[7]  = 'text-align: right';
        $table1->headstyle[8]  = 'text-align: right';
        $table1->headstyle[9]  = 'text-align: right';

        if (isset($item['data'][0]['failover']) === true) {
            $table1->style[-1]  = 'text-align: left';
        }

        $table1->style[0]  = 'text-align: left';
        $table1->style[1]  = 'text-align: left';
        $table1->style[2]  = 'text-align: center';
        $table1->style[3]  = 'text-align: center';
        $table1->style[4]  = 'text-align: center';
        $table1->style[5]  = 'text-align: center';
        $table1->style[6]  = 'text-align: center';
        $table1->style[7]  = 'text-align: right';
        $table1->style[8]  = 'text-align: right';
        $table1->style[9]  = 'text-align: right';
        $table1->style[10] = 'text-align: right';

        $table2 = new stdClass();
        $table2->width = '99%';
        $table2->data = [];

        $table2->head = [];
        if (isset($item['data'][0]['failover']) === true) {
            $table2->head[-1] = __('Failover');
        }

        $table2->head[0] = __('Agent');
        // HACK it is saved in show_graph field.
        // Show interfaces instead the modules.
        if ($item['kind_availability'] == 'address') {
            $table2->head[1] = __('IP Address');
        } else {
            $table2->head[1] = __('Module');
        }

        if ($item['fields']['total_checks']) {
            $table2->head[2] = __('Total checks');
        } else {
            $table2->head[2] = __('');
        }

        if ($item['fields']['checks_failed']) {
            $table2->head[3] = __('Checks failed');
        } else {
            $table2->head[3] = __('');
        }

        if ($item['fields']['checks_in_ok_status']) {
            $table2->head[4] = __('Checks OK');
        } else {
            $table2->head[4] = __('');
        }

        if ($item['fields']['checks_in_warning_status']) {
            $table2->head[5] = __('Checks Warning');
        } else {
            $table2->head[5] = __('');
        }

        if ($item['fields']['unknown_checks']) {
            $table2->head[6] = __('Checks Uknown');
        } else {
            $table2->head[6] = __('');
        }

        $table2->headstyle = [];
        if (isset($item['data'][0]['failover']) === true) {
            $table2->headstyle[-1] = 'text-align: left';
        }

        $table2->headstyle[0] = 'text-align: left';
        $table2->headstyle[1] = 'text-align: left';
        $table2->headstyle[2] = 'text-align: left';
        if (isset($item['data'][0]['failover']) === true) {
            $table2->headstyle[2] = 'text-align: right';
        }

        $table2->headstyle[3] = 'text-align: right';
        $table2->headstyle[4] = 'text-align: right';
        $table2->headstyle[5] = 'text-align: right';
        $table2->headstyle[6] = 'text-align: right';

        if (isset($item['data'][0]['failover']) === true) {
            $table2->style[-1] = 'text-align: left';
        }

        $table2->style[0] = 'text-align: left';
        $table2->style[1] = 'text-align: left';
        $table2->style[2] = 'text-align: left';
        $table2->style[3] = 'text-align: right';
        $table2->style[4] = 'text-align: right';
        $table2->style[5] = 'text-align: right';
        $table2->style[6] = 'text-align: right';

        foreach ($item['data'] as $row) {
            $table_row = [];
            if (isset($row['failover']) === true) {
                switch ($row['failover']) {
                    case 'primary_compare':
                        $table_row[] = __('Primary').' (24x7)';
                    break;

                    case 'failover_compare':
                        $table_row[] = __('Failover').' (24x7)';
                    break;

                    case 'result_compare':
                        $table_row[] = __('Result').' (24x7)';
                    break;

                    default:
                        if (strpos($row['failover'], 'failover') !== false) {
                            $table_row[] = __('Failover');
                        } else {
                            $table_row[] = ucfirst($row['failover']);
                        }
                    break;
                }
            }

            if (isset($row['failover']) === true
                && ($row['failover'] === 'result'
                || $row['failover'] === 'result_compare')
            ) {
                $table_row[] = '--';
                $table_row[] = '--';
            } else {
                $table_row[] = $row['agent'];
                $item_name = $row['availability_item'];
                if ((bool) $row['compare'] === true) {
                    $item_name .= ' ('.__('24 x 7').')';
                }

                $table_row[] = $item_name;
            }

            if ($row['time_total'] != 0 && $item['fields']['total_time']) {
                $table_row[] = human_time_description_raw(
                    $row['time_total'],
                    true,
                    $interval_description
                );
            } else if ($row['time_total'] == 0
                && $item['fields']['total_time']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_error'] != 0 && $item['fields']['time_failed']) {
                $table_row[] = human_time_description_raw(
                    $row['time_error'],
                    true,
                    $interval_description
                );
            } else if ($row['time_error'] == 0
                && $item['fields']['time_failed']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_ok'] != 0 && $item['fields']['time_in_ok_status']) {
                $table_row[] = human_time_description_raw(
                    $row['time_ok'],
                    true,
                    $interval_description
                );
            } else if ($row['time_ok'] == 0
                && $item['fields']['time_in_ok_status']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_warning'] != 0 && $item['fields']['time_in_warning_status']) {
                $table_row[] = human_time_description_raw(
                    $row['time_warning'],
                    true,
                    $interval_description
                );
            } else if ($row['time_warning'] == 0
                && $item['fields']['time_in_warning_status']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_unknown'] != 0
                && $item['fields']['time_in_unknown_status']
            ) {
                $table_row[] = human_time_description_raw(
                    $row['time_unknown'],
                    true,
                    $interval_description
                );
            } else if ($row['time_unknown'] == 0
                && $item['fields']['time_in_unknown_status']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_not_init'] != 0
                && $item['fields']['time_of_not_initialized_module']
            ) {
                $table_row[] = human_time_description_raw(
                    $row['time_not_init'],
                    true,
                    $interval_description
                );
            } else if ($row['time_not_init'] == 0
                && $item['fields']['time_of_not_initialized_module']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            if ($row['time_downtime'] != 0
                && $item['fields']['time_of_downtime']
            ) {
                $table_row[] = human_time_description_raw(
                    $row['time_downtime'],
                    true,
                    $interval_description
                );
            } else if ($row['time_downtime'] == 0
                && $item['fields']['time_of_downtime']
            ) {
                $table_row[] = '--';
            } else {
                $table_row[] = '';
            };

            $table_row[] = '<span class="bolder" style="font-size: '.$font_size.';">'.sla_truncate($row['SLA'], $config['graph_precision']).'%</span>';

            $table_row2 = [];
            if (isset($row['failover']) === true) {
                switch ($row['failover']) {
                    case 'primary_compare':
                        $table_row2[] = __('Primary').' (24x7)';
                    break;

                    case 'failover_compare':
                        $table_row2[] = __('Failover').' (24x7)';
                    break;

                    case 'result_compare':
                        $table_row2[] = __('Result').' (24x7)';
                    break;

                    default:
                        if (strpos($row['failover'], 'failover') !== false) {
                            $table_row2[] = __('Failover');
                        } else {
                            $table_row2[] = ucfirst($row['failover']);
                        }
                    break;
                }
            }

            if (isset($row['failover']) === true
                && ($row['failover'] === 'result'
                || $row['failover'] === 'result_compare')
            ) {
                $table_row2[] = '--';
                $table_row2[] = '--';
            } else {
                $table_row2[] = $row['agent'];
                $item_name = $row['availability_item'];
                if ((bool) $row['compare'] === true) {
                    $item_name .= ' ('.__('24 x 7').')';
                }

                $table_row2[] = $item_name;
            }

            if ($item['fields']['total_checks']) {
                $table_row2[] = $row['checks_total'];
            } else {
                $table_row2[] = '';
            }

            if ($item['fields']['checks_failed']) {
                $table_row2[] = $row['checks_error'];
            } else {
                $table_row2[] = '';
            }

            if ($item['fields']['checks_in_ok_status']) {
                $table_row2[] = $row['checks_ok'];
            } else {
                $table_row2[] = '';
            }

            if ($item['fields']['checks_in_warning_status']) {
                $table_row2[] = $row['checks_warning'];
            } else {
                $table_row2[] = '';
            }

            if ($item['fields']['unknown_checks']) {
                $table_row2[] = $row['checks_unknown'];
            } else {
                $table_row2[] = '';
            }

            $table1->data[] = $table_row;
            $table2->data[] = $table_row2;
        }
    } else {
        $table = new stdClass();
        $table->colspan['error']['cell'] = 3;
        $table->data['error']['cell'] = __(
            'There are no Agent/Modules defined'
        );
    }

    if ($pdf === 0) {
        $table->colspan[1][0] = 2;
        $table->colspan[2][0] = 2;
        $data = [];
        $data[0] = html_print_table($table1, true);
        array_push($table->data, $data);
    } else {
        // $table1->title = $item['title'];
        // $table1->titleclass = 'title_table_pdf';
        // $table1->titlestyle = 'text-align:left;';
        $return_pdf .= html_print_table($table1, true);
    }

    if ($item['resume']['resume']) {
        if ($pdf === 0) {
            $data2 = [];
            $data2[0] = html_print_table($table2, true);
            array_push($table->data, $data2);
        } else {
            // $table2->title = $item['title'];
            // $table2->titleclass = 'title_table_pdf';
            // $table2->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table($table2, true);
        }
    }

    if ($item['resume']['resume'] && empty($item['data']) === false) {
        $table1->width = '99%';
        $table1->data = [];
        if (empty($same_agent_in_resume) === true
            || (strpos($item['resume']['min_text'], $same_agent_in_resume) === false)
        ) {
            $table1->head = [];
            $table1->head['max_text'] = __('Agent max value');
            $table1->head['max']      = __('Max Value');
            $table1->head['min_text'] = __('Agent min value');
            $table1->head['min']      = __('Min Value');
            $table1->head['avg']      = __('Average Value');

            $table1->headstyle = [];
            $table1->headstyle['min_text'] = 'text-align: left';
            $table1->headstyle['min']        = 'text-align: right';
            $table1->headstyle['max_text'] = 'text-align: left';
            $table1->headstyle['max']      = 'text-align: right';
            $table1->headstyle['avg']      = 'text-align: right';

            $table1->style = [];
            $table1->style['min_text'] = 'text-align: left';
            $table1->style['min']      = 'text-align: right';
            $table1->style['max_text'] = 'text-align: left';
            $table1->style['max']      = 'text-align: right';
            $table1->style['avg']      = 'text-align: right';

            $table1->data[] = [
                'max_text' => $item['resume']['max_text'],
                'max'      => sla_truncate(
                    $item['resume']['max'],
                    $config['graph_precision']
                ).'%',
                'min_text' => $item['resume']['min_text'],
                'min'      => sla_truncate(
                    $item['resume']['min'],
                    $config['graph_precision']
                ).'%',
                'avg'      => '<span class="bolder" style="font-size: '.$font_size.';">'.sla_truncate($item['resume']['avg'], $config['graph_precision']).'%</span>',
            ];
            if ($item['fields']['agent_max_value'] == false) {
                $table1->head['max_text'] = '';
                $table1->data[0]['max_text'] = '';
                $table1->head['max'] = '';
                $table1->data[0]['max'] = '';
            }

            if ($item['fields']['agent_min_value'] == false) {
                $table1->head['min_text'] = '';
                $table1->data[0]['min_text'] = '';
                $table1->head['min'] = '';
                $table1->data[0]['min'] = '';
            }

            if ($pdf === 0) {
                $table->colspan[3][0] = 3;
                $data = [];
                $data[0] = html_print_table(
                    $table1,
                    true
                );
                array_push($table->data, $data);
            } else {
                // $table1->title = $item['title'];
                // $table1->titleclass = 'title_table_pdf';
                // $table1->titlestyle = 'text-align:left;';
                $return_pdf .= html_print_table(
                    $table1,
                    true
                );
            }
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * The availability report shows in detail the reached
 * status of a module in a given time interval.
 *
 * @param string  $table Reference table in pdf a false.
 * @param array   $item  Parameters for item pdf.
 * @param boolean $pdf   Send pdf.
 *
 * @return string HTML code.
 */
function reporting_html_availability_graph($table, $item, $pdf=0)
{
    global $config;
    $metaconsole_on = is_metaconsole();

    $font_size = $config['font_size_item_report'].'em';

    if ($pdf) {
        $font_mini = '9px';
    } else {
        $font_mini = 'inherit';
    }

    if ($metaconsole_on !== false) {
        $hack_metaconsole = '../../';
    } else {
        $hack_metaconsole = '';
    }

    $src = ui_get_full_url(false);

    $tables_chart = '';

    $total_values = 0;

    $count_total_charts = 0;

    if (isset($item['failed']) === true && empty($item['failed']) === false) {
        $tables_chart .= $item['failed'];
    } else {
        foreach ($item['charts'] as $k_chart => $chart) {
            $checks_resume = '';
            $sla_value = '';
            if (reporting_sla_is_not_init_from_array($chart)) {
                $color = COL_NOTINIT;
                $sla_value = __('Not init');
            } else if (reporting_sla_is_ignored_from_array($chart)) {
                $color = COL_IGNORED;
                $sla_value = __('No data');
            } else {
                switch ($chart['sla_status']) {
                    case REPORT_STATUS_ERR:
                        $color = COL_CRITICAL;
                    break;

                    case REPORT_STATUS_OK:
                        $color = COL_NORMAL;
                    break;

                    default:
                        $color = COL_UNKNOWN;
                    break;
                }

                $sla_value = sla_truncate(
                    $chart['sla_value'],
                    $config['graph_precision']
                ).'%';
                $checks_resume = '('.$chart['checks_ok'].'/'.$chart['checks_total'].')';
            }

            // Check failover availability report.
            if (empty($item['data'][$k_chart]['failover']) === true) {
                if ($item['data'][$k_chart]['compare'] === 0
                    || $item['data'][$k_chart]['compare'] === 1
                ) {
                    $table1 = new stdClass();
                    $table1->width = '100%';
                    $table1->class = 'info_table';
                    $table1->autosize = 1;
                    $table1->styleTable = 'overflow: wrap; table-layout: fixed;';
                    $table1->data = [];
                    $table1->size = [];
                    $table1->size[0] = '10%';
                    $table1->size[1] = '80%';
                    $table1->size[2] = '10%';
                }

                $table1->style[0] = 'overflow-wrap: break-word';

                // Align percentage and checks resume.
                $table1->align[2] = 'left';
                $table1->data[$k_chart][0] = $chart['agent'];
                $table1->data[$k_chart][0] .= '<br />';
                $table1->data[$k_chart][0] .= $chart['module'];
                if ($item['data'][$k_chart]['compare'] === 1) {
                    $table1->data[$k_chart][0] .= ' (24 x 7)';
                }

                $total_values .= $sla_value;
                $count_total_charts++;
                $table1->data[$k_chart][1] = $chart['chart'];
                $table1->data[$k_chart][2] = "<span style = 'font-weight: bold; font-size: ".$font_size.'; color: '.$color."'>".$sla_value.'</span><br/>';

                // Pdf sizes to avoid excesive overflow.
                if ($pdf !== 0) {
                    $table1->size[0] = '15%';
                    $table1->size[1] = '70%';
                    $table1->size[2] = '15%';
                }

                $table1->data[$k_chart][2] .= "<span style = 'font-size: ".$font_mini.";'>".$checks_resume.'</span>';

                if ($item['data'][$k_chart]['compare'] !== 1) {
                    $tables_chart .= html_print_table(
                        $table1,
                        true
                    );
                }
            } else {
                if (($item['data'][$k_chart]['failover'] === 'primary'
                    || $item['data'][$k_chart]['failover'] === 'primary_compare'
                    || $item['failover_type'] == REPORT_FAILOVER_TYPE_SIMPLE)
                    && ($item['data'][$k_chart]['compare'] === 0
                    || $item['data'][$k_chart]['compare'] === 1)
                ) {
                    $table1 = new stdClass();
                    $table1->width = '100%';
                    $table1->data = [];
                    $table1->size = [];
                    $table1->size[0] = '10%';
                    $table1->size[1] = '80%';
                    $table1->size[2] = '5%';
                    $table1->size[3] = '5%';
                }

                $title = '';
                $checks_resume_text = '<span style = "font-size: '.$font_mini.';">';
                $checks_resume_text .= $checks_resume;
                $checks_resume_text .= '</span>';
                $sla_value_text = "<span style = 'font-weight: bold; font-size: ".$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.$color."'>".$sla_value.'</span>';
                switch ($item['data'][$k_chart]['failover']) {
                    case 'primary_compare':
                        $title = '<b>'.__('Primary').' (24x7)</b>';
                        $title .= '<br />'.$chart['agent'];
                        $title .= '<br />'.$chart['module'];
                    break;

                    case 'primary':
                        $title = '<b>'.__('Primary').'</b>';
                        $title .= '<br />'.$chart['agent'];
                        $title .= '<br />'.$chart['module'];
                    break;

                    case 'failover_compare':
                        $title = '<b>'.__('Failover').' (24x7)</b>';
                        $title .= '<br />'.$chart['agent'];
                        $title .= '<br />'.$chart['module'];
                    break;

                    case (preg_match('/failover.*/', $item['data'][$k_chart]['failover']) ? true : false):
                        $title = '<b>'.__('Failover').'</b>';
                        $title .= '<br />'.$chart['agent'];
                        $title .= '<br />'.$chart['module'];
                    break;

                    case 'result_compare':
                        $title = '<b>'.__('Result').' (24x7)</b>';
                        $sla_value_text = "<span style = 'font-weight: bold; font-size: ".$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.$color."'>".$sla_value.'</span>';
                        $checks_resume_text = '<span style = "font-size: '.$font_mini.';">';
                        $checks_resume_text .= $checks_resume;
                        $checks_resume_text .= '</span>';
                    break;

                    case 'result':
                    default:
                        $total_values .= $sla_value;
                        $count_total_charts++;
                        $title = '<b>'.__('Result').'</b>';
                        $sla_value_text = "<span style = 'font-weight: bold; font-size: ".$font_size.(($pdf === 0) ? ' !important' : '').'; color: '.$color."'>".$sla_value.'</span>';
                        $checks_resume_text = '<span style = "font-size: '.$font_mini.';">';
                        $checks_resume_text .= $checks_resume;
                        $checks_resume_text .= '</span>';
                    break;
                }

                $table1->data[$item['data'][$k_chart]['failover']][0] = $title;
                $table1->data[$item['data'][$k_chart]['failover']][1] = $chart['chart'];
                $table1->data[$item['data'][$k_chart]['failover']][2] = $sla_value_text;
                $table1->data[$item['data'][$k_chart]['failover']][3] = $checks_resume_text;

                if ($item['data'][$k_chart]['compare'] !== 1
                    && $item['data'][$k_chart]['failover'] === 'result'
                ) {
                    $tables_chart .= html_print_table(
                        $table1,
                        true
                    );
                }
            }
        }
    }

    if ((bool) $item['summary'] === true) {
        $table_summary = new stdClass();
        $table_summary->width = '20%';

        $table_summary->size = [];
        $table_summary->size[0] = '50%';
        $table_summary->size[1] = '50%';

        $table_summary->data = [];
        $table_summary->data[0][0] = '<b>'.__('Summary').'</b>';
        $table_summary->data[0][1] = '<span style = "font-weight: bold; font-size: '.$font_size.(($pdf === 0) ? ' !important' : '').';">';
        $table_summary->data[0][1] .= sla_truncate($total_values / $count_total_charts);
        $table_summary->data[0][1] .= ' %';
        $table_summary->data[0][1] .= '</span>';

        $tables_chart .= html_print_table(
            $table_summary,
            true
        );
    }

    if ($item['type'] == 'availability_graph') {
        // Table_legend_graphs.
        $table2 = new stdClass();
        $table2->width = '99%';
        $table2->data = [];
        $table2->size = [];
        $table2->size[0] = '2%';
        $table2->data[0][0] = '<img src ="'.$src.$hack_metaconsole.'images/square_green.png">';
        $table2->size[1] = '14%';
        $table2->data[0][1] = '<span>'.__('OK').'</span>';

        $table2->size[2] = '2%';
        $table2->data[0][2] = '<img src ="'.$src.$hack_metaconsole.'images/square_red.png">';
        $table2->size[3] = '14%';
        $table2->data[0][3] = '<span>'.__('Critical').'</span>';

        $table2->size[4] = '2%';
        $table2->data[0][4] = '<img src ="'.$src.$hack_metaconsole.'images/square_gray.png">';
        $table2->size[5] = '14%';
        $table2->data[0][5] = '<span>'.__('Unknow').'</span>';

        $table2->size[6] = '2%';
        $table2->data[0][6] = '<img src ="'.$src.$hack_metaconsole.'images/square_blue.png">';
        $table2->size[7] = '14%';
        $table2->data[0][7] = '<span>'.__('Not Init').'</span>';

        $table2->size[8] = '2%';
        $table2->data[0][8] = '<img src ="'.$src.$hack_metaconsole.'images/square_violet.png">';
        $table2->size[9] = '14%';
        $table2->data[0][9] = '<span>'.__('Downtimes').'</span>';

        $table2->size[10] = '2%';
        $table2->data[0][10] = '<img src ="'.$src.$hack_metaconsole.'images/square_light_gray.png">';
        $table2->size[11] = '15%';
        $table2->data[0][11] = '<span>'.__('Scheduled Downtime').'</span>';
    }

    if ($pdf !== 0) {
        $tables_chart .= html_print_table(
            $table2,
            true
        );
        return $tables_chart;
    } else {
        $table->colspan['charts']['cell'] = 2;
        $table->data['charts']['cell'] = $tables_chart;
        $table->colspan['legend']['cell'] = 2;
        $table->data['legend']['cell'] = html_print_table(
            $table2,
            true
        );
    }
}


/**
 * Function for first time data agent.
 *
 * @param string $agent_name Agent name.
 *
 * @return array
 */
function get_agent_first_time($agent_name)
{
    $id = agents_get_agent_id($agent_name, true);

    $utimestamp = db_get_all_rows_sql(
        'SELECT min(utimestamp) FROM tagente_datos WHERE id_agente_modulo IN 
        (SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = '.$id.')'
    );
    $utimestamp = $utimestamp[0]['utimestamp'];

    return $utimestamp;
}


/**
 * Function to print to HTML General report.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_general($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (!empty($item['data'])) {
        $data_in_same_row = $item['show_in_same_row'];
        switch ($item['subtype']) {
            default:
            case REPORT_GENERAL_NOT_GROUP_BY_AGENT:
                if (!$data_in_same_row) {
                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->class = 'info_table';
                    $table1->data = [];
                    $table1->head = [];
                    $table1->head[0] = __('Agent');
                    $table1->head[1] = __('Module');
                    if ($item['date']['period'] != 0) {
                        $table1->head[2] = __('Operation');
                    }

                    $table1->head[3] = __('Value');
                    $table1->style[0] = 'text-align: center';
                    $table1->style[1] = 'text-align: center';
                    $table1->style[2] = 'text-align: center';
                    $table1->style[3] = 'text-align: center';

                    foreach ($item['data'] as $row) {
                        if ($row['id_module_type'] == 6 || $row['id_module_type'] == 9 || $row['id_module_type'] == 18 || $row['id_module_type'] == 2) {
                            $row['formated_value'] = round($row['formated_value'], 0, PHP_ROUND_HALF_DOWN);
                        }

                        if (($row['id_module_type'] == 6 || $row['id_module_type'] == 9 || $row['id_module_type'] == 18 || $row['id_module_type'] == 2) && $row['formated_value'] == 1) {
                            $row['formated_value'] = 'Up';
                        } else if (($row['id_module_type'] == 6 || $row['id_module_type'] == 9 || $row['id_module_type'] == 18 || $row['id_module_type'] == 2) && $row['formated_value'] == 0) {
                            $row['formated_value'] = 'Down';
                        }

                        if ($item['date']['period'] != 0) {
                            $table1->data[] = [
                                $row['agent'],
                                $row['module'],
                                $row['operator'],
                                $row['formated_value'],
                            ];
                        } else {
                            $table1->data[] = [
                                $row['agent'],
                                $row['module'],
                                $row['formated_value'],
                            ];
                        }
                    }
                } else {
                    $order_data = [];
                    foreach ($item['data'] as $row) {
                        $order_data[$row['id_agent']][$row['id_agent_module']][$row['operator']] = $row['formated_value'];
                    }

                    $table1 = new stdClass();
                    $table1->width = '99%';
                    $table1->data = [];
                    $table1->head = [];
                    $table1->head[0] = __('Agent');
                    $table1->head[1] = __('Module');
                    $table1->head[2] = __('Avg');
                    $table1->head[3] = __('Max');
                    $table1->head[4] = __('Min');
                    $table1->head[5] = __('Sum');
                    $table1->style[0] = 'text-align: center';
                    $table1->style[1] = 'text-align: center';
                    $table1->style[2] = 'text-align: center';
                    $table1->style[3] = 'text-align: center';
                    $table1->style[4] = 'text-align: center';
                    $table1->style[4] = 'text-align: center';

                    foreach ($order_data as $id_agent => $row) {
                        foreach ($row as $id_module => $row2) {
                            $table1->data[] = [
                                agents_get_alias($id_agent),
                                modules_get_agentmodule_name($id_module),
                                $row2['all'][0],
                                $row2['all'][1],
                                $row2['all'][2],
                                $row2['all'][3],
                            ];
                        }
                    }
                }
            break;
            case REPORT_GENERAL_GROUP_BY_AGENT:
                $list_modules = [];
                foreach ($item['data'] as $modules) {
                    foreach ($modules as $name => $value) {
                        $list_modules[$name] = null;
                    }
                }

                $list_modules = array_keys($list_modules);
                $table1 = new stdClass();
                $table1->width = '99%';
                $table1->data = [];
                $table1->head = array_merge([__('Agent')], $list_modules);
                foreach ($item['data'] as $agent => $modules) {
                    $row = [];
                    $alias = agents_get_alias_by_name($agent);
                    $row['agent'] = $alias;
                    $table1->style['agent'] = 'text-align: center;';
                    foreach ($list_modules as $name) {
                        $table1->style[$name] = 'text-align: center;';
                        if (isset($modules[$name])) {
                            $row[$name] = $modules[$name];
                        } else {
                            $row[$name] = '--';
                        }
                    }

                    $table1->data[] = $row;
                }
            break;
        }

        if ($pdf !== 0) {
            $table1->title = $item['title'];
            $table1->titleclass = 'title_table_pdf';
            $table1->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table($table1, true);
        } else {
            $table->colspan['data']['cell'] = 3;
            $table->cellstyle['data']['cell'] = 'text-align: center;';
            $table->data['data']['cell'] = html_print_table($table1, true);
        }
    } else {
        if ($pdf !== 0) {
            $return_pdf .= __('There are no Agent/Modules defined');
        } else {
            $table->colspan['error']['cell'] = 3;
            $table->data['error']['cell'] = __('There are no Agent/Modules defined');
        }
    }

    if ($item['resume'] && !empty($item['data'])) {
        $table_summary = new stdClass();
        $table_summary->width = '99%';

        $table_summary->data = [];
        $table_summary->head = [];
        $table_summary->head_colspan = [];
        $table_summary->align = [];
        $table_summary->headstyle = [];
        $table_summary->headstyle[0] = 'text-align: center;';
        $table_summary->headstyle[1] = 'text-align: center;';
        $table_summary->headstyle[2] = 'text-align: center;';

        $table_summary->align[0] = 'center';
        $table_summary->align[1] = 'center';
        $table_summary->align[2] = 'center';
        $table_summary->align[3] = 'center';
        $table_summary->align[4] = 'center';

        $table_summary->head_colspan[0] = 2;
        $table_summary->head[0] = __('Min Value');
        $table_summary->head[1] = __('Average Value');
        $table_summary->head_colspan[2] = 2;
        $table_summary->head[2] = __('Max Value');

        $table_summary->data[0][0] = $item['min']['agent'].' - '.$item['min']['module'].str_repeat('&nbsp;', 20).$item['min']['formated_value'];
        $table_summary->data[0][1] = '';
        $table_summary->data[0][2] = $item['avg_value'];
        $table_summary->data[0][3] = $item['max']['agent'].' - '.$item['max']['module'].str_repeat('&nbsp;', 20).$item['max']['formated_value'];
        $table_summary->data[0][4] = '';

        if ($pdf !== 0) {
            $return_pdf .= html_print_table($table_summary, true);
        } else {
            $table->colspan['summary_title']['cell'] = 3;
            $table->data['summary_title']['cell'] = '<b>'.__('Summary').'</b>';
            $table->colspan['summary_table']['cell'] = 3;
            $table->data['summary_table']['cell'] = html_print_table(
                $table_summary,
                true
            );
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Function to print to HTML query sql.
 *
 * @param object  $table Head table or false if it comes from pdf.
 * @param array   $item  Items data.
 * @param boolean $pdf   If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_html_sql($table, $item, $pdf=0)
{
    $return_pdf = '';
    if (!$item['correct']) {
        if ($pdf === 0) {
            $table->colspan['error']['cell'] = 3;
            $table->data['error']['cell'] = $item['error'];
        } else {
            $return_pdf .= $item['error'];
        }
    } else {
        $first = true;

        $table2 = new stdClass();
        $table2->class = 'info_table';
        $table2->width = '100%';

        foreach ($item['data'] as $row) {
            if ($first === true) {
                $first = false;

                // Print the header.
                foreach ($row as $key => $value) {
                    $table2->head[] = $key;
                }
            }

            $table2->data[] = $row;
        }

        if ($pdf === 0) {
            $table->colspan['data']['cell'] = 3;
            $table->cellstyle['data']['cell'] = 'text-align: center;';
            $table->data['data']['cell'] = html_print_table(
                $table2,
                true
            );
        } else {
            $table2->title = $item['title'];
            $table2->titleclass = 'title_table_pdf';
            $table2->titlestyle = 'text-align:left;';
            $return_pdf .= html_print_table(
                $table2,
                true
            );
        }
    }

    if ($pdf !== 0) {
        return $return_pdf;
    }
}


/**
 * Function for stats.
 *
 * @param array   $data         Array item.
 * @param integer $graph_width  Items data.
 * @param integer $graph_height If it comes from pdf.
 *
 * @return string HTML code.
 */
function reporting_get_stats_summary($data, $graph_width, $graph_height)
{
    global $config;

    // Alerts table.
    $table_sum = html_get_predefined_table();

    $tdata = [];
    $table_sum->colspan[count($table_sum->data)][0] = 2;
    $table_sum->colspan[count($table_sum->data)][2] = 2;
    $table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
    $table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';
    $tdata[0] = '<span class="med_data color_666">'.__('Module status').'</span>';
    $tdata[2] = '<span class="med_data color_666">'.__('Alert level').'</span>';
    $table_sum->rowclass[] = '';
    $table_sum->data[] = $tdata;

    $tdata = [];
    $table_sum->colspan[count($table_sum->data)][0] = 2;
    $table_sum->colspan[count($table_sum->data)][2] = 2;
    $table_sum->cellstyle[count($table_sum->data)][0] = 'text-align: center;';
    $table_sum->cellstyle[count($table_sum->data)][2] = 'text-align: center;';

    if ($data['monitor_checks'] > 0) {
        // Fixed width non interactive charts.
        $status_chart_width = $graph_width;

        $tdata[0] = '<div style="margin: auto; width: '.$graph_width.'px;">';
        $tdata[0] .= '<div id="status_pie" style="margin: auto; width: '.$graph_width.'">';
        $tdata[0] .= graph_agent_status(
            false,
            $graph_width,
            $graph_height,
            true,
            true
        );
        $tdata[0] .= '</div>';
        $tdata[0] .= '</div>';
    } else {
        $tdata[2] = html_print_image(
            'images/image_problem_area_small.png',
            true,
            ['width' => $graph_width]
        );
    }

    if ($data['monitor_alerts'] > 0) {
        $tdata[2] = '<div style="margin: auto; width: '.$graph_width.'px;">';
        $tdata[2] .= graph_alert_status(
            $data['monitor_alerts'],
            $data['monitor_alerts_fired'],
            $graph_width,
            $graph_height,
            true,
            true
        );
        $tdata[2] .= '</div>';
    } else {
        $tdata[2] = html_print_image(
            'images/image_problem_area_small.png',
            true,
            ['width' => $graph_width]
        );
    }

    $table_sum->rowclass[] = '';
    $table_sum->data[] = $tdata;

    $output = '<fieldset class="databox tactical_set">
                <legend>'.__('Summary').'</legend>'.html_print_table($table_sum, true).'</fieldset>';

    return $output;
}


/**
 * Get an event reporting table.
 *
 * It construct a table object with all the events happened in a group
 * during a period of time.
 *
 * @param integer $id_group Group id to get the report.
 * @param integer $period   Period of time to get the report.
 * @param integer $date     Beginning date of the report.
 * @param boolean $return   Flag to return or echo the
 *   report table (echo by default).
 *
 * @return object A table object
 */
function reporting_event_reporting($id_group, $period, $date=0, $return=false)
{
    if (empty($date)) {
        $date = get_system_time();
    } else if (!is_numeric($date)) {
        $date = strtotime($date);
    }

    $table->data = [];
    $table->head = [];
    $table->head[0] = __('Status');
    $table->head[1] = __('Event name');
    $table->head[2] = __('User ID');
    $table->head[3] = __('Timestamp');

    $events = events_get_group_events($id_group, $period, $date);
    if (empty($events)) {
        $events = [];
    }

    foreach ($events as $event) {
        $data = [];
        if ($event['estado'] == 0) {
            $data[0] = html_print_image('images/dot_red.png', true);
        } else {
            $data[0] = html_print_image('images/dot_green.png', true);
        }

        $data[1] = $event['evento'];
        $data[2] = ($event['id_usuario'] != '0') ? $event['id_usuario'] : '';
        $data[3] = $event['timestamp'];
        array_push($table->data, $data);
    }

    if (empty($return)) {
        html_print_table($table);
    }

    return $table;
}


/**
 * Get a table report from a alerts fired array.
 *
 * @param array $alerts_fired Alerts fired array.
 *
 * @see function get_alerts_fired ()
 *
 * @return object A table object with a report of the fired alerts.
 */
function reporting_get_fired_alerts_table($alerts_fired)
{
    $agents = [];
    global $config;

    include_once $config['homedir'].'/include/functions_alerts.php';

    foreach (array_keys($alerts_fired) as $id_alert) {
        $alert_module = alerts_get_alert_agent_module($id_alert);
        $template = alerts_get_alert_template($id_alert);

        // Add alerts fired to $agents_fired_alerts indexed by id_agent.
        $id_agent = db_get_value(
            'id_agente',
            'tagente_modulo',
            'id_agente_modulo',
            $alert_module['id_agent_module']
        );
        if (!isset($agents[$id_agent])) {
            $agents[$id_agent] = [];
        }

        array_push($agents[$id_agent], [$alert_module, $template]);
    }

    $table->data = [];
    $table->head = [];
    $table->head[0] = __('Agent');
    $table->head[1] = __('Alert description');
    $table->head[2] = __('Times fired');
    $table->head[3] = __('Priority');

    foreach ($agents as $id_agent => $alerts) {
        $data = [];
        foreach ($alerts as $tuple) {
            $alert_module = $tuple[0];
            $template = $tuple[1];
            if (! isset($data[0])) {
                $data[0] = agents_get_alias($id_agent);
            } else {
                $data[0] = '';
            }

            $data[1] = $template['name'];
            $data[2] = $alerts_fired[$alert_module['id']];
            $data[3] = get_alert_priority($alert_module['priority']);
            array_push($table->data, $data);
        }
    }

    return $table;
}


/**
 * Get a report table with all the monitors down.
 *
 * @param array $monitors_down An array with all the monitors down.
 *
 * @see Function modules_get_monitors_down().
 *
 * @return object A table object with a monitors down report.
 */
function reporting_get_monitors_down_table($monitors_down)
{
    $table->data = [];
    $table->head = [];
    $table->head[0] = __('Agent');
    $table->head[1] = __('Monitor');

    $agents = [];
    if ($monitors_down) {
        foreach ($monitors_down as $monitor) {
            // Add monitors fired to $agents_fired_alerts indexed by id_agent.
            $id_agent = $monitor['id_agente'];
            if (!isset($agents[$id_agent])) {
                $agents[$id_agent] = [];
            }

            array_push($agents[$id_agent], $monitor);

            $monitors_down++;
        }

        foreach ($agents as $id_agent => $monitors) {
            $data = [];
            foreach ($monitors as $monitor) {
                if (! isset($data[0])) {
                    $data[0] = agents_get_alias($id_agent);
                } else {
                    $data[0] = '';
                }

                if ($monitor['descripcion'] != '') {
                    $data[1] = $monitor['descripcion'];
                } else {
                    $data[1] = $monitor['nombre'];
                }

                array_push($table->data, $data);
            }
        }
    }

    return $table;
}


/**
 * Get a general report of a group of agents.
 *
 * It shows the number of agents and no more things right now.
 *
 * @param integer $id_group Group to get the report.
 * @param boolean $return   Flag to return or echo the report (by default).
 *
 * @return string HTML code. string with group report
 */
function reporting_print_group_reporting($id_group, $return=false)
{
    $agents = agents_get_group_agents($id_group, false, 'none');
    $output = '<strong>'.sprintf(__('Agents in group: %s'), count($agents)).'</strong><br />';

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Get a report table of the fired alerts group by agents.
 *
 * @param integer $id_agent Agent id to generate the report.
 * @param integer $period   Period of time of the report.
 * @param integer $date     Beginning date of the report in
 * UNIX time (current date by default).
 *
 * @return object A table object with the alert reporting..
 */
function reporting_get_agent_alerts_table($id_agent, $period=0, $date=0)
{
    global $config;
    $table->data = [];
    $table->head = [];
    $table->head[0] = __('Type');
    $table->head[1] = __('Description');
    $table->head[2] = __('Value');
    $table->head[3] = __('Threshold');
    $table->head[4] = __('Last fired');
    $table->head[5] = __('Times fired');

    include_once $config['homedir'].'/include/functions_alerts.php';

    $alerts = agents_get_alerts($id_agent);

    foreach ($alerts['simple'] as $alert) {
        $fires = get_alert_fires_in_period($alert['id'], $period, $date);
        if (! $fires) {
            continue;
        }

        $template = alerts_get_alert_template($alert['id_alert_template']);
        $data = [];
        $data[0] = alerts_get_alert_templates_type_name($template['type']);
        $data[1] = $template['name'];

        switch ($template['type']) {
            case 'regex':
            default:
                if ($template['matches_value']) {
                    $data[2] = '&#8771; "'.$template['value'].'"';
                } else {
                    $data[2] = '&#8772; "'.$template['value'].'"';
                }
            break;

            case 'equal':
            case 'not_equal':
                $data[2] = $template['value'];
            break;

            case 'max-min':
                $data[2] = __('Min.').': '.$template['min_value'].' ';
                $data[2] .= __('Max.').': '.$template['max_value'].' ';
            break;

            case 'max':
                $data[2] = $template['max_value'];
            break;

            case 'min':
                $data[2] = $template['min_value'];
            break;
        }

        $data[3] = $template['time_threshold'];
        $data[4] = ui_print_timestamp(get_alert_last_fire_timestamp_in_period($alert['id'], $period, $date), true);
        $data[5] = $fires;

        array_push($table->data, $data);
    }

    return $table;
}


/**
 * Get a report of monitors in an agent.
 *
 * @param integer $id_agent Agent id to get the report.
 * @param integer $period   Period of time of the report.
 * @param integer $date     Beginning date of the report in UNIX time
 *         (current date by default).
 *
 * @return object A table object with the report.
 */
function reporting_get_agent_monitors_table($id_agent, $period=0, $date=0)
{
    $n_a_string = __('N/A').'(*)';
    $table->head = [];
    $table->head[0] = __('Monitor');
    $table->head[1] = __('Last failure');
    $table->data = [];
    $monitors = modules_get_monitors_in_agent($id_agent);

    if ($monitors === false) {
        return $table;
    }

    foreach ($monitors as $monitor) {
        $downs = modules_get_monitor_downs_in_period(
            $monitor['id_agente_modulo'],
            $period,
            $date
        );
        if (! $downs) {
            continue;
        }

        $data = [];
        if ($monitor['descripcion'] != $n_a_string && $monitor['descripcion'] != '') {
            $data[0] = $monitor['descripcion'];
        } else {
            $data[0] = $monitor['nombre'];
        }

        $data[1] = modules_get_last_down_timestamp_in_period(
            $monitor['id_agente_modulo'],
            $period,
            $date
        );
        array_push($table->data, $data);
    }

    return $table;
}


/**
 * Get a report of all the modules in an agent.
 *
 * @param integer $id_agent Agent id to get the report.
 * @param integer $period   Period of time of the report.
 * @param integer $date     Beginning date of the report in UNIX time
 *         (current date by default).
 *
 * @return object
 */
function reporting_get_agent_modules_table($id_agent, $period=0, $date=0)
{
    $table->data = [];
    $n_a_string = __('N/A').'(*)';
    $modules = agents_get_modules($id_agent, ['nombre', 'descripcion']);
    if ($modules === false) {
        $modules = [];
    }

    $data = [];

    foreach ($modules as $module) {
        if ($module['descripcion'] != $n_a_string && $module['descripcion'] != '') {
            $data[0] = $module['descripcion'];
        } else {
            $data[0] = $module['nombre'];
        }

        array_push($table->data, $data);
    }

    return $table;
}


/**
 * Get a detailed report of an agent
 *
 * @param integer $id_agent Agent to get the report.
 * @param integer $period   Period of time of the desired report.
 * @param integer $date     Beginning date of the report in UNIX time
 * (current date by default).
 * @param boolean $return   Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_get_agent_detailed(
    $id_agent,
    $period=0,
    $date=0,
    $return=false
) {
    $output = '';
    $n_a_string = __('N/A(*)');

    // Show modules in agent.
    $output .= '<div class="agent_reporting">';
    $output .= '<h3 class="underline">'.__('Agent').' - '.agents_get_alias($id_agent).'</h3>';
    $output .= '<h4>'.__('Modules').'</h3>';
    $table_modules = reporting_get_agent_modules_table($id_agent, $period, $date);
    $table_modules->width = '99%';
    $output .= html_print_table($table_modules, true);

    // Show alerts in agent.
    $table_alerts = reporting_get_agent_alerts_table($id_agent, $period, $date);
    $table_alerts->width = '99%';
    if (count($table_alerts->data)) {
        $output .= '<h4>'.__('Alerts').'</h4>';
        $output .= html_print_table($table_alerts, true);
    }

    // Show monitor status in agent (if any).
    $table_monitors = reporting_get_agent_monitors_table($id_agent, $period, $date);
    if (count($table_monitors->data) == 0) {
        $output .= '</div>';
        if (! $return) {
            echo $output;
        }

        return $output;
    }

    $table_monitors->width = '99%';
    $table_monitors->align = [];
    $table_monitors->align[1] = 'right';
    $table_monitors->size = [];
    $table_monitors->align[1] = '10%';
    $output .= '<h4>'.__('Monitors').'</h4>';
    $output .= html_print_table($table_monitors, true);

    $output .= '</div>';

    if (! $return) {
        echo $output;
    }

    return $output;
}


/**
 * Get a detailed report of agents in a group.
 *
 * @param mixed Group(s) to get the report
 * @param int Period
 * @param int Timestamp to start from
 * @param bool Flag to return or echo the report (by default).
 *
 * @return string
 */
function reporting_agents_get_group_agents_detailed($id_group, $period=0, $date=0, $return=false)
{
    $agents = agents_get_group_agents($id_group, false, 'none');

    $output = '';
    foreach ($agents as $agent_id => $agent_name) {
        $output .= reporting_get_agent_detailed($agent_id, $period, $date, true);
    }

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 *  This is the callback sorting function for SLA values descending
 *
 * @param array $a Array element 1 to compare
 * @param array $b Array element 2 to compare
 */
function sla_value_desc_cmp($a, $b)
{
    // This makes 'Unknown' values the lastest
    if (preg_match('/^(.)*Unknown(.)*$/', $a[5])) {
        $a[6] = -1;
    }

    if (preg_match('/^(.)*Unknown(.)*$/', $b[5])) {
        $b[6] = -1;
    }

    return ($a[6] < $b[6]) ? 1 : 0;
}


/**
 *  This is the callback sorting function for SLA values ascending
 *
 * @param array $a Array element 1 to compare
 * @param array $b Array element 2 to compare
 */
function sla_value_asc_cmp($a, $b)
{
    // This makes 'Unknown' values the lastest
    if (preg_match('/^(.)*Unknown(.)*$/', $a[5])) {
        $a[6] = -1;
    }

    if (preg_match('/^(.)*Unknown(.)*$/', $b[5])) {
        $b[6] = -1;
    }

    return ($a[6] > $b[6]) ? 1 : 0;
}


/**
 * Make the header for each content.
 */
function reporting_header_content(
    $mini,
    $content,
    $report,
    &$table,
    $title=false,
    $name=false,
    $period=false
) {
    global $config;

    if ($mini) {
        $sizh = '';
        $sizhfin = '';
    } else {
        $sizh = '<h4>';
        $sizhfin = '</h4>';
    }

    $data = [];

    $count_empty = 0;

    if ($title !== false) {
        $data[] = $sizh.$title.$sizhfin;
    } else {
        $count_empty++;
    }

    if ($name !== false) {
        $data[] = $sizh.$name.$sizhfin;
    } else {
        $count_empty++;
    }

    if ($period !== false && $content['period'] > 0) {
        $data[] = $sizh.$period.$sizhfin;
    } else if ($content['period'] == 0) {
        $es = json_decode($content['external_source'], true);
        if ($es['date'] == 0) {
            $date = __('Last data');
        } else {
            $date = date($config['date_format'], $es['date']);
        }

        $data[] = "<div style='text-align: right;'>".$sizh.$date.$sizhfin.'</div>';
    } else {
        $data[] = "<div style='text-align: right;'>".$sizh.'('.human_time_description_raw($content['period']).') '.__('From:').' '.date($config['date_format'], ($report['datetime'] - $content['period'])).'<br />'.__('To:').' '.date($config['date_format'], $report['datetime']).'<br />'.$sizhfin.'</div>';
    }

    $table->colspan[0][(2 - $count_empty)] = (1 + $count_empty);

    array_push($table->data, $data);
}


function reporting_get_agents_by_status($data, $graph_width=250, $graph_height=150, $links=false)
{
    global $config;

    if ($links == false) {
        $links = [];
    }

    $table_agent = html_get_predefined_table();

    $agent_data = [];
    $agent_data[0] = html_print_image('images/agent_critical.png', true, ['title' => __('Agents critical')]);
    $agent_data[1] = "<a style='color: ".COL_CRITICAL.";' href='".$links['agents_critical']."'><b><span class='red_color font_12pt bolder big_data'>".format_numeric($data['agent_critical']).'</span></b></a>';

    $agent_data[2] = html_print_image('images/agent_warning.png', true, ['title' => __('Agents warning')]);
    $agent_data[3] = "<a style='color: ".COL_WARNING.";' href='".$links['agents_warning']."'><b><span class='yellow_color font_12pt bolder big_data'>".format_numeric($data['agent_warning']).'</span></b></a>';

    $table_agent->data[] = $agent_data;

    $agent_data = [];
    $agent_data[0] = html_print_image('images/agent_ok.png', true, ['title' => __('Agents ok')]);
    $agent_data[1] = "<a style='color: ".COL_NORMAL.";' href='".$links['agents_ok']."'><b><span class='pandora_green_text font_12pt bolder big_data'>".format_numeric($data['agent_ok']).'</span></b></a>';

    $agent_data[2] = html_print_image('images/agent_unknown.png', true, ['title' => __('Agents unknown')]);
    $agent_data[3] = "<a style='color: ".COL_UNKNOWN.";' href='".$links['agents_unknown']."'><b><span class='grey_color font_12pt bolder big_data'>".format_numeric($data['agent_unknown']).'</span></b></a>';

    $table_agent->data[] = $agent_data;

    $agent_data = [];
    $agent_data[0] = html_print_image('images/agent_notinit.png', true, ['title' => __('Agents not init')]);
    $agent_data[1] = "<a style='color: ".COL_NOTINIT.";' href='".$links['agents_not_init']."'><b><span class='blue_color font_12pt bolder big_data'>".format_numeric($data['agent_not_init']).'</span></b></a>';

    $agent_data[2] = '';
    $agent_data[3] = '';
    $table_agent->data[] = $agent_data;

    if (!defined('METACONSOLE')) {
        $agents_data = '<fieldset class="databox tactical_set">
                    <legend>'.__('Agents by status').'</legend>'.html_print_table($table_agent, true).'</fieldset>';
    } else {
        $table_agent->style = [];
        $table_agent->class = 'tactical_view';
        $agents_data = '<fieldset class="tactical_set">
                    <legend>'.__('Agents by status').'</legend>'.html_print_table($table_agent, true).'</fieldset>';
    }

    return $agents_data;
}


function reporting_get_total_agents_and_monitors($data, $graph_width=250, $graph_height=150)
{
    global $config;

    $total_agent = ($data['agent_ok'] + $data['agent_warning'] + $data['agent_critical'] + $data['gent_unknown'] + $data['agent_not_init']);
    $total_module = ($data['monitor_ok'] + $data['monitor_warning'] + $data['monitor_critical'] + $data['monitor_unknown'] + $data['monitor_not_init']);

    $table_total = html_get_predefined_table();

    $total_data = [];
    $total_data[0] = html_print_image(
        'images/agent.png',
        true,
        [
            'title' => __('Total agents'),
            'class' => 'invert_filter',
        ]
    );
    $total_data[1] = $total_agent <= 0 ? '-' : $total_agent;
    $total_data[2] = html_print_image(
        'images/module.png',
        true,
        [
            'title' => __('Monitor checks'),
            'class' => 'invert_filter',
        ]
    );
    $total_data[3] = $total_module <= 0 ? '-' : $total_module;
    $table_total->data[] = $total_data;
    $total_agent_module = '<fieldset class="databox tactical_set">
                    <legend>'.__('Total agents and monitors').'</legend>'.html_print_table($table_total, true).'</fieldset>';

    return $total_agent_module;
}


function reporting_get_total_servers($num_servers)
{
    global $config;

    $table_node = html_get_predefined_table();

    $node_data = [];
    $node_data[0] = html_print_image('images/server_export.png', true, ['title' => __('Nodes'), 'class' => 'invert_filter']);
    $node_data[1] = "<b><span style='font-size: 12pt; font-weight: bold; color: black;'>".format_numeric($num_servers).'</span></b>';
    $table_node->data[] = $node_data;

    if (!defined('METACONSOLE')) {
        $node_overview = '<fieldset class="databox tactical_set">
                    <legend>'.__('Node overview').'</legend>'.html_print_table($table_node, true).'</fieldset>';
    } else {
        $table_node->style = [];
        $table_node->class = 'tactical_view';
        $node_overview = '<fieldset class="tactical_set">
                    <legend>'.__('Node overview').'</legend>'.html_print_table($table_node, true).'</fieldset>';
    }

    return $node_overview;
}


function reporting_get_events($data, $links=false)
{
    global $config;
    $table_events = new stdClass();
    $table_events->width = '100%';
    if (defined('METACONSOLE')) {
        $style = ' vertical-align:middle;';
    } else {
        $style = '';
    }

    if (defined('METACONSOLE')) {
        $table_events->style[0] = 'background-color:#e63c52';
        $table_events->data[0][0] = html_print_image('images/module_event_critical.png', true, ['title' => __('Critical events')]);
        $table_events->data[0][0] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder color_white' style='".$style."' href='".$links['critical']."'>".format_numeric($data['critical']).'</a>';
        $table_events->style[1] = 'background-color:#f3b200';
        $table_events->data[0][1] = html_print_image('images/module_event_warning.png', true, ['title' => __('Warning events')]);
        $table_events->data[0][1] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder color_white' style='".$style."' href='".$links['warning']."'>".format_numeric($data['warning']).'</a>';
        $table_events->style[2] = 'background-color:#82b92e';
        $table_events->data[0][2] = html_print_image('images/module_event_ok.png', true, ['title' => __('OK events')]);
        $table_events->data[0][2] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder color_white' style='".$style."' href='".$links['normal']."'>".format_numeric($data['normal']).'</a>';
        $table_events->style[3] = 'background-color:#B2B2B2';
        $table_events->data[0][3] = html_print_image('images/module_event_unknown.png', true, ['title' => __('Unknown events')]);
        $table_events->data[0][3] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder color_white' style='".$style."' href='".$links['unknown']."'>".format_numeric($data['unknown']).'</a>';
    } else {
        $table_events->data[0][0] = html_print_image('images/module_critical.png', true, ['title' => __('Critical events')]);
        $table_events->data[0][0] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder red_color' style='".$style."' href='".$links['critical']."'><b><span class='font_12pt bolder red_color'>".format_numeric($data['critical']).'</span></b></a>';
        $table_events->data[0][1] = html_print_image('images/module_warning.png', true, ['title' => __('Warning events')]);
        $table_events->data[0][1] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder yellow_color' style='".$style."' href='".$links['warning']."'><b><span class='font_12pt bolder yellow_color;'>".format_numeric($data['warning']).'</span></b></a>';
        $table_events->data[0][2] = html_print_image('images/module_ok.png', true, ['title' => __('OK events')]);
        $table_events->data[0][2] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder pandora_green_text' style='".$style."' href='".$links['normal']."'><b class='font_12pt bolder pandora_green_text'>".format_numeric($data['normal']).'</b></a>';
        $table_events->data[0][3] = html_print_image('images/module_unknown.png', true, ['title' => __('Unknown events')]);
        $table_events->data[0][3] .= '&nbsp;&nbsp;&nbsp;'."<a class='font_12pt bolder grey_color' style='".$style."' href='".$links['unknown']."'><b><span class='font_12pt bolder grey_color'>".format_numeric($data['unknown']).'</span></b></a>';
    }

    if (!defined('METACONSOLE')) {
        $event_view = '<fieldset class="databox tactical_set">
                    <legend>'.__('Events by severity').'</legend>'.html_print_table($table_events, true).'</fieldset>';
    } else {
        $table_events->class = 'tactical_view';
        $table_events->styleTable = 'text-align:center;';
        $table_events->size[0] = '10%';
        $table_events->size[1] = '10%';
        $table_events->size[2] = '10%';
        $table_events->size[3] = '10%';

        $tooltip = ui_print_help_tip(
            __(
                'Event count corresponds to events within the last hour'
            ),
            true
        );

        $event_view = '<fieldset class="tactical_set"><legend>'.__('Important Events by Criticity').$tooltip.'</legend>'.html_print_table($table_events, true).'</fieldset>';
    }

    return $event_view;
}


function reporting_get_last_activity()
{
    global $config;

    // Show last activity from this user
    $table = new stdClass();
    $table->width = '100%';
    $table->data = [];
    $table->class = 'info_table';
    $table->size = [];
    $table->size[2] = '150px';
    $table->size[3] = '130px';
    $table->size[5] = '200px';
    $table->head = [];
    $table->head[0] = __('User');
    $table->head[1] = '';
    $table->head[2] = __('Action');
    $table->head[3] = __('Date');
    $table->head[4] = __('Source IP');
    $table->head[5] = __('Comments');
    $table->title = '<span>'.__('Last activity in %s console', get_product_name()).'</span>';

    $sql = sprintf(
        'SELECT id_usuario,accion,fecha,ip_origen,descripcion,utimestamp
        FROM tsesion
        WHERE (`utimestamp` > UNIX_TIMESTAMP(NOW()) - '.SECONDS_1WEEK.") 
            AND `id_usuario` = '%s' ORDER BY `utimestamp` DESC LIMIT 5",
        $config['id_user']
    );

    $sessions = db_get_all_rows_sql($sql);

    if ($sessions === false) {
        $sessions = [];
    }

    foreach ($sessions as $session) {
        $data = [];

        $session_id_usuario = $session['id_usuario'];
        $session_ip_origen = $session['ip_origen'];

        $data[0] = '<strong>'.$session_id_usuario.'</strong>';
        $data[1] = ui_print_session_action_icon($session['accion'], true);
        $data[2] = $session['accion'];
        $data[3] = ui_print_help_tip($session['fecha'], true).human_time_comparation($session['utimestamp'], 'tiny');
        $data[4] = $session_ip_origen;
        $data[5] = io_safe_output($session['descripcion']);

        array_push($table->data, $data);
    }

    return html_print_table($table, true);

}


function reporting_get_event_histogram($events, $text_header_event=false)
{
    global $config;
    if (!is_metaconsole()) {
        include_once $config['homedir'].'/include/graphs/functions_gd.php';
    } else {
        include_once '../../include/graphs/functions_gd.php';
    }

    $period = SECONDS_1DAY;

    if (!$text_header_event) {
        $text_header_event = __('Events info (1hr.)');
    }

    $ttl = 1;
    $urlImage = ui_get_full_url(false, true, false, false);

    $colors = [
        EVENT_CRIT_MAINTENANCE   => COL_MAINTENANCE,
        EVENT_CRIT_INFORMATIONAL => COL_INFORMATIONAL,
        EVENT_CRIT_NORMAL        => COL_NORMAL,
        EVENT_CRIT_MINOR         => COL_MINOR,
        EVENT_CRIT_WARNING       => COL_WARNING,
        EVENT_CRIT_MAJOR         => COL_MAJOR,
        EVENT_CRIT_CRITICAL      => COL_CRITICAL,
    ];

    if (is_metaconsole()) {
        $full_legend = [];
        $cont = 0;
    }

    foreach ($events as $data) {
        switch ($data['criticity']) {
            case 0:
                $color = EVENT_CRIT_MAINTENANCE;
            break;

            case 1:
                $color = EVENT_CRIT_INFORMATIONAL;
            break;

            case 2:
                $color = EVENT_CRIT_NORMAL;
            break;

            case 3:
                $color = EVENT_CRIT_WARNING;
            break;

            case 4:
                $color = EVENT_CRIT_CRITICAL;
            break;

            case 5:
                $color = EVENT_CRIT_MINOR;
            break;

            case 6:
                $color = EVENT_CRIT_MAJOR;
            break;

            case 20:
                $color = EVENT_CRIT_NOT_NORMAL;
            break;

            case 34:
                $color = EVENT_CRIT_WARNING_OR_CRITICAL;
            break;
        }

        if (is_metaconsole()) {
            $full_legend[$cont] = $data['timestamp'];
            $graph_data[] = [
                'data'       => $color,
                'utimestamp' => ($data['utimestamp'] - get_system_time()),
            ];
            $cont++;
        } else {
            $graph_data[] = [
                'data'       => $color,
                'utimestamp' => SECONDS_1DAY,
            ];
        }
    }

    $table = new stdClass();
    $table->width = '100%';
    $table->data = [];
    $table->size = [];
    $table->size[0] = '100%';
    $table->head = [];
    $table->title = '<span>'.$text_header_event.'</span>';
    $table->data[0][0] = '';

    if (empty($graph_data) === false) {
        $url_slice = is_metaconsole() ? $url : $urlImage;

        $slicebar = flot_slicesbar_graph(
            $graph_data,
            $period,
            '400px;border:0',
            40,
            $full_legend,
            $colors,
            $config['fontpath'],
            $config['round_corner'],
            $url,
            '',
            '',
            false,
            0,
            [],
            true,
            1,
            450,
            true
        );

        $table->data[0][0] = $slicebar;
    } else {
        $table->data[0][0] = __('No events');
    }

    if (!is_metaconsole()) {
        if (!$text_header_event) {
            $event_graph = '<fieldset class="databox tactical_set">
                        <legend>'.$text_header_event.'</legend>'.html_print_table($table, true).'</fieldset>';
        } else {
            $table->class = 'noclass';
            $event_graph = html_print_table($table, true);
        }
    } else {
        $table->class = 'tactical_view';
        $event_graph = '<fieldset id="event_tactical" class="tactical_set">'.html_print_table($table, true).'</fieldset>';
    }

    return $event_graph;
}


function reporting_get_event_histogram_meta($width, $events)
{
    global $config;
    if (!defined('METACONSOLE')) {
        include_once $config['homedir'].'/include/graphs/functions_gd.php';
    } else {
        include_once '../../include/graphs/functions_gd.php';
    }

    $period = SECONDS_1HOUR;

    if (!$text_header_event) {
        $text_header_event = __('Events info (1hr.)');
    }

    $ttl = 1;
    $urlImage = ui_get_full_url(false, true, false, false);

    $data = [];

    // $resolution = $config['graph_res'] * ($period * 2 / $width); // Number of "slices" we want in graph
    $resolution = (5 * ($period * 2 / $width));
    // Number of "slices" we want in graph
    $interval = (int) ($period / $resolution);
    $date = get_system_time();
    $datelimit = ($date - $period);
    $periodtime = floor($period / $interval);
    $time = [];
    $data = [];
    $legend = [];
    $full_legend = [];
    $full_legend_date = [];

    $colors = [
        EVENT_CRIT_MAINTENANCE   => COL_MAINTENANCE,
        EVENT_CRIT_INFORMATIONAL => COL_INFORMATIONAL,
        EVENT_CRIT_NORMAL        => COL_NORMAL,
        EVENT_CRIT_MINOR         => COL_MINOR,
        EVENT_CRIT_WARNING       => COL_WARNING,
        EVENT_CRIT_MAJOR         => COL_MAJOR,
        EVENT_CRIT_CRITICAL      => COL_CRITICAL,
    ];

    $cont = 0;
    for ($i = 0; $i < $interval; $i++) {
        $bottom = ($datelimit + ($periodtime * $i));
        if (! $graphic_type) {
            $name = date('H:i:s', $bottom);
        } else {
            $name = $bottom;
        }

        // Show less values in legend
        if ($cont == 0 or ($cont % 2)) {
            $legend[$cont] = $name;
        }

        if ($from_agent_view) {
            $full_date = date('Y/m/d', $bottom);
            $full_legend_date[$cont] = $full_date;
        }

        $full_legend[$cont] = $name;

        $top = ($datelimit + ($periodtime * ($i + 1)));

        $events_criticity = [];
        if (is_array($events)) {
            foreach ($events as $value) {
                if ($value['utimestamp'] >= $bottom && $value['utimestamp'] < $top) {
                    array_push($events_criticity, $value['criticity']);
                }
            }
        }

        if (!empty($events)) {
            if (array_search('4', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_CRITICAL;
            } else if (array_search('3', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_WARNING;
            } else if (array_search('6', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_MAJOR;
            } else if (array_search('5', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_MINOR;
            } else if (array_search('20', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_NOT_NORMAL;
            } else if (array_search('34', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_WARNING_OR_CRITICAL;
            } else if (array_search('2', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_NORMAL;
            } else if (array_search('0', $events_criticity) !== false) {
                $data[$cont]['data'] = EVENT_CRIT_MAINTENANCE;
            } else {
                $data[$cont]['data'] = EVENT_CRIT_INFORMATIONAL;
            }

            $data[$cont]['utimestamp'] = $periodtime;
        } else {
            $data[$cont]['utimestamp'] = $periodtime;
            $data[$cont]['data'] = 1;
        }

        $cont++;
    }

    $table = new stdClass();

    $table->width = '100%';

    $table->data = [];
    $table->size = [];
    $table->head = [];
    $table->title = '<span>'.$text_header_event.'</span>';
    $table->data[0][0] = '';

    if (!empty($data)) {
        $slicebar = flot_slicesbar_graph(
            $data,
            $period,
            100,
            30,
            $full_legend,
            $colors,
            $config['fontpath'],
            $config['round_corner'],
            $url,
            '',
            '',
            false,
            0,
            $full_legend_date,
            true,
            1,
            false,
            true
        );

        $table->data[0][0] = $slicebar;
    } else {
        $table->data[0][0] = __('No events');
    }

    if (!$text_header_event) {
        $event_graph = '<fieldset class="databox tactical_set">
                    <legend>'.$text_header_event.'</legend>'.html_print_table($table, true).'</fieldset>';
    } else {
        $table->class = 'noclass';
        $event_graph = html_print_table($table, true);
    }

    return $event_graph;
}


function reporting_html_planned_downtimes_table($planned_downtimes)
{
    global $config;

    if (empty($planned_downtimes)) {
        return false;
    }

    include_once $config['homedir'].'/include/functions_planned_downtimes.php';

    $downtime_malformed = false;
    $malformed_planned_downtimes = planned_downtimes_get_malformed();

    $table = new StdClass();
    $table->width = '99%';
    $table->title = __('This SLA has been affected by the following scheduled downtimes').ui_print_help_tip(
        __('If the duration of the scheduled downtime is less than 5 minutes it will not be represented in the graph'),
        true
    );
    $table->head = [];
    $table->head[0] = __('Name');
    $table->head[1] = __('Description');
    $table->head[2] = __('Execution');
    $table->head[3] = __('Dates');
    $table->headstyle = [];
    $table->style = [];
    $table->data = [];

    foreach ($planned_downtimes as $planned_downtime) {
        $data = [];
        $data[0] = $planned_downtime['name'];
        $data[1] = $planned_downtime['description'];
        $data[2] = $planned_downtime['execution'];
        $data[3] = $planned_downtime['dates'];

        if (!empty($malformed_planned_downtimes) && isset($malformed_planned_downtimes[$planned_downtime['id']])) {
            $next_row_num = count($table->data);
            $table->cellstyle[$next_row_num][0] = 'color: red';
            $table->cellstyle[$next_row_num][1] = 'color: red';
            $table->cellstyle[$next_row_num][2] = 'color: red';
            $table->cellstyle[$next_row_num][3] = 'color: red';

            if (!$downtime_malformed) {
                $downtime_malformed = true;
            }
        }

        $table->data[] = $data;
    }

    $downtimes_table = '';

    if ($downtime_malformed) {
        $info_malformed = ui_print_error_message(
            __('This item is affected by a malformed scheduled downtime').'. '.__('Go to the scheduled downtimes section to solve this').'.',
            '',
            true
        );
        $downtimes_table .= $info_malformed;
    }

    $downtimes_table .= html_print_table($table, true);

    return $downtimes_table;
}


function reporting_html_permissions($table, $item, $pdf=0)
{
    global $config;

    $table1 = new stdClass();
    $table1->width = '100%';

    $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
    $table1->class = 'info_table';
    $table1->cellpadding = 1;
    $table1->cellspacing = 1;
    $table1->styleTable = 'overflow: wrap; table-layout: fixed;';

    if ($item['subtype'] === REPORT_PERMISSIONS_NOT_GROUP_BY_GROUP) {
        $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 100px;';
        $table1->style[1] = 'text-align: left;vertical-align: top;min-width: 100px;';
        $table1->style[2] = 'text-align: left;vertical-align: top; min-width: 100px';

        $table1->head = [
            __('User ID'),
            __('Full name'),
            __('Permissions'),
        ];

        $table1->headstyle[0] = 'text-align: left';
        $table1->headstyle[1] = 'text-align: left';
        $table1->headstyle[2] = 'text-align: left';
    }

    if ($item['subtype'] === REPORT_PERMISSIONS_GROUP_BY_GROUP) {
        $table1->style[0] = 'text-align: left;vertical-align: top;min-width: 150px;';
        $table1->style[1] = 'text-align: left;vertical-align: top;min-width: 150px;';
        $table1->style[2] = 'text-align: left;vertical-align: top;min-width: 150px;';
        $table1->style[3] = 'text-align: left;vertical-align: top;min-width: 150px;';

        $table1->headstyle[0] = 'text-align: left';
        $table1->headstyle[1] = 'text-align: left';
        $table1->headstyle[2] = 'text-align: left';
        $table1->headstyle[3] = 'text-align: left';

        $table1->head = [
            __('Group'),
            __('User ID'),
            __('Full name'),
            __('Permissions'),
        ];
    }

    $table1->data = [];

    foreach ($item['data'] as $data) {
        if ($item['subtype'] === REPORT_PERMISSIONS_NOT_GROUP_BY_GROUP) {
            $profile_group_name = '';
            foreach ($data['user_profiles'] as $user_profile) {
                $profile_group_name .= $user_profile.'<br />';
            }

            $row = [
                $data['user_id'],
                $data['user_name'],
                $profile_group_name,
            ];
        }

        if ($item['subtype'] === REPORT_PERMISSIONS_GROUP_BY_GROUP) {
            $user_profile_id_users = '';
            $user_profile_name = '';
            $user_profile_users_name = '';
            $group_name = $data['group_name'].'<br />';

            foreach ($data['users'] as $user => $user_data) {
                $user_profile_id_users .= $user.'<br />';
                $user_profile_users_name .= $user_data['fullname'].'<br />';

                foreach ($user_data['profiles'] as $profile) {
                    $user_profile_id_users .= '<br />';
                    $user_profile_users_name .= '<br />';
                    $user_profile_name .= $profile.'<br />';
                }

                $user_profile_name .= '<br />';
            }

            $row = [
                $group_name,
                $user_profile_id_users,
                $user_profile_users_name,
                $user_profile_name,
            ];
        }

        $table1->data[] = $row;

        if ($pdf !== 0) {
            $table1->data[] = '<br />';
        }
    }

    if ($pdf === 0) {
        $table->colspan['permissions']['cell'] = 3;
        $table->cellstyle['permissions']['cell'] = 'text-align: center;';
        $table->data['permissions']['cell'] = html_print_table(
            $table1,
            true
        );
    } else {
        return html_print_table(
            $table1,
            true
        );
    }
}


/**
 * HTML content for ncm configuration diff report.
 *
 * @param array $item Content generated by reporting_ncm_config.
 *
 * @return string HTML code.
 */
function reporting_html_ncm_config($table, $item, $pdf=0)
{
    $key = uniqid();
    if ($pdf === 0) {
        ui_require_javascript_file('diff2html-ui.min');
        ui_require_css_file('diff2html.min');
        $script = "$(document).ready(function() {
                const configuration = {
                    drawFileList: false,
                    collapsed: true,
                    matching: 'lines',
                    outputFormat: 'side-by-side',
                };
                const diff2htmlUi = new Diff2HtmlUI(
                    document.getElementById('".$key."'),
                    atob('".base64_encode($item['data'])."'),
                    configuration
                );
                diff2htmlUi.draw();
            });";
        $content = '<div class="w100p" id="'.$key.'"></div class="w100p">';
        $content .= '<script>'.$script.'</script>';
        $table->data[1] = $content;
        $table->colspan[1][0] = 2;
    } else {
        $content = '<div style="text-align:left;margin-left: 14px;">';
        $content .= str_replace("\n", '<br>', $item['data']);
        $content .= '</div>';
        return $content;
    }
}
