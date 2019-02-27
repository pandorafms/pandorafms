<?php

// Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 20012 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_db.php';

// Update the execution interval of the given module.
function cron_update_module_interval($module_id, $cron)
{
    // Check for a valid cron.
    if (!cron_check_syntax($cron)) {
        return;
    }

    if ($cron == '* * * * *') {
        $module_interval = db_get_value_filter(
            'module_interval',
            'tagente_modulo',
            ['id_agente_modulo' => $module_id]
        );
        return db_process_sql(
            'UPDATE tagente_estado SET current_interval = '.$module_interval.' WHERE id_agente_modulo = '.(int) $module_id
        );
    } else {
        return db_process_sql(
            'UPDATE tagente_estado SET current_interval = '.cron_next_execution($cron, $module_interval, $module_id).' WHERE id_agente_modulo = '.(int) $module_id
        );
    }

}


// Get the number of seconds left to the next execution of the given cron entry.
function cron_next_execution($cron, $module_interval, $module_id)
{
    // Get day of the week and month from cron config.
    $cron_array = explode(' ', $cron);
    $minute = $cron_array[0];
    $hour = $cron_array[1];
    $mday = $cron_array[2];
    $month = $cron_array[3];
    $wday = $cron_array[4];

    // Get last execution time.
    $last_execution = db_get_value(
        'utimestamp',
        'tagente_estado',
        'id_agente_modulo',
        $module_id
    );
    $cur_time = ($last_execution !== false) ? $last_execution : time();

    // Any day of the way.
    if ($wday == '*') {
        $nex_time = cron_next_execution_date(
            $cron,
            $cur_time,
            $module_interval
        );
        return ($nex_time - $cur_time);
    }

    // A specific day of the week.
    $count = 0;
    $nex_time = $cur_time;
    do {
        $nex_time = cron_next_execution_date(
            $cron,
            $nex_time,
            $module_interval
        );
        $nex_time_wd = $nex_time;

        $array_nex = explode(' ', date('m w', $nex_time_wd));
        $nex_mon   = $array_nex[0];
        $nex_wday  = $array_nex[1];

        do {
            // Check the day of the week.
            if ($nex_wday == $wday) {
                return ($nex_time_wd - $cur_time);
            }

            // Move to the next day of the month.
            $nex_time_wd += SECONDS_1DAY;

            $array_nex_w = explode(' ', date('m w', $nex_time_wd));
            $nex_mon_wd  = $array_nex_w[0];
            $nex_wday    = $array_nex_w[1];
        } while ($mday == '*' && $nex_mon_wd == $nex_mon);

        $count++;
    } while ($count < SECONDS_1MINUTE);

    // Something went wrong, default to 5 minutes.
    return SECONDS_5MINUTES;
}


