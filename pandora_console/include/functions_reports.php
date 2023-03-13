<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

    if (!is_array($filter)) {
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

    if (!check_acl($config['id_user'], $report['id_group'], 'RR')) {
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

    if (!is_array($filter)) {
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
                && !check_acl($config['id_user'], $report['id_group'], $privileges)
            ) {
                continue;
            }
        } else {
            if ($returnAllGroup === false) {
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

    if (!is_array($values)) {
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
    global $config;

    $id_report = safe_int($id_report);
    if (empty($id_report)) {
        return false;
    }

    $report = reports_get_report($id_report);
    if ($report === false) {
        return false;
    }

    // Delete report from fav menu.
    db_process_sql_delete(
        'tfavmenu_user',
        [
            'id_element' => $id_report,
            'section'    => 'Reporting',
            'id_user'    => $config['id_user'],
        ]
    );

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

    if (!is_array($filter)) {
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
 * Creates the contents of a report.
 *
 * @param int Report id to get contents.
 * @param array values to be created.
 *
 * @return boolean true id succed, false otherwise.
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

    if (!is_array($values)) {
        return false;
    }

    $values['id_report'] = $id_report;

    switch ($config['dbtype']) {
        case 'mysql':
            if (isset($values['`order`'])) {
                unset($values['`order`']);
            } else {
                unset($values['order']);
            }

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

    if (!is_array($filter)) {
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
    if (!isset($types[$type])) {
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

    // Only pandora managers have access to the whole database.
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
            'name'     => __('SQL horizontal bar graph'),
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

    if ($config['enterprise_installed'] && is_metaconsole() === false) {
        $types['IPAM_network'] = [
            'optgroup' => __('IPAM'),
            'name'     => __('IPAM networks'),
        ];
    }

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

        if ($template === false) {
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
        'name'     => __('Avg. value'),
    ];
    $types['max_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Max. value'),
    ];
    $types['min_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Min. value'),
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
    $types['last_value'] = [
        'optgroup' => __('Modules'),
        'name'     => __('Last value'),
    ];

    $types['general'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('General'),
    ];
    if (is_metaconsole()) {
        if ($template === false) {
            $types['group_report'] = [
                'optgroup' => __('Grouped'),
                'name'     => __('Group report'),
            ];
        }
    } else {
        $types['group_report'] = [
            'optgroup' => __('Grouped'),
            'name'     => __('Group report'),
        ];
    }

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

    $types['agent_module_status'] = [
        'optgroup' => __('Grouped'),
        'name'     => __('Agents/Modules status'),
    ];

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
    if (!$template) {
        $types['custom_render'] = [
            'optgroup' => __('Grouped'),
            'name'     => __('Custom Render'),
        ];
    }

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
        'name'     => __('Module alert report'),
    ];
    $types['alert_report_agent'] = [
        'optgroup' => __('Alerts'),
        'name'     => __('Agent alert report '),
    ];

    if (!$template) {
        $types['alert_report_group'] = [
            'optgroup' => __('Alerts'),
            'name'     => __('Group alert report'),
        ];
    }

    $types['alert_report_actions'] = [
        'optgroup' => __('Alerts'),
        'name'     => __('Actions alert report '),
    ];

    $types['event_report_module'] = [
        'optgroup' => __('Events'),
        'name'     => __('Module event report'),
    ];
    $types['event_report_agent'] = [
        'optgroup' => __('Events'),
        'name'     => __('Agent event report'),
    ];
    $types['event_report_group'] = [
        'optgroup' => __('Events'),
        'name'     => __('Group event report'),
    ];

    if (!$template) {
        $types['agents_inventory'] = [
            'optgroup' => __('Inventory'),
            'name'     => __('Agents inventory'),
        ];
    }

    if (!$template) {
        $types['modules_inventory'] = [
            'optgroup' => __('Inventory'),
            'name'     => __('Modules inventory'),
        ];
    }

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
        $types['netflow_top_N'] = [
            'optgroup' => __('Netflow'),
            'name'     => __('Netflow top-N connections'),
        ];
    }

    if ($config['enterprise_installed'] && $template === false && !is_metaconsole()) {
        $types['event_report_log'] = [
            'optgroup' => __('Log'),
            'name'     => __('Log report'),
        ];
    }

    if ($template === false) {
        $types['permissions_report'] = [
            'optgroup' => __('Permissions report'),
            'name'     => __('Permissions report'),
        ];
    }

    $types['ncm'] = [
        'optgroup' => __('NCM'),
        'name'     => __('Network configuration changes'),
    ];

    return $types;
}


function reports_copy_report($id_report)
{
    $report = reports_get_report($id_report);

    // Unset original report id_report.
    unset($report['id_report']);

    $original_name = $report['name'];
    $original_group = $report['id_group'];

    $copy_name = io_safe_input(sprintf(__('copy of %s'), io_safe_output($original_name)));

    $copy_report = reports_create_report($copy_name, $original_group, $report);

    if ($copy_report !== false) {
        $original_contents = reports_get_contents($id_report);
        if (empty($original_contents) === false) {
            foreach ($original_contents as $original_content) {
                $original_content['id_report'] = $copy_report;
                $original_id_rc = $original_content['id_rc'];
                unset($original_content['id_rc']);
                $result_content = db_process_sql_insert('treport_content', $original_content);

                if ($result_content === false) {
                    $result = false;
                    break;
                }

                switch (io_safe_output($original_content['type'])) {
                    case 'SLA':
                    case 'SLA_monthly':
                    case 'SLA_weekly':
                    case 'SLA_hourly':
                    case 'availability_graph':

                        $slas = db_get_all_rows_field_filter('treport_content_sla_combined', 'id_report_content', $original_id_rc);
                        if ($slas === false) {
                            $slas = [];
                        }

                        foreach ($slas as $sla) {
                            unset($sla['id']);

                            // Set id report to copy id.
                            $sla['id_report_content'] = $result_content;
                            $sla_copy = db_process_sql_insert('treport_content_sla_combined', $sla);

                            if ($sla_copy === false) {
                                reports_delete_content($result_content);
                                $result = false;
                                break;
                            }
                        }
                    break;

                    case 'general':
                    case 'top_n':
                    case 'availability':
                    case 'exception':

                        $items = db_get_all_rows_field_filter('treport_content_item', 'id_report_content', $original_id_rc);
                        if ($items === false) {
                            $items = [];
                        }

                        foreach ($items as $item) {
                            unset($item['id']);

                            // Set id report to copy id.
                            $item['id_report_content'] = $result_content;
                            $item_copy = db_process_sql_insert('treport_content_item', $item);

                            if ($item_copy === false) {
                                reports_delete_content($result_content);
                                $result = false;
                                break;
                            }
                        }
                    break;

                    default:
                        // Empty default.
                    break;
                }
            }
        }
    }

    if ($result === false) {
        reports_delete_report($copy_report);
        return false;
    }

    return true;
}


/**
 * Table custom macros.
 *
 * @param string $data JSON.
 *
 * @return string Html output.
 */
function get_table_custom_macros_report($data)
{
    $table = new StdClass();
    $table->data = [];
    $table->width = '100%';
    $table->class = 'info_table';
    $table->id = 'table-macros-definition';
    $table->rowclass = [];

    $table->size = [];
    $table->size['name'] = '20%';
    $table->size['type'] = '20%';
    $table->size['value'] = '50%';
    $table->size['op'] = '10%';

    $table->head = [];
    $table->head['name'] = __('Macro');
    $table->head['type'] = __('Type');
    $table->head['value'] = __('Value');
    $table->head['op'] = html_print_image(
        'images/add.png',
        true,
        [
            'class'   => 'invert_filter btn_debugModule',
            'style'   => 'cursor: pointer; filter: invert(100%);',
            'onclick' => 'addCustomFieldRow();',
        ]
    );

    $list_macro_custom_type = [
        0 => __('String'),
        1 => __('Sql'),
        2 => __('Graph Sql'),
        3 => __('Simple graph'),
    ];

    $data = json_decode($data, true);
    if (is_array($data) === false || empty($data) === true) {
        $data = [];
        $data[0] = [
            'name'  => '',
            'type'  => 0,
            'value' => '',
        ];
    }

    $table->data = [];
    foreach ($data as $key_macro => $value_data_macro) {
        $table->rowclass[$key_macro] = 'tr-macros-definition';
        $table->data[$key_macro]['name'] = html_print_input_text_extended(
            'macro_custom_name[]',
            $value_data_macro['name'],
            ($key_macro === 0) ? 'macro_custom_name' : 'macro_custom_name_'.$key_macro,
            '',
            15,
            255,
            false,
            '',
            'class="fullwidth"',
            true
        );

        $table->data[$key_macro]['name'] .= html_print_input_hidden(
            'macro_custom_key[]',
            $key_macro,
            true,
            false,
            false,
            ($key_macro === 0) ? 'macro_custom_key' : 'macro_custom_key_'.$key_macro
        );

        $table->data[$key_macro]['type'] = html_print_select(
            $list_macro_custom_type,
            'macro_custom_type[]',
            $value_data_macro['type'],
            'change_custom_fields_macros_report('.$key_macro.')',
            '',
            0,
            true,
            false,
            false,
            'fullwidth',
            false,
            'height: 32px;',
            false,
            false,
            false,
            '',
            false,
            false,
            false,
            false,
            false
        );

        $custom_fields = custom_fields_macros_report(
            $value_data_macro,
            $key_macro
        );

        $custom_field_draw = '';
        if (empty($custom_fields) === false) {
            foreach ($custom_fields as $key => $value) {
                $custom_field_draw .= $value;
            }
        }

        $table->data[$key_macro]['value'] = $custom_field_draw;

        $table->data[$key_macro]['op'] = html_print_image(
            'images/clean.png',
            true,
            [
                'class'   => 'invert_filter icon-clean-custom-macro',
                'style'   => 'cursor: pointer;',
                'onclick' => 'cleanCustomFieldRow('.$key_macro.')',
            ]
        );

        $styles_remove = 'cursor: pointer; margin-right:10px;';
        if ($key_macro === 0) {
            $styles_remove .= 'display:none';
        }

        $table->data[$key_macro]['op'] .= html_print_image(
            'images/delete.png',
            true,
            [
                'class'   => 'invert_filter icon-delete-custom-macro',
                'style'   => $styles_remove,
                'onclick' => 'removeCustomFieldRow('.$key_macro.')',
            ]
        );
    }

    return html_print_table(
        $table,
        true
    );

}


/**
 * Custom field macros report
 *
 * @param array  $macro     Info macro.
 * @param string $key_macro Key.
 *
 * @return array
 */
function custom_fields_macros_report($macro, $key_macro)
{
    $result = [];

    switch ($macro['type']) {
        case 0:
        case 1:
            $result['value'] = '<div class="custom-field-macro-report">';
            $result['value'] .= '<label>';
            $result['value'] .= ($macro['type'] == 0) ? __('String') : __('Sql');
            $result['value'] .= '</label>';
            $result['value'] .= html_print_input_text_extended(
                'macro_custom_value[]',
                $macro['value'],
                ($key_macro === 0) ? 'macro_custom_value' : 'macro_custom_value_'.$key_macro,
                '',
                15,
                255,
                false,
                '',
                '',
                true
            );
            $result['value'] .= '</div>';
        break;

        case 2:
            $result['value'] = '<div class="custom-field-macro-report mb10">';
            $result['value'] .= '<label>';
            $result['value'] .= __('Sql');
            $result['value'] .= '</label>';
            $result['value'] .= html_print_input_text_extended(
                'macro_custom_value['.$key_macro.'][value]',
                $macro['value'],
                ($key_macro === 0) ? 'macro_custom_value' : 'macro_custom_value_'.$key_macro,
                '',
                15,
                255,
                false,
                '',
                'class="fullwidth"',
                true
            );
            $result['value'] .= '</div>';

            $result['size'] = '<div class="custom-field-macro-report">';
            $result['size'] .= '<label>';
            $result['size'] .= __('Width');
            $result['size'] .= '</label>';
            $result['size'] .= html_print_input_text_extended(
                'macro_custom_value['.$key_macro.'][width]',
                $macro['width'],
                ($key_macro === 0) ? 'macro_custom_width' : 'macro_custom_width_'.$key_macro,
                '',
                5,
                255,
                false,
                '',
                '',
                true
            );

            $result['size'] .= '<label>';
            $result['size'] .= __('Height');
            $result['size'] .= '</label>';
            $result['size'] .= html_print_input_text_extended(
                'macro_custom_value['.$key_macro.'][height]',
                $macro['height'],
                ($key_macro === 0) ? 'macro_custom_height' : 'macro_custom_height_'.$key_macro,
                '',
                5,
                255,
                false,
                '',
                '',
                true
            );
            $result['size'] .= '</div>';
        break;

        case 3:
            $params = [];
            $params['show_helptip'] = true;
            $params['input_name'] = 'macro_custom_value_agent_name_'.$key_macro;
            $params['print_hidden_input_idagent'] = true;
            $params['hidden_input_idagent_id'] = 'macro_custom_value_agent_id_'.$key_macro;
            $params['hidden_input_idagent_name']  = 'macro_custom_value['.$key_macro.'][agent_id]';
            $params['hidden_input_idagent_value'] = $macro['agent_id'];
            $params['javascript_is_function_select'] = true;
            $params['selectbox_id'] = 'macro_custom_value'.$key_macro.'id_agent_module';
            $params['add_none_module'] = false;
            $params['return'] = true;
            $params['disabled_javascript_on_blur_function'] = true;

            if (is_metaconsole() === true) {
                $params['print_input_id_server'] = true;
                $params['metaconsole_enabled'] = true;
                $params['input_id_server_id'] = 'macro_custom_value_id_server_'.$key_macro;
                $params['input_id_server_name']  = 'macro_custom_value['.$key_macro.'][server_id]';
                $params['input_id_server_value'] = $macro['server_id'];
                $params['value'] = agents_meta_get_alias(
                    $macro['agent_id'],
                    'none',
                    $macro['server_id'],
                    true
                );
            } else {
                $params['value'] = agents_get_alias($macro['agent_id']);
            }

            $result['size'] = '<div class="custom-field-macro-report mb10">';
            $result['size'] .= '<label>';
            $result['size'] .= __('Agent');
            $result['size'] .= '</label>';
            $result['size'] .= ui_print_agent_autocomplete_input($params);

            $modules = [];
            if (isset($macro['agent_id']) === true
                && empty($macro['agent_id']) === false
            ) {
                if (is_metaconsole() === true) {
                    $server = db_get_row(
                        'tmetaconsole_setup',
                        'id',
                        $macro['server_id']
                    );
                    if (metaconsole_connect($server) != NOERR) {
                        continue;
                    }
                }

                $modules = agents_get_modules(
                    $macro['agent_id'],
                    false,
                    ['delete_pending' => 0]
                );

                if (is_metaconsole() === true) {
                    metaconsole_restore_db();
                }
            }

            $result['size'] .= '<label>';
            $result['size'] .= __('Module');
            $result['size'] .= '</label>';
            $result['size'] .= html_print_select(
                $modules,
                'macro_custom_value['.$key_macro.'][id_agent_module]',
                $macro['id_agent_module'],
                true,
                __('Select'),
                0,
                true,
                false,
                true,
                '',
                (empty($macro['agent_id']) === true),
                'min-width: 250px;margin-right: 0.5em;'
            );
            $result['size'] .= '</div>';

            $result['size'] .= '<div class="custom-field-macro-report">';
            $result['size'] .= '<label>';
            $result['size'] .= __('Height');
            $result['size'] .= '</label>';
            $result['size'] .= html_print_input_text_extended(
                'macro_custom_value['.$key_macro.'][height]',
                $macro['height'],
                ($key_macro === 0) ? 'macro_custom_height' : 'macro_custom_height_'.$key_macro,
                '',
                5,
                255,
                false,
                '',
                '',
                true
            );

            $result['size'] .= '<label>';
            $result['size'] .= __('Period ');
            $result['size'] .= '</label>';
            $result['size'] .= html_print_input_text_extended(
                'macro_custom_value['.$key_macro.'][period]',
                $macro['period'],
                ($key_macro === 0) ? 'macro_custom_period' : 'macro_custom_period_'.$key_macro,
                '',
                5,
                255,
                false,
                '',
                '',
                true
            );
            $result['size'] .= '</div>';
        break;

        default:
            // Not possible.
        break;
    }

    return $result;
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
function reports_get_report_templates(
    $filter=false,
    $fields=false,
    $returnAllGroup=true,
    $privileges='RR',
    $group=false,
    $strict_user=false
) {
    global $config;

    if (is_array($filter) === false) {
        $filter = [];
    }

    if (is_array($fields) === false) {
        $fields[] = 'id_group';
        $fields[] = 'id_user';
    }

    $templates = [];
    $all_templates = @db_get_all_rows_filter('treport_template', $filter, $fields);

    if (empty($all_templates) === true) {
        $all_templates = [];
    }

    if ($group) {
        $groups = $group;
    } else {
        $groups = users_get_groups($config['id_user'], $privileges, $returnAllGroup);
        if ($strict_user) {
            $groups = users_get_strict_mode_groups($config['id_user'], $returnAllGroup);
        }
    }

    foreach ($all_templates as $template) {
        // If the template is not in all group.
        if ($template['id_group'] != 0) {
            if (!in_array($template['id_group'], array_keys($groups))) {
                continue;
            }

            if ($config['id_user'] != $template['id_user']
                && !check_acl($config['id_user'], $template['id_group'], $privileges)
            ) {
                continue;
            }
        } else {
            if ($returnAllGroup === false) {
                continue;
            }
        }

        array_push($templates, $template);
    }

    return $templates;
}
