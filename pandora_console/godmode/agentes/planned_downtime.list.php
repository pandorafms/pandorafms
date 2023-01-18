<?php
/**
 * Planned downtimes list.
 *
 * @category   Planned downtimes
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

// Load global vars.
global $config;

check_login();

$read_permisson = (bool) check_acl($config['id_user'], 0, 'AR');
$write_permisson = (bool) check_acl($config['id_user'], 0, 'AD');
$manage_permisson = (bool) check_acl($config['id_user'], 0, 'AW');
$access = null;
if ($read_permisson === true) {
    $access = 'AR';
}

if ($write_permisson === true) {
    $access = 'AD';
}

if ($manage_permisson === true) {
    $access = 'AW';
}

if ($read_permisson === false && $manage_permisson === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access downtime scheduler'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_users.php';
require_once 'include/functions_events.php';
require_once 'include/functions_planned_downtimes.php';
require_once 'include/functions_reporting.php';

if (is_ajax() === true) {
    $show_info_agents_modules_affected = (bool) get_parameter(
        'show_info_agents_modules_affected',
        false
    );

    $get_info_agents_modules_affected = (bool) get_parameter(
        'get_info_agents_modules_affected',
        false
    );

    if ($show_info_agents_modules_affected === true) {
        $id = (int) get_parameter('id', 0);

        $columns = [
            'agent_name',
            'module_name',
        ];

        $column_names = [
            __('Agents'),
            __('Modules'),
        ];

        ui_print_datatable(
            [
                'id'                  => 'agent_modules_affected_planned_downtime',
                'class'               => 'info_table',
                'style'               => 'width: 100%',
                'columns'             => $columns,
                'column_names'        => $column_names,
                'ajax_url'            => 'godmode/agentes/planned_downtime.list',
                'ajax_data'           => [
                    'get_info_agents_modules_affected' => 1,
                    'id'                               => $id,
                ],
                'order'               => [
                    'field'     => 'agent_name',
                    'direction' => 'asc',
                ],
                'search_button_class' => 'sub filter float-right',
                'form'                => [
                    'inputs' => [
                        [
                            'label' => __('Agents'),
                            'type'  => 'text',
                            'class' => 'w200px',
                            'id'    => 'filter_agents',
                            'name'  => 'filter_agents',
                        ],
                        [
                            'label' => __('Modules'),
                            'type'  => 'text',
                            'class' => 'w200px',
                            'id'    => 'filter_modules',
                            'name'  => 'filter_modules',
                        ],
                    ],
                ],
            ]
        );

        return;
    }

    if ($get_info_agents_modules_affected === true) {
        $id = (int) get_parameter('id', 0);

        // Catch post parameters.
        $options = [
            'limit'   => get_parameter('start', 0),
            'offset'  => get_parameter('length', $config['block_size']),
            'order'   => get_datatable_order(),
            'filters' => get_parameter('filter', []),
        ];

        $type_downtime = db_get_value_filter(
            'type_downtime',
            'tplanned_downtime',
            ['id' => $id]
        );

        if ($type_downtime === 'disable_agents') {
            $sql = sprintf(
                'SELECT ta.alias as agent_name
                    FROM tplanned_downtime_agents tpa JOIN tagente ta
                    ON tpa.id_agent = ta.id_agente
                    WHERE tpa.id_downtime = %d',
                $id
            );
            $data = db_get_all_rows_sql($sql);

            if (empty($data) === false) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        global $config;
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $tmp->agent_name  = io_safe_output($item['agent_name']);
                        $tmp->module_name   = __('All modules');

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }
        } else {
            $data = get_agents_modules_planned_dowtime($id, $options);
        }


        $count = get_agents_modules_planned_dowtime($id, $options, $count);

        echo json_encode(
            [
                'data'            => $data,
                'recordsTotal'    => $count[0]['total'],
                'recordsFiltered' => $count[0]['total'],
            ]
        );
        return;
    }

    return;
}

$malformed_downtimes = planned_downtimes_get_malformed();
$malformed_downtimes_exist = (empty($malformed_downtimes) === false) ? true : false;
$migrate_malformed = (bool) get_parameter('migrate_malformed');
if ($migrate_malformed === true) {
    $migration_result = planned_downtimes_migrate_malformed_downtimes();

    $str = 'An error occurred while migrating the malformed scheduled downtimes';
    $str2 = 'Please run the migration again or contact with the administrator';
    if ((bool) $migration_result['status'] === false) {
        ui_print_error_message(
            __($str).'. '.__($str2)
        );
        echo '<br>';
    }
}

// Header.
ui_print_page_header(
    __('Scheduled Downtime'),
    'images/gm_monitoring.png',
    false,
    '',
    true,
    ''
);

$id_downtime = (int) get_parameter('id_downtime', 0);

$stop_downtime = (bool) get_parameter('stop_downtime');
// STOP DOWNTIME.
if ($stop_downtime === true) {
    $downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);

    // Check AD permission on the downtime.
    if (empty($downtime) === true
        || ((bool) check_acl($config['id_user'], $downtime['id_group'], 'AD') === false
        && (bool) check_acl($config['id_user'], $downtime['id_group'], 'AW') === false)
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access downtime scheduler'
        );
        include 'general/noaccess.php';
        return;
    }

    $result = planned_downtimes_stop($downtime);

    if ($result === false) {
        ui_print_error_message(
            __('An error occurred stopping the scheduled downtime')
        );
    } else {
        echo $result['message'];
    }
}

$delete_downtime = (int) get_parameter('delete_downtime');
// DELETE WHOLE DOWNTIME!
if (empty($delete_downtime) === false) {
    $downtime = db_get_row('tplanned_downtime', 'id', $id_downtime);

    // Check AD permission on the downtime.
    if (empty($downtime) === true
        || ((bool) check_acl($config['id_user'], $downtime['id_group'], 'AD') === false
        && (bool) check_acl($config['id_user'], $downtime['id_group'], 'AW') === false)
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access downtime scheduler'
        );
        include 'general/noaccess.php';
        return;
    }

    // The downtime shouldn't be running!!
    if ((bool) $downtime['executed'] === true) {
        ui_print_error_message(__('This scheduled downtime is running'));
    } else {
        $result = db_process_sql_delete(
            'tplanned_downtime',
            ['id' => $id_downtime]
        );

        ui_print_result_message(
            $result,
            __('Successfully deleted'),
            __('Not deleted. Error deleting data')
        );
    }
}

// Filter parameters.
$offset = (int) get_parameter('offset');
$filter_params = [];

$search_text = (string) get_parameter('search_text');
$date_from = (string) get_parameter('date_from');
$date_to = (string) get_parameter('date_to');
$execution_type = (string) get_parameter('execution_type');
$show_archived = (bool) get_parameter('archived');
$agent_id = (int) get_parameter('agent_id');
$agent_name = (string) ((empty($agent_id) === false) ? get_parameter('agent_name') : '');
$module_id = (int) get_parameter('module_name_hidden');
$module_name = (string) ((empty($module_id) === false) ? get_parameter('module_name') : '');

$filter_params['search_text'] = $search_text;
$filter_params['date_from'] = $date_from;
$filter_params['date_to'] = $date_to;
$filter_params['execution_type'] = $execution_type;
$filter_params['archived'] = $show_archived;
$filter_params['agent_id'] = $agent_id;
$filter_params['agent_name'] = $agent_name;
$filter_params['module_id'] = $module_id;
$filter_params['module_name'] = $module_name;

$filter_params_str = http_build_query($filter_params);

// Table filter.
$table_form = new StdClass();
$table_form->class = 'databox filters';
$table_form->width = '100%';
$table_form->rowstyle = [];
$table_form->data = [];

$row = [];

// Search text.
$row[] = __('Search').'&nbsp;'.html_print_input_text(
    'search_text',
    $search_text,
    '',
    50,
    250,
    true
);
// Dates.
$date_inputs = __('From').'&nbsp;'.html_print_input_text(
    'date_from',
    $date_from,
    '',
    10,
    10,
    true
);
$date_inputs .= '&nbsp;&nbsp;';
$date_inputs .= __('To').'&nbsp;'.html_print_input_text(
    'date_to',
    $date_to,
    '',
    10,
    10,
    true
);
$row[] = $date_inputs;

$table_form->data[] = $row;

$row = [];

// Execution type.
$execution_type_fields = [
    'once'         => __('Once'),
    'periodically' => __('Periodically'),
    'cron'         => __('Cron'),
];
$row[] = __('Execution type').'&nbsp;'.html_print_select(
    $execution_type_fields,
    'execution_type',
    $execution_type,
    '',
    __('Any'),
    '',
    true,
    false,
    false
);
// Show past downtimes.
$row[] = __('Show past downtimes').'&nbsp;'.html_print_checkbox(
    'archived',
    1,
    $show_archived,
    true
);

$table_form->data[] = $row;

$row = [];

// Agent.
$params = [];
$params['show_helptip'] = true;
$params['input_name'] = 'agent_name';
$params['value'] = $agent_name;
$params['return'] = true;
$params['print_hidden_input_idagent'] = true;
$params['hidden_input_idagent_name'] = 'agent_id';
$params['hidden_input_idagent_value'] = $agent_id;
$agent_input = __('Agent').'&nbsp;'.ui_print_agent_autocomplete_input($params);
$row[] = $agent_input;

// Module.
$row[] = __('Module').'&nbsp;'.html_print_autocomplete_modules(
    'module_name',
    $module_name,
    false,
    true,
    '',
    [],
    true
);

$row[] = html_print_submit_button(
    __('Search'),
    'search',
    false,
    'class="sub search"',
    true
);

$table_form->data[] = $row;
// End of table filter.
// Useful to know if the user has done a form filtering.
$filter_performed = false;

$downtimes = [];
$groups = users_get_groups(false, $access);
if (empty($groups) === false) {
    $where_values = '1=1';

    $groups_string = implode(',', array_keys($groups));
    $where_values .= sprintf(' AND id_group IN (%s)', $groups_string);

    // WARNING: add $filter_performed = true; to any future filter.
    if (empty($search_text) === false) {
        $filter_performed = true;
        $where_values .= sprintf(
            ' AND (name LIKE "%%%s%%" OR description LIKE "%%%s%%")',
            $search_text,
            $search_text
        );
    }

    if (empty($execution_type) === false) {
        $filter_performed = true;
        $where_values .= sprintf(' AND type_execution = "%s"', $execution_type);
    }

    if (empty($date_from) === false) {
        $filter_performed = true;
        $where_values .= sprintf(
            ' AND (type_execution = "periodically"
                OR (type_execution = "once"
                    AND date_from >= "%s")
            )',
            strtotime($date_from.' 00:00:00')
        );
    }

    if (empty($date_to) === false) {
        $filter_performed = true;
        $periodically_monthly_w = sprintf(
            'type_periodicity = "monthly" AND (
                (
                    periodically_day_from <= "%s"
                    AND periodically_day_to >= "%s"
                )
				OR (
                    periodically_day_from > periodically_day_to
					AND (
                        periodically_day_from <= "%s"
                        OR periodically_day_to >= "%s"
                    )
                )
            )',
            date('d', strtotime($date_from)),
            date('d', strtotime($date_to)),
            date('d', strtotime($date_from)),
            date('d', strtotime($date_to))
        );

        $periodically_weekly_days = [];
        $date_from_aux = strtotime($date_from);
        $date_end = strtotime($date_to);
        $days_number = 0;

        while ($date_from_aux <= $date_end && $days_number < 7) {
            $weekday_actual = strtolower(date('l', $date_from_aux));

            $periodically_weekly_days[] = $weekday_actual.' = 1';

            $date_from_aux = ($date_from_aux + SECONDS_1DAY);
            $days_number++;
        }

        $periodically_weekly_w = "type_periodicity = 'weekly' AND (".implode(' OR ', $periodically_weekly_days).')';

        $periodically_w = sprintf(
            'type_execution = "periodically" AND ((%s) OR (%s))',
            $periodically_monthly_w,
            $periodically_weekly_w
        );

        $once_w = sprintf(
            'type_execution = "once" AND date_to <= "%s"',
            strtotime($date_to.' 23:59:59')
        );

        $cron = sprintf(
            'type_execution = "cron"'
        );

        $where_values .= sprintf(
            ' AND ((%s) OR (%s) OR (%s))',
            $periodically_w,
            $once_w,
            $cron
        );
    }

    if ($show_archived === false) {
        $filter_performed = true;
        $where_values .= sprintf(
            ' AND (type_execution = "periodically"
                OR type_execution = "cron"
                OR (type_execution = "once"
                AND date_to >= "%s"))',
            time()
        );
    }

    if (empty($agent_id) === false) {
        $filter_performed = true;
        $where_values .= sprintf(
            ' AND id IN (SELECT id_downtime FROM tplanned_downtime_agents WHERE id_agent = %d)',
            $agent_id
        );
    }

    if (empty($module_id) === false) {
        $filter_performed = true;
        $where_values .= sprintf(
            ' AND (id IN (
                SELECT id_downtime
				FROM tplanned_downtime_modules
			    WHERE id_agent_module = %d)
					OR id IN (
                        SELECT id_downtime
						FROM tplanned_downtime_agents tpda, tagente_modulo tam
						WHERE tpda.id_agent = tam.id_agente
					    AND tam.id_agente_modulo = %d
						AND tpda.all_modules = 1
                    )
                )',
            $module_id,
            $module_id
        );
    }

    // Columns of the table tplanned_downtime.
    $columns = [
        'id',
        'name',
        'description',
        'date_from',
        'date_to',
        'executed',
        'id_group',
        'only_alerts',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
        'periodically_time_from',
        'periodically_time_to',
        'periodically_day_from',
        'periodically_day_to',
        'type_downtime',
        'type_execution',
        'type_periodicity',
        'id_user',
        'cron_interval_from',
        'cron_interval_to',
    ];

    $columns_str = implode(',', $columns);
    $sql = sprintf(
        'SELECT %s
        FROM tplanned_downtime
        WHERE %s
        ORDER BY type_execution DESC, date_from DESC
        LIMIT %d
        OFFSET %d',
        $columns_str,
        $where_values,
        $config['block_size'],
        $offset
    );

    $sql_count = sprintf(
        'SELECT COUNT(id) AS num
		FROM tplanned_downtime
		WHERE %s',
        $where_values
    );

    $downtimes = db_get_all_rows_sql($sql);
    $downtimes_number_res = db_get_all_rows_sql($sql_count);
    $downtimes_number = ($downtimes_number_res !== false) ? $downtimes_number_res[0]['num'] : 0;
}

$url_list = 'index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.list';
$url_editor = 'index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.editor';
// No downtimes cause the user has not anyone.
if ($downtimes === false && $filter_performed === false) {
    include_once $config['homedir'].'/general/first_task/planned_downtime.php';
} else if ($downtimes === false) {
    // No downtimes cause the user performed a search.
    // Filter form.
    echo '<form method="post" action="'.$url_list.'">';
        html_print_table($table_form);
    echo '</form>';

    // Info message.
    echo '<div class="nf">'.__('No scheduled downtime').'</div>';

    echo '<div class="action-buttons w100p" >';

    // Create button.
    if ($write_permisson === true) {
        echo '&nbsp;';
        echo '<form method="post" class="display_in" action="'.$url_editor.'">';
        html_print_submit_button(__('Create'), 'create', false, 'class="sub next"');
        echo '</form>';
    }

    echo '</div>';
} else {
    // Has downtimes.
    echo '<form method="post" action="'.$url_list.'">';
        html_print_table($table_form);
    echo '</form>';

    ui_pagination(
        $downtimes_number,
        $url_list.'&'.$filter_params_str,
        $offset
    );

    // User groups with AR, AD or AW permission.
    $groupsAD = users_get_groups($config['id_user'], $access);
    $groupsAD = array_keys($groupsAD);

    // View available downtimes present in database (if any of them).
    $table = new StdClass();
    $table->class = 'info_table';
    $table->width = '100%';
    $table->cellstyle = [];

    $table->head = [];
    $table->head['name'] = __('Name #Ag.');
    $table->head['description'] = __('Description');
    $table->head['group'] = __('Group');
    $table->head['type'] = __('Type');
    $table->head['execution'] = __('Execution');
    $table->head['configuration'] = __('Configuration');
    $table->head['running'] = __('Running');
    $table->head['agents_modules'] = __('Affected');

    if ($write_permisson === true
        || $manage_permisson === true
    ) {
        $table->head['stop'] = __('Stop downtime');
        $table->head['copy'] = __('Copy');
        $table->head['edit'] = __('Edit');
        $table->head['delete'] = __('Delete');
    }

    $table->align = [];
    $table->align['group'] = 'center';
    $table->align['running'] = 'center';

    if ($write_permisson === true
        || $manage_permisson === true
    ) {
        $table->align['stop'] = 'center';
        $table->align['edit'] = 'center';
        $table->align['delete'] = 'center';
    }

    $table->data = [];

    foreach ($downtimes as $downtime) {
        $data = [];
        $total  = db_get_sql(
            'SELECT COUNT(id_agent)
			FROM tplanned_downtime_agents
			WHERE id_downtime = '.$downtime['id']
        );

        $data['name'] = $downtime['name'].' ('.$total.')';
        $data['description'] = $downtime['description'];
        $data['group'] = ui_print_group_icon($downtime['id_group'], true);

        $type_text = [
            'quiet'                 => __('Quiet'),
            'disable_agents'        => __('Disabled Agents'),
            'disable_agents_alerts' => __('Disabled only Alerts'),
        ];

        $data['type'] = $type_text[$downtime['type_downtime']];

        $execution_text = [
            'once'         => __('Once'),
            'periodically' => __('Periodically'),
            'cron'         => __('Cron'),
        ];

        $data['execution'] = $execution_text[$downtime['type_execution']];

        $data['configuration'] = reporting_format_planned_downtime_dates($downtime);

        if ((int) $downtime['executed'] === 0) {
            $data['running'] = html_print_image(
                'images/pixel_red.png',
                true,
                [
                    'width'  => 20,
                    'height' => 20,
                    'title'  => __('Not running'),
                ]
            );
        } else {
            $data['running'] = html_print_image(
                'images/pixel_green.png',
                true,
                [
                    'width'  => 20,
                    'height' => 20,
                    'title'  => __('Running'),
                ]
            );
        }

        $settings = [
            'url'         => ui_get_full_url('ajax.php', false, false, false),
            'loadingText' => __('Loading, this operation might take several minutes...'),
            'title'       => __('Agents / Modules affected'),
            'id'          => $downtime['id'],
        ];

        $data['agents_modules'] = '<a href="#" onclick=\'dialogAgentModulesAffected('.json_encode($settings).')\'>';
        $data['agents_modules'] .= html_print_image(
            'images/search_big.png',
            true,
            [
                'title' => __('Agents and modules affected'),
                'style' => 'width:22px; height: 22px;',
            ]
        );
        $data['agents_modules'] .= '</a>';

        // If user have writting permissions.
        if (in_array($downtime['id_group'], $groupsAD) === true) {
            // Stop button.
            if ($downtime['type_execution'] === 'once'
                && (int) $downtime['executed'] === 1
            ) {
                if ((bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AW') === true
                    || (bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AD') === true
                ) {
                    $url_list_params = $url_list.'&stop_downtime=1&id_downtime='.$downtime['id'].'&'.$filter_params_str;
                    $data['stop'] = '<a href="'.$url_list_params.'">';
                    $data['stop'] .= html_print_image(
                        'images/cancel.png',
                        true,
                        ['title' => __('Stop downtime')]
                    );
                } else {
                    $data['stop'] = html_print_image(
                        'images/cancel.png',
                        true,
                        ['title' => __('Stop downtime')]
                    );
                }
            } else {
                $data['stop'] = '';
            }

            // Edit & delete buttons.
            if ((int) $downtime['executed'] === 0) {
                if ((bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AW') === true
                    || (bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AD') === true
                ) {
                    // Copy.
                    $data['copy'] = '<a href="'.$url_editor.'&downtime_copy=1&id_downtime='.$downtime['id'].'">';
                    $data['copy'] .= html_print_image(
                        'images/copy.png',
                        true,
                        [
                            'title' => __('Copy'),
                            'class' => 'invert_filter',
                        ]
                    );
                    $data['copy'] .= '</a>';

                    // Edit.
                    $data['edit'] = '<a href="'.$url_editor.'&edit_downtime=1&id_downtime='.$downtime['id'].'">';
                    $data['edit'] .= html_print_image(
                        'images/config.png',
                        true,
                        [
                            'title' => __('Update'),
                            'class' => 'invert_filter',
                        ]
                    );
                    $data['edit'] .= '</a>';

                    // Delete.
                    $url_delete = $url_list.'&delete_downtime=1&id_downtime='.$downtime['id'].'&'.$filter_params_str;
                    $data['delete'] = '<a id="delete_downtime" href="'.$url_delete.'">';
                    $data['delete'] .= html_print_image(
                        'images/cross.png',
                        true,
                        [
                            'title' => __('Delete'),
                            'class' => 'invert_filter',
                        ]
                    );
                    $data['delete'] .= '</a>';
                } else {
                    $data['edit'] = '';
                    $data['delete'] = '';
                }
            } else if ((int) $downtime['executed'] === 1
                && $downtime['type_execution'] === 'once'
            ) {
                if ((bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AW') === true
                    || (bool) check_acl_restricted_all($config['id_user'], $downtime['id_group'], 'AD') === true
                ) {
                    // Copy.
                    $data['copy'] = '<a href="'.$url_editor.'&downtime_copy=1&id_downtime='.$downtime['id'].'">';
                    $data['copy'] .= html_print_image(
                        'images/copy.png',
                        true,
                        [
                            'title' => __('Copy'),
                            'class' => 'invert_filter',
                        ]
                    );
                    $data['copy'] .= '</a>';
                    // Edit.
                    $data['edit'] = '<a href="'.$url_editor.'&edit_downtime=1&id_downtime='.$downtime['id'].'">';
                    $data['edit'] .= html_print_image(
                        'images/config.png',
                        true,
                        [
                            'title' => __('Update'),
                            'class' => 'invert_filter',
                        ]
                    );
                    $data['edit'] .= '</a>';
                    // Delete.
                    $data['delete'] = __('N/A');
                } else {
                    $data['edit'] = '';
                    $data['delete'] = '';
                }
            } else {
                $data['copy'] = '';
                $data['edit'] = '';
                $data['delete'] = '';
            }
        } else {
            $data['stop'] = '';
            $data['copy'] = '';
            $data['edit'] = '';
            $data['delete'] = '';
        }

        if (empty($malformed_downtimes_exist) === false
            && isset($malformed_downtimes[$downtime['id']]) === true
        ) {
            $next_row_num = count($table->data);
            $table->cellstyle[$next_row_num][0] = 'color: red';
            $table->cellstyle[$next_row_num][1] = 'color: red';
            $table->cellstyle[$next_row_num][3] = 'color: red';
            $table->cellstyle[$next_row_num][4] = 'color: red';
            $table->cellstyle[$next_row_num][5] = 'color: red';
        }

        array_push($table->data, $data);
    }

    html_print_table($table);
    ui_pagination(
        $downtimes_number,
        $url_list.'&'.$filter_params_str,
        $offset,
        0,
        false,
        'offset',
        true,
        'pagination-bottom'
    );

    echo '<div class="action-buttons" style="width: '.$table->width.'">';

    // CSV export button.
    echo '<div class="display_in">';
        html_print_button(
            __('Export to CSV'),
            'csv_export',
            false,
            'blockResubmit($(this)); location.href=\'godmode/agentes/planned_downtime.export_csv.php?'.$filter_params_str.'\'',
            'class="sub next"'
        );
    echo '</div>';

    // Create button.
    if ($write_permisson === true) {
        echo '&nbsp;';
        echo '<form method="post" action="'.$url_editor.'" class="display_in" >';
        html_print_submit_button(
            __('Create'),
            'create',
            false,
            'class="sub next"'
        );
        echo '</form>';
    }

    echo '</div>';
}

ui_require_jquery_file(
    'ui.datepicker-'.get_user_language(),
    'include/javascript/i18n/'
);

ui_require_javascript_file('pandora_planned_downtimes');
?>
<script language="javascript" type="text/javascript">

$("input[name=module_name_hidden]").val(<?php echo (int) $module_id; ?>);

$(document).ready (function () {
    $("#text-date_from, #text-date_to")
        .datepicker({dateFormat: "<?php echo DATE_FORMAT_JS; ?>"});

    $.datepicker
        .setDefaults($.datepicker.regional[ "<?php echo get_user_language(); ?>"]);

    $("a#delete_downtime").click(function (e) {
        if (!confirm("<?php echo __('WARNING: If you delete this scheduled downtime, it will not be taken into account in future SLA reports'); ?>")) {
            e.preventDefault();
        }
    });

    if (<?php echo json_encode($malformed_downtimes_exist); ?> && <?php echo json_encode($migrate_malformed == false); ?>) {
        if (confirm("<?php echo __('WARNING: There are malformed scheduled downtimes').'.\n'.__('Do you want to migrate automatically the malformed items?'); ?>")) {
            window.location.href = "index.php?sec=extensions&sec2=godmode/agentes/planned_downtime.list&migrate_malformed=1";
        }
    }
});

</script>
