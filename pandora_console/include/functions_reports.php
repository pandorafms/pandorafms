<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Reporting
 */

require_once $config['homedir'].'/include/functions_users.php';


function reports_get_type_access($report)
{
    if (empty($report)) {
        return 'group_view';
    }

    if ($report['private']) {
        return 'user_edit';
    } else if ($report['id_group_edit'] != 0) {
        return 'group_edit';
    }

    return 'group_view';
}


/**
 * Get a custom user report.
 *
 * @param int Report id to get.
 * @param array Extra filter.
 * @param array Fields to get.
 *
 * @return Report with the given id. False if not available or readable.
 */
function reports_get_report($id_report, $filter=false, $fields=false)
{
    global $config;

    $id_report = safe_int($id_report);
    if (empty($id_report)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_report'] = $id_report;
    if (!is_user_admin($config['id_user'])) {
        $filter[] = sprintf(
            'private = 0 OR (private = 1 AND id_user = "%s")',
            $config['id_user']
        );
    }

    if (is_array($fields)) {
        $fields[] = 'id_group';
    }

    $report = db_get_row_filter('treport', $filter, $fields);

    if (! check_acl($config['id_user'], $report['id_group'], 'RR')) {
        return false;
    }

    return $report;
}


/**
 * Get a list of the reports the user can view.
 *
 * A user can view a report by two ways:
 *  - The user created the report (id_user field in treport)
 *  - The report is not private and the user has reading privileges on
 *    the group associated to the report
 *
 * @param array Extra filter to retrieve reports. All reports are returned by
 * default
 * @param array Fields to be fetched on every report.
 *
 * @return array An array with all the reports the user can view.
 */
function reports_get_reports(
    $filter=false,
    $fields=false,
    $returnAllGroup=true,
    $privileges='RR',
    $group=false,
    $strict_user=false
) {
    global $config;

    if (! is_array($filter)) {
        $filter = [];
    }

        /*
            if (!is_user_admin ($config["id_user"]))
            $filter[] = sprintf ('private = 0 OR (private = 1 AND id_user = "%s")',
            $config['id_user']);
        */
        $filter['hidden'] = 0;
    if (is_array($fields)) {
        $fields[] = 'id_group';
        $fields[] = 'id_user';
        $fields[] = 'id_group_edit';
        $fields[] = 'hidden';
    }

    $reports = [];
    $all_reports = @db_get_all_rows_filter('treport', $filter, $fields);

    if (empty($all_reports)) {
        $all_reports = [];
    }

    if ($group) {
        $groups = $group;
    } else {
        // Recheck in all reports if the user have permissions to see each report.
        $groups = users_get_groups($config['id_user'], $privileges, $returnAllGroup);
        if ($strict_user) {
            $groups = users_get_strict_mode_groups($config['id_user'], $returnAllGroup);
        }
    }

    foreach ($all_reports as $report) {
        // If the report is not in all group.
        if ($report['id_group'] != 0) {
            if (!in_array($report['id_group'], array_keys($groups))) {
                continue;
            }

            if ($config['id_user'] != $report['id_user']
                && ! check_acl($config['id_user'], $report['id_group'], $privileges)
            ) {
                continue;
            }
        }

        array_push($reports, $report);
    }

    return $reports;
}


/**
 * Creates a report.
 *
 * @param string Report name.
 * @param int Group where the report will operate.
 * @param array Extra values to be set. Notice that id_user is automatically
 * set to the logged user.
 *
 * @return mixed New report id if created. False if it could not be created.
 */
function reports_create_report($name, $id_group, $values=false)
{
    global $config;

    if (! is_array($values)) {
        $values = [];
    }

    $values['name'] = $name;
    $values['id_group'] = $id_group;
    $values['id_user'] = $config['id_user'];

    return @db_process_sql_insert('treport', $values);
}


/**
 * Updates a report.
 *
 * @param int Report id.
 * @param array Extra values to be set.
 *
 * @return boolean True if the report was updated. False otherwise.
 */
function reports_update_report($id_report, $values)
{
    $report = reports_get_report($id_report, false, ['id_report']);
    if ($report === false) {
        return false;
    }

    return (@db_process_sql_update(
        'treport',
        $values,
        ['id_report' => $id_report]
    )) !== false;
}


/**
 * Deletes a report.
 *
 * @param int Report id to be deleted.
 *
 * @return boolean True if deleted, false otherwise.
 */
function reports_delete_report($id_report)
{
    $id_report = safe_int($id_report);
    if (empty($id_report)) {
        return false;
    }

    $report = reports_get_report($id_report);
    if ($report === false) {
        return false;
    }

    @db_process_sql_delete('treport_content', ['id_report' => $id_report]);
    return @db_process_sql_delete('treport', ['id_report' => $id_report]);
}


/**
 * Deletes a content from a report.
 *
 * @param int Report content id to be deleted.
 *
 * @return boolean True if deleted, false otherwise.
 */
function reports_get_content($id_report_content, $filter=false, $fields=false)
{
    $id_report_content = safe_int($id_report_content);
    if (empty($id_report_content)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    if (is_array($fields)) {
        $fields[] = 'id_report';
    }

    $filter['id_rc'] = $id_report_content;

    $content = @db_get_row_filter('treport_content', $filter, $fields);
    if ($content === false) {
        return false;
    }

    $report = reports_get_report($content['id_report']);
    if ($report === false) {
        return false;
    }

    return $content;
}


/**
 * Get all the contents of a report.
 *
 * @param int Report id to get contents.
 * @param array Extra filters for the contents.
 * @param array Fields to be fetched. All fields by default
 *
 * @return array All the contents of a report.
 */
function reports_create_content($id_report, $values)
{
    global $config;

    $id_report = safe_int($id_report);
    if (empty($id_report)) {
        return false;
    }

    $report = reports_get_report($id_report);
    if ($report === false) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    $values['id_report'] = $id_report;

    switch ($config['dbtype']) {
        case 'mysql':
            unset($values['`order`']);

            $order = (int) db_get_value('MAX(`order`)', 'treport_content', 'id_report', $id_report);
            $values['`order`'] = ($order + 1);
        break;

        case 'postgresql':
        case 'oracle':
            unset($values['"order"']);

            $order = (int) db_get_value('MAX("order")', 'treport_content', 'id_report', $id_report);
            $values['"order"'] = ($order + 1);
        break;
    }

    return @db_process_sql_insert('treport_content', $values);
}


/**
 * Get all the contents of a report.
 *
 * @param int Report id to get contents.
 * @param array Extra filters for the contents.
 * @param array Fields to be fetched. All fields by default
 *
 * @return array All the contents of a report.
 */
function reports_get_contents($id_report, $filter=false, $fields=false)
{
    $id_report = safe_int($id_report);
    if (empty($id_report)) {
        return [];
    }

    $report = reports_get_report($id_report);
    if ($report === false) {
        return [];
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_report'] = $id_report;
    $filter['order'] = '`order`';

    $contents = db_get_all_rows_filter('treport_content', $filter, $fields);
    if ($contents === false) {
        return [];
    }

    return $contents;
}


/**
 * Moves a content from a report up.
 *
 * @param int Report content id to be moved.
 *
 * @return boolean True if moved, false otherwise.
 */
function reports_move_content_up($id_report_content)
{
    global $config;

    if (empty($id_report_content)) {
        return false;
    }

    $content = reports_get_content($id_report_content);
    if ($content === false) {
        return false;
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $order = db_get_value('`order`', 'treport_content', 'id_rc', $id_report_content);
            // Set the previous element order to the current of the content we want to change
            db_process_sql_update(
                'treport_content',
                ['`order` = `order` + 1'],
                [
                    'id_report' => $content['id_report'],
                    '`order` = '.($order - 1)
                ]
            );

        return (@db_process_sql_update(
            'treport_content',
            ['`order` = `order` - 1'],
            ['id_rc' => $id_report_content]
        )) !== false;

            break;
        case 'postgresql':
        case 'oracle':
            $order = db_get_value('"order"', 'treport_content', 'id_rc', $id_report_content);
            // Set the previous element order to the current of the content we want to change
            db_process_sql_update(
                'treport_content',
                ['"order" = "order" + 1'],
                [
                    'id_report' => $content['id_report'],
                    '"order" = '.($order - 1)
                ]
            );

        return (@db_process_sql_update(
            'treport_content',
            ['"order" = "order" - 1'],
            ['id_rc' => $id_report_content]
        )) !== false;

            break;
    }
}


/**
 * Moves a content from a report up.
 *
 * @param int Report content id to be moved.
 *
 * @return boolean True if moved, false otherwise.
 */
function reports_move_content_down($id_report_content)
{
    global $config;

    if (empty($id_report_content)) {
        return false;
    }

    $content = reports_get_content($id_report_content);
    if ($content === false) {
        return false;
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $order = db_get_value('`order`', 'treport_content', 'id_rc', $id_report_content);
            // Set the previous element order to the current of the content we want to change
            db_process_sql_update(
                'treport_content',
                ['`order` = `order` - 1'],
                [
                    'id_report' => (int) $content['id_report'],
                    '`order` = '.($order + 1)
                ]
            );
        return (@db_process_sql_update(
            'treport_content',
            ['`order` = `order` + 1'],
            ['id_rc' => $id_report_content]
        )) !== false;

            break;
        case 'postgresql':
        case 'oracle':
            $order = db_get_value('"order"', 'treport_content', 'id_rc', $id_report_content);
            // Set the previous element order to the current of the content we want to change
            db_process_sql_update(
                'treport_content',
                ['"order" = "order" - 1'],
                [
                    'id_report' => (int) $content['id_report'],
                    '"order" = '.($order + 1)
                ]
            );
        return (@db_process_sql_update(
            'treport_content',
            ['"order" = "order" + 1'],
            ['id_rc' => $id_report_content]
        )) !== false;

            break;
    }
}


/**
 * Deletes a content from a report.
 *
 * @param int Report content id to be deleted.
 *
 * @return boolean True if deleted, false otherwise.
 */
function reports_delete_content($id_report_content)
{
    if (empty($id_report_content)) {
        return false;
    }

    $content = reports_get_content($id_report_content);
    if ($content === false) {
        return false;
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $order = db_get_value('`order`', 'treport_content', 'id_rc', $id_report_content);
            db_process_sql_update(
                'treport_content',
                ['`order` = `order` - 1'],
                [
                    'id_report' => (int) $content['id_report'],
                    '`order` > '.$order
                ]
            );
        break;

        case 'postgresql':
        case 'oracle':
            $order = db_get_value('"order"', 'treport_content', 'id_rc', $id_report_content);
            db_process_sql_update(
                'treport_content',
                ['"order" = "order" - 1'],
                [
                    'id_report' => (int) $content['id_report'],
                    '"order" > '.$order
                ]
            );
        break;
    }

    return (@db_process_sql_delete(
        'treport_content',
        ['id_rc' => $id_report_content]
    )) !== false;
}


/**
 * Get report type name from type id.
 *
 * @param integer $type     Type id of the report.
 * @param boolean $template Set true for to get types for templates. By default false.
 *
 * @return string Report type name.
 */
function get_report_name($type, $template=false)
{
    $types = reports_get_report_types($template);
    if (! isset($types[$type])) {
        return __('Unknown');
    }

    if ($type == 'automatic_custom_graph') {
        return __('Custom graph');
    }

    return $types[$type]['name'];
}


/**
 * Get report type data source from type id.
 *
 * TODO: Better documentation as to what this function does
 *
 * @param mixed $type Type id or type name of the report.
 *
 * @return string Report type name.
 */
function get_report_type_data_source($type)
{
    switch ($type) {
        case 1:
        case 'simple_graph':
        case 6:
        case 'monitor_report':
        case 7:
        case 'avg_value':
        case 8:
        case 'max_value':
        case 9:
        case 'min_value':
        case 10:
        case 'sumatory':
        case 'agent_detailed_event':
        return 'module';

            break;
        case 2:
        case 'custom_graph':
        case 'automatic_custom_graph':
        return 'custom-graph';

            break;
        case 3:
        case 'SLA':
        case 4:
        case 'event_report':
        case 5:
        case 'alert_report':
        case 11:
        case 'general_group_report':
        case 12:
        case 'monitor_health':
        case 13:
        case 'agents_detailed':
        return 'agent-group';

            break;
    }

    return 'unknown';
}


/**
 * Get report types in an array.
 *
 * @param boolean $template   Set true for to get types for templates. By default false.
 * @param boolean $not_editor When this function is not used in item editors.
 *
 * @return array An array with all the possible reports in Pandora where the array index is the report id.
 */
function reports_get_report_types($template=false, $not_editor=false)
{
    global $config;

    $types = [];

    $types['simple_graph'] = [
        'optgroup' => __('Graphs'),
        'name'     => __('Simple graph'),
    ];
    $types['simple_baseline_graph'] = [
        'optgroup' => __('Graphs'),
        'name'     => __('Simple baseline graph'),
    ];
    if ($not_editor == false) {
        $types['automatic_custom_graph'] = [
            'optgroup' => __('Graphs'),
            'name'     => __('Custom graph'),
        ];
    }

    $types['custom_graph'] = [
        'optgroup' => __('Graphs'),
        'name'     => __('Custom graph'),
    ];
    // Only pandora managers have access to the whole database
    if (check_acl($config['id_user'], 0, 'PM')) {
        $types['sql_graph_vbar'] = [
            'optgroup' => __('Graphs'),
            'name'     => __('SQL vertical bar graph'),
        ];
        $types['sql_graph_pie'] = [
            'optgroup' => __('Graphs'),
            'name'     => __('SQL pie graph'),
        ];
        $types['sql_graph_hbar'] = [
            'optgroup' => __('Graphs'),
            'name'     => __('SQL horizonal bar graph'),
        ];
    }

    if ($template) {
        $types['automatic_graph'] = [
            'optgroup' => __('Graphs'),
            'name'     => __('Automatic combined Graph'),
        ];
    }

    $types['availability_graph'] = [
        'optgroup' => __('Graphs'),
        'name'     => __('Availability graph'),
    ];

    $types['module_histogram_graph'] = [
        'optgroup' => __('Graphs'),
        'name'     => __('Module Histogram graph'),
    ];

    /*
        $types['TTRT'] = [
        'optgroup' => __('ITIL'),
        'name'     => __('TTRT'),
        ];
        $types['TTO'] = [
        'optgroup' => __('ITIL'),
        'name'     => __('TTO'),
        ];
        $types['MTBF'] = [
        'optgroup' => __('ITIL'),
        'name'     => __('MTBF'),
        ];
        $types['MTTR'] = [
        'optgroup' => __('ITIL'),
        'name'     => __('MTTR'),
        ];
    */
    $types['SLA'] = [
        'optgroup' => __('SLA'),
        'name'     => __('S.L.A.'),
    ];
    if ($config['enterprise_installed']) {
        $types['SLA_monthly'] = [
            'optgroup' => __('SLA'),
            'name'     => __('Monthly S.L.A.'),
        ];
        $types['SLA_weekly'] = [
            'optgroup' => __('SLA'),
            'name'     => __('Weekly S.L.A.'),
        ];
        $types['SLA_hourly'] = [
            'optgroup' => __('SLA'),
            'name'     => __('Hourly S.L.A.'),
        ];

        if (!$config['metaconsole'] && !$template) {
            $types['SLA_services'] = [
                'optgroup' => __('SLA'),
                'name'     => __('Services S.L.A.'),
            ];
        }
    }

    $types['prediction_date'] = [
        'optgroup' => __('Forecasting'),
        'name'     => __('Prediction date'),
    ];
    $types['projection_graph'] = [
        'optgroup' => __('Forecasting'),
        'name'     => __('Projection graph'),
    ];

    $types['avg_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Avg. Value'),
    ];
    $types['max_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Max. Value'),
    ];
    $types['min_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Min. Value'),
    ];
    $types['monitor_report'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Monitor report'),
    ];
    $types['database_serialized'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Serialize data'),
    ];
    $types['sumatory'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Summatory'),
    ];
    $types['historical_data'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Historical Data'),
    ];
    $types['increment'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Increment'),
    ];

    $types['general'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('General'),
    ];
    $types['group_report'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Group report'),
    ];
    $types['exception'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Exception'),
    ];
    if ($config['metaconsole'] != 1) {
        if (!$template) {
            $types['agent_module'] = [
                'optgroup' => __('Grouped'),
                'name'     => __('Agents/Modules'),
            ];
        }
    }

    // Only pandora managers have access to the whole database.
    if (check_acl($config['id_user'], 0, 'PM')) {
        $types['sql'] = [
            'optgroup' => __('Grouped'),
            'name'     => __('SQL query'),
        ];
    }

    $types['top_n'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Top n'),
    ];
    $types['network_interfaces_report'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Network interfaces'),
    ];
    $types['availability'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Availability'),
    ];

    $types['text'] = [
        'optgroup' => __('Text/HTML '),
        'name'     => __('Text'),
    ];
    $types['url'] = [
        'optgroup' => __('Text/HTML '),
        'name'     => __('Import text from URL'),
    ];

    $types['alert_report_module'] = [
        'optgroup' => __('Alerts'),
        'name'     => __('Alert report module'),
    ];
    $types['alert_report_agent'] = [
        'optgroup' => __('Alerts'),
        'name'     => __('Alert report agent'),
    ];
    if (!$template) {
        $types['alert_report_group'] = [
            'optgroup' => __('Alerts'),
            'name'     => __('Alert report group'),
        ];
    }

    $types['event_report_agent'] = [
        'optgroup' => __('Events'),
        'name'     => __('Event report agent'),
    ];
    $types['event_report_module'] = [
        'optgroup' => __('Events'),
        'name'     => __('Event report module'),
    ];
    $types['event_report_group'] = [
        'optgroup' => __('Events'),
        'name'     => __('Event report group'),
    ];

    if ($config['enterprise_installed']) {
        $types['inventory'] = [
            'optgroup' => __('Inventory'),
            'name'     => __('Inventory'),
        ];
        if (!$template) {
            $types['inventory_changes'] = [
                'optgroup' => __('Inventory'),
                'name'     => __('Inventory changes'),
            ];
        }
    }

    if (!$template) {
        $types['agent_configuration'] = [
            'optgroup' => __('Configuration'),
            'name'     => __('Agent configuration'),
        ];
        $types['group_configuration'] = [
            'optgroup' => __('Configuration'),
            'name'     => __('Group configuration'),
        ];
        $types['netflow_area'] = [
            'optgroup' => __('Netflow'),
            'name'     => __('Netflow area chart'),
        ];
        $types['netflow_data'] = [
            'optgroup' => __('Netflow'),
            'name'     => __('Netflow data table'),
        ];
        $types['netflow_summary'] = [
            'optgroup' => __('Netflow'),
            'name'     => __('Netflow summary table'),
        ];
    }

    if ($config['enterprise_installed']) {
        $types['event_report_log'] = [
            'optgroup' => __('Log'),
            'name'     => __('Log report'),
        ];
    }

    $types['nt_top_n'] = [
        'optgroup' => __('Network traffic'),
        'name'     => __('Network Traffic Top N'),
    ];

    return $types;
}