// Get the next execution date for the given cron entry in seconds since epoch.
function cron_next_execution_date($cron, $cur_time=false, $module_interval=300)
{
    // Get cron configuration.
    $cron_array = explode(' ', $cron);

    // REMARKS: Months start from 1 in php (different to server)
    // Get current time.
    if ($cur_time === false) {
        $cur_time = time();
    }

    $nex_time = ($cur_time + $module_interval);
    $nex_time_array = explode(' ', date('i H d m Y', $nex_time));
    if (cron_is_in_cron($cron_array, $nex_time_array)) {
        return $nex_time;
    }

    // Update minutes.
    $min_s = cron_get_interval($cron_array[0]);
    $nex_time_array[0] = ($min_s['down'] == '*') ? 0 : $min_s['down'];

    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time >= $cur_time) {
        if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
            return $nex_time;
        }
    }

    // Check if next hour is in cron.
    $nex_time_array[1]++;
    $nex_time = cron_valid_date($nex_time_array);

    if ($nex_time === false) {
        // Update the month day if overflow.
        $nex_time_array[1] = 0;
        $nex_time_array[2]++;
        $nex_time = cron_valid_date($nex_time_array);
        if ($nex_time === false) {
            // Update the month if overflow.
            $nex_time_array[2] = 1;
            $nex_time_array[3]++;
            $nex_time = cron_valid_date($nex_time_array);
            if ($nex_time === false) {
                // Update the year if overflow.
                $nex_time_array[3] = 1;
                $nex_time_array[4]++;
                $nex_time = cron_valid_date($nex_time_array);
            }
        }
    }

    // Check the hour.
    if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
        return $nex_time;
    }

    // Update the hour if fails.
    $hour_s = cron_get_interval($cron_array[1]);
    $nex_time_array[1] = ($hour_s['down'] == '*') ? 0 : $hour_s['down'];

    // When an overflow is passed check the hour update again.
    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time >= $cur_time) {
        if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
            return $nex_time;
        }
    }

    // Check if next day is in cron.
    $nex_time_array[2]++;
    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time === false) {
        // Update the month if overflow.
        $nex_time_array[2] = 1;
        $nex_time_array[3]++;
        $nex_time = cron_valid_date($nex_time_array);
        if ($nex_time === false) {
            // Update the year if overflow.
            $nex_time_array[3] = 1;
            $nex_time_array[4]++;
            $nex_time = cron_valid_date($nex_time_array);
        }
    }

    // Check the day.
    if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
        return $nex_time;
    }

    // Update the day if fails.
    $mday_s = cron_get_interval($cron_array[2]);
    $nex_time_array[2] = ($mday_s['down'] == '*') ? 1 : $mday_s['down'];

    // When an overflow is passed check the hour update in the next execution.
    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time >= $cur_time) {
        if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
            return $nex_time;
        }
    }

    // Check if next month is in cron.
    $nex_time_array[3]++;
    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time === false) {
        // Update the year if overflow.
        $nex_time_array[3] = 1;
        $nex_time_array[4]++;
        $nex_time = cron_valid_date($nex_time_array);
    }

    // Check the month.
    if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
        return $nex_time;
    }

    // Update the month if fails.
    $mon_s = cron_get_interval($cron_array[3]);
    $nex_time_array[3] = ($mon_s['down'] == '*') ? 1 : $mon_s['down'];

    // When an overflow is passed check the hour update in the next execution.
    $nex_time = cron_valid_date($nex_time_array);
    if ($nex_time >= $cur_time) {
        if (cron_is_in_cron($cron_array, $nex_time_array) && $nex_time) {
            return $nex_time;
        }
    }

    // Update the year.
    $nex_time_array[4]++;
    $nex_time = cron_valid_date($nex_time_array);

    return ($nex_time !== false) ? $nex_time : $module_interval;
}


// Get an array with the cron interval.
function cron_get_interval($element)
{
    // Not a range.
    if (!preg_match('/(\d+)\-(\d+)/', $element, $capture)) {
        return [
            'down' => $element,
            'up'   => false,
        ];
    }

    return [
        'down' => $capture[1],
        'up'   => $capture[2],
    ];
}


// Returns if a date is in a cron. Recursive.
function cron_is_in_cron($elems_cron, $elems_curr_time)
{
    $elem_cron = array_shift($elems_cron);
    $elem_curr_time = array_shift($elems_curr_time);

    // If there is no elements means that is in cron.
    if ($elem_cron === null || $elem_curr_time === null) {
        return true;
    }

    // Go to last element if current is a wild card.
    if ($elem_cron != '*') {
        $elem_s = cron_get_interval($elem_cron);
        // Check if there is no a range
        if (($elem_s['up'] === false) && ($elem_s['down'] != $elem_curr_time)) {
            return false;
        }

        // Check if there is on the range.
        if ($elem_s['up'] !== false) {
            if ($elem_s['down'] < $elem_s['up']) {
                if ($elem_curr_time < $elem_s['down'] || $elem_curr_time > $elem_s['up']) {
                    return false;
                }
            } else {
                if ($elem_curr_time > $elem_s['down'] || $elem_curr_time < $elem_s['up']) {
                    return false;
                }
            }
        }
    }

    return cron_is_in_cron($elems_cron, $elems_curr_time);
}


function cron_valid_date($da)
{
    $st = sprintf(
        '%04d:%02d:%02d %02d:%02d:00',
        $da[4],
        $da[3],
        $da[2],
        $da[1],
        $da[0]
    );
    $time = strtotime($st);
    return $time;
}


