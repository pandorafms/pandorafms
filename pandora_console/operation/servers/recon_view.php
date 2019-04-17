<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Load global vars
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access recon task viewer'
    );
    include 'general/noaccess.php';
    return;
}

// Get all recon servers
$servers = db_get_all_rows_sql('SELECT * FROM tserver WHERE server_type = 3');
if ($servers === false) {
    $servers = [];
    ui_print_page_header(__('Recon View'), 'images/op_recon.png', false, '', false);
    ui_print_error_message(__('Discovery Server is disabled'));
    return;
} else {
    $recon_task = db_get_all_rows_sql('SELECT * FROM trecon_task');
    if ($recon_task === false) {
        ui_print_page_header(__('Recon View'), 'images/op_recon.png', false, '', false);
        include_once $config['homedir'].'/general/firts_task/recon_view.php';
        return;
    } else {
        include_once $config['homedir'].'/include/functions_graph.php';
        include_once $config['homedir'].'/include/functions_servers.php';
        include_once $config['homedir'].'/include/functions_network_profiles.php';

        if (check_acl($config['id_user'], 0, 'AW')) {
            $options['manage']['text'] = "<a href='index.php?sec=estado&sec2=godmode/servers/manage_recontask'>".html_print_image('images/setup.png', true, ['title' => __('Manage')]).'</a>';
        }

        $options[]['text'] = "<a href='index.php?sec=estado&sec2=operation/servers/recon_view'>".html_print_image('images/refresh_mc.png', true, ['title' => __('Refresh')]).'</a>';

        ui_print_page_header(__('Recon View'), 'images/op_recon.png', false, '', false, $options);

        $modules_server = 0;
        $total_modules = 0;
        $total_modules_data = 0;

        // --------------------------------
        // FORCE A RECON TASK
        // --------------------------------
        if (check_acl($config['id_user'], 0, 'PM')) {
            if (isset($_GET['force'])) {
                $id = (int) get_parameter_get('force', 0);
                servers_force_recon_task($id);
            }
        }

        foreach ($servers as $serverItem) {
            $id_server = $serverItem['id_server'];
            $server_name = servers_get_name($id_server);
            $recon_tasks = db_get_all_rows_field_filter('trecon_task', 'id_recon_server', $id_server);

            // Show network tasks for Recon Server
            if ($recon_tasks === false) {
                $recon_tasks = [];
            }

            $table = new StdClass();
            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->width = '100%';
            $table->class = 'info_table';
            $table->head = [];
            $table->data = [];
            $table->align = [];
            $table->headstyle = [];
            for ($i = 0; $i < 9; $i++) {
                $table->headstyle[$i] = 'text-align: left;';
            }

            $table->head[0] = __('Force');
            $table->align[0] = 'left';

            $table->head[1] = __('Task name');
            $table->align[1] = 'left';

            $table->head[2] = __('Interval');
            $table->align[2] = 'left';

            $table->head[3] = __('Network');
            $table->align[3] = 'left';

            $table->head[4] = __('Status');
            $table->align[4] = 'left';

            $table->head[5] = __('Template');
            $table->align[5] = 'left';

            $table->head[6] = __('Progress');
            $table->align[6] = 'left';

            $table->head[7] = __('Updated at');
            $table->align[7] = 'left';

            $table->head[8] = __('Edit');
            $table->align[8] = 'left';

            foreach ($recon_tasks as $task) {
                $data = [];

                if ($task['disabled'] == 0) {
                    $data[0] = '<a href="index.php?sec=estado&amp;sec2=operation/servers/recon_view&amp;server_id='.$id_server.'&amp;force='.$task['id_rt'].'">';
                    $data[0] .= html_print_image('images/target.png', true, ['title' => __('Force')]);
                    $data[0] .= '</a>';
                } else {
                    $data[0] = '';
                }

                $data[1] = '<b>'.$task['name'].'</b>';

                $data[2] = human_time_description_raw($task['interval_sweep']);

                if ($task['id_recon_script'] == 0) {
                    $data[3] = $task['subnet'];
                } else {
                    $data[3] = '-';
                }

                if ($task['status'] <= 0) {
                    $data[4] = __('Done');
                } else {
                    $data[4] = __('Pending');
                }

                if ($task['id_recon_script'] == 0) {
                    // Network recon task
                    $data[5] = html_print_image('images/network.png', true, ['title' => __('Network recon task')]).'&nbsp;&nbsp;';
                    $data[5] .= network_profiles_get_name($task['id_network_profile']);
                } else {
                    // APP recon task
                    $data[5] = html_print_image('images/plugin.png', true).'&nbsp;&nbsp;';
                    $data[5] .= db_get_sql(sprintf('SELECT name FROM trecon_script WHERE id_recon_script = %d', $task['id_recon_script']));
                }

                if ($task['status'] <= 0 || $task['status'] > 100) {
                    $data[6] = '-';
                } else {
                    $data[6] = progress_bar($task['status'], 100, 20, __('Progress').':'.$task['status'].'%', 1);
                }

                $data[7] = ui_print_timestamp($task['utimestamp'], true);

                if (check_acl($config['id_user'], $task['id_group'], 'PM')) {
                    $table->cellclass[][8] = 'action_buttons';
                    $data[8] = '<a href="index.php?sec=gservers&amp;sec2=godmode/servers/manage_recontask_form&amp;update='.$task['id_rt'].'">'.html_print_image('images/wrench_orange.png', true).'</a>';
                } else {
                    $data[8] = '';
                }

                array_push($table->data, $data);
            }

            if (empty($table->data)) {
                echo '<div class="nf">'.__('Server').' '.$server_name.' '.__('has no recon tasks assigned').'</div>';
            } else {
                html_print_table($table);
            }

            unset($table);
        }
    }
}