// Check if cron is properly constructed.
function cron_check_syntax($cron)
{
    return preg_match(
        '/^[\d|\*].* .*[\d|\*].* .*[\d|\*].* .*[\d|\*].* .*[\d|\*]$/',
        $cron
    );
}


function cron_list_table()
{
    global $config;

    $read_perms = check_acl($config['id_user'], 0, 'RR');
    $write_perms = check_acl($config['id_user'], 0, 'RW');
    $manage_perms = check_acl($config['id_user'], 0, 'RM');
    $manage_pandora = check_acl($config['id_user'], 0, 'PM');

    $url = 'index.php?extension_in_menu=gservers&sec=extensions&sec2=enterprise/extensions/cron&';

    $user_groups = implode(
        ',',
        array_keys(users_get_groups())
    );

    $defined_tasks = db_get_all_rows_filter(
        'tuser_task_scheduled',
        'id_grupo IN ('.$user_groups.')'
    );

    if (!check_acl($config['id_user'], 0, 'PM')) {
        $read_tasks = [];
        foreach ($defined_tasks as $task) {
            $function_name = db_get_value(
                'function_name',
                'tuser_task',
                'id',
                $task['id_user_task']
            );

            if (($function_name != 'cron_task_execute_custom_script')
                && ($function_name != 'cron_task_do_backup')
            ) {
                $read_tasks[] = $task;
            }
        }

        $defined_tasks = $read_tasks;

        if (empty($defined_tasks)) {
            $defined_tasks = false;
        }
    }

    if ($defined_tasks !== false) {
        echo '<h2>'.__('Scheduled jobs').'</h2>';

        $table = new stdClass();
        $table->class = 'databox data';
        $table->width = '100%';
        $table->data = [];
        $table->head = [];
        $table->head[0] = '';
        $table->head[1] = __('User');
        $table->head[2] = __('Task');
        $table->head[3] = __('Scheduled');
        $table->head[4] = __('Next execution');
        $table->head[5] = __('Last run');
        $table->head[6] = __('Group');
        $table->head[7] = __('Actions');
        $table->align[7] = 'left';

        foreach ($defined_tasks as $task) {
            $data = [];

            $function_name = db_get_value(
                'function_name',
                'tuser_task',
                'id',
                $task['id_user_task']
            );

            switch ($function_name) {
                case 'cron_task_generate_report':
                    if ($write_perms || $manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value(
                        'name',
                        'tuser_task',
                        'id',
                        $task['id_user_task']
                    );
                    $args = unserialize($task['args']);
                    $report = reports_get_report($args[0]);

                    // Check ACL in reports_get_report return false.
                    if ($report === false) {
                        continue;
                    }

                    $email = $args[1];
                    $data[2] .= '<br>- '.__('Report').": <a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id=".$args[0]."'>";
                    $data[2] .= $report['name'].'</a>';
                    $data[2] .= '<br>- '.__('Email').": <a href='mailto:".$email."'>";
                    $data[2] .= ui_print_truncate_text($email, 60, false).'</a>';
                break;

                case 'cron_task_generate_report_by_template':
                    if ($write_perms || $manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value(
                        'name',
                        'tuser_task',
                        'id',
                        $task['id_user_task']
                    );

                    $args = unserialize($task['args']);

                    $filter = [];
                    $filter['id_report'] = $args[0];
                    $template = db_get_row_filter(
                        'treport_template',
                        $filter,
                        false
                    );

                    // Check ACL in reports_get_report return false.
                    if ($template === false) {
                        continue;
                    }

                    $agents_id = $args[1];
                    $id_group = $args[2];
                    $report_per_agent = $args[0];
                    $report_name = $args[3];
                    $email = $args[4];
                    $data[2] .= '<br>- '.__('Template').": <a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer";
                    $data[2] .= '&id='.$args[0]."'>".$template['name'].'</a>';
                    $data[2] .= '<br>- '.__('Agents').': '.$agents_id.'</a>';
                    $data[2] .= '<br>- '.__('Report per agent').': '.$report_per_agent.'</a>';
                    $data[2] .= '<br>- '.__('Report name').': '.$report_name.'</a>';
                    $data[2] .= '<br>- '.__('Email').": <a href='mailto:".$email."'>".$email.'</a>';
                break;

                case 'cron_task_execute_custom_script':
                    if ($manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value(
                        'name',
                        'tuser_task',
                        'id',
                        $task['id_user_task']
                    );

                    $args = unserialize($task['args']);
                    $data[2] .= '<br>- '.__('Custom script').': '.$args[0];
                break;

                case 'cron_task_save_report_to_disk':
                    if ($write_perms || $manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value(
                        'name',
                        'tuser_task',
                        'id',
                        $task['id_user_task']
                    );

                    $args = unserialize($task['args']);
                    $report = reports_get_report($args[0]);

                    // Check ACL in reports_get_report return false.
                    if ($report === false) {
                        continue;
                    }

                    $path = $args[1];
                    $data[2] .= '<br>- '.__('Report').": <a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer";
                    $data[2] .= '&id='.$args[0]."'>".$report['name'].'</a>';
                    $data[2] .= '<br>- '.__('Path').': '.$path.'</a>';
                break;

                case 'cron_task_save_xml_report_to_disk':
                    if ($write_perms || $manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value('name', 'tuser_task', 'id', $task['id_user_task']);
                    $args = unserialize($task['args']);
                    $report = reports_get_report($args[0]);

                    // Check ACL in reports_get_report return false.
                    if ($report === false) {
                        continue;
                    }

                    $path = $args[1];
                    $data[2] .= '<br>- '.__('Report').": <a href='index.php?sec=reporting&sec2=operation/reporting/reporting_viewer";
                    $data[2] .= '&id='.$args[0]."'>".$report['name'].'</a>';
                    $data[2] .= '<br>- '.__('Path').': '.$path.'</a>';
                break;

                case 'cron_task_do_backup':
                    if ($manage_pandora) {
                        $data[0]  = '<a href="'.$url;
                        $data[0] .= 'force_run=1&id_user_task='.$task['id'].'">';
                        $data[0] .= html_print_image(
                            'images/target.png',
                            true,
                            ['title' => __('Force run')]
                        );
                        $data[0] .= '</a>';
                    } else {
                        $data[0] = '';
                    }

                    $data[1] = $task['id_usuario'];
                    $data[2] = db_get_value(
                        'name',
                        'tuser_task',
                        'id',
                        $task['id_user_task']
                    );
                    $args = unserialize($task['args']);
                break;

                default:
                    // Ignore.
                break;
            }

            $data[3] = cron_get_scheduled_string($task['scheduled']);
            $data[4] = date('Y/m/d H:i:s', $args['first_execution']);
            $data[5] = empty($task['last_run']) ? __('Never') : date('Y/m/d H:i:s', $task['last_run']);

            $data[6] = ui_print_group_icon($task['id_grupo'], true);

            if ($function_name == 'cron_task_do_backup' || $function_name == 'cron_task_execute_custom_script') {
                if ($manage_pandora) {
                    $data[7]  = '<a href="'.$url;
                    $data[7] .= 'edit_task=1&id='.$task['id'].'">';
                    $data[7] .= html_print_image(
                        'images/config.png',
                        true,
                        ['title' => __('Edit')]
                    );
                    $data[7] .= '</a>';
                }

                if ($manage_pandora) {
                    $data[7] .= '<a href="'.$url;
                    $data[7] .= 'delete_task=1&id_user_task='.$task['id'].'">';
                    $data[7] .= html_print_image(
                        'images/cross.png',
                        true,
                        ['title' => __('Delete')]
                    );
                    $data[7] .= '</a>';
                }
            } else {
                if ($write_perms || $manage_pandora) {
                    $data[7] = '<a href="'.$url;
                    $data[7] .= 'edit_task=1&id='.$task['id'].'">';
                    $data[7] .= html_print_image(
                        'images/config.png',
                        true,
                        ['title' => __('Edit')]
                    );
                    $data[7] .= '</a>';
                }

                if ($manage_perms || $manage_pandora) {
                    $data[7] .= '<a href="'.$url;
                    $data[7] .= 'delete_task=1&id_user_task='.$task['id'].'">';
                    $data[7] .= html_print_image(
                        'images/cross.png',
                        true,
                        ['title' => __('Delete')]
                    );
                    $data[7] .= '</a>';
                }
            }

            array_push($table->data, $data);
        }

        html_print_table($table);
    }
}
