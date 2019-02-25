<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
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

require_once __DIR__.'/Wizard.main.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_reports.php';
require_once $config['homedir'].'/include/functions_cron.php';

/**
 * Defined as wizard to guide user to explore running tasks.
 */
class DiscoveryTaskList extends Wizard
{


    /**
     * Constructor.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Custom default mesage.
     * @param string  $icon  Custom icon.
     * @param string  $label Custom label.
     *
     * @return class HostDevices
     */
    public function __construct(
        int $page=0,
        string $msg='Default message. Not set.',
        string $icon='images/wizard/tasklist.png',
        string $label='Task list'
    ) {
        $this->setBreadcrum([]);

        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = __($label);
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist'
        );

        return $this;
    }


    /**
     * Implements run method.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run($message='', $status=null)
    {
        global $config;
        // Load styles.
        parent::run();

        $this->prepareBreadcrum(
            [
                [
                    'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                    'label' => 'Discovery',
                ],
            ]
        );

        $this->printHeader();

        // Show redirected messages from discovery.php.
        if ($status === 0) {
            ui_print_success_message($message);
        } else if ($status !== null) {
            ui_print_error_message($message);
        }

        $force_run = (bool) get_parameter('force_run');
        if ($force_run === true) {
            return $this->forceConsoleTask();
        }

        $delete_console_task = (bool) get_parameter('delete_console_task');
        if ($delete_console_task === true) {
            return $this->deleteConsoleTask();
        }

        $delete = (bool) get_parameter('delete', false);
        if ($delete === true) {
            return $this->deleteTask();
        }

        if (enterprise_installed()) {
            // This check only applies to enterprise users.
            // Check if DiscoveryCronTasks is running. Warn user if not.
            if ($config['cron_last_run'] == 0
                || (get_system_time() - $config['cron_last_run']) > 3600
            ) {
                $message_conf_cron = __('DiscoveryConsoleTasks is not running properly');
                if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') {
                    $message_conf_cron .= __('Discovery relies on a proper setup of cron, the time-based scheduling service');
                    $message_conf_cron .= '. '.__('Please, add the following line to your crontab file:');
                    $message_conf_cron .= '<b><pre style="color: #333;">* * * * * &lt;user&gt; wget -q -O - --no-check-certificate ';
                    $message_conf_cron .= str_replace(
                        ENTERPRISE_DIR.'/meta/',
                        '',
                        ui_get_full_url(false)
                    );
                    $message_conf_cron .= ENTERPRISE_DIR.'/'.EXTENSIONS_DIR;
                    $message_conf_cron .= '/cron/cron.php &gt;&gt; ';
                    $message_conf_cron .= $config['homedir'].'/pandora_console.log</pre></b>';
                }

                if (isset($config['cron_last_run']) === true
                    && $config['cron_last_run'] > 0
                ) {
                    $message_conf_cron .= '<p style="color: #333;">'.__('Last execution').': ';
                    $message_conf_cron .= date('Y/m/d H:i:s', $config['cron_last_run']).'</p>';
                    $message_conf_cron .= '<p style="color: #333;">';
                    $message_conf_cron .= __('Please check process is no locked.').'</p>';
                }

                ui_print_warning_message($message_conf_cron, '', false);
            }
        }

        $ret = $this->showListConsoleTask();
        $ret2 = $this->showList();

        if ($ret === false && $ret2 === false) {
            include_once $config['homedir'].'/general/firts_task/recon_view.php';
        }

        return $ret;
    }


    /**
     * Implements load method.
     *
     * @return mixed Skeleton for button.
     */
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,

        ];

    }


    /**
     * Delete a recon task.
     *
     * @return void
     */
    public function deleteTask()
    {
        global $config;

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $task = get_parameter('task', null);

        if ($task !== null) {
            $result = db_process_sql_delete(
                'trecon_task',
                ['id_rt' => $task]
            );

            if ($result == 1) {
                return [
                    'result' => 0,
                    'msg'    => __('Task successfully deleted'),
                    'id'     => false,
                ];
            }

            // Trick to avoid double execution.
            header('Location: '.$this->url);
        }

    }


    /**
     * Force console task.
     *
     * @return void
     */
    public function forceConsoleTask()
    {
        global $config;

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $id_console_task = (int) get_parameter('id_console_task');

        if ($id_console_task !== null) {
            cron_task_run($id_console_task, true);
            // Trick to avoid double execution.
            header('Location: '.$this->url);
        }

    }


    /**
     * Delete a Console task.
     *
     * @return void
     */
    public function deleteConsoleTask()
    {
        global $config;

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $id_console_task = (int) get_parameter('id_console_task');

        if ($id_console_task !== null) {
            $result = db_process_sql_delete(
                'tuser_task_scheduled',
                ['id' => $id_console_task]
            );

            if ($result == 1) {
                return [
                    'result' => 0,
                    'msg'    => __('Console Task successfully deleted'),
                    'id'     => false,
                ];
            }

            // Trick to avoid double execution.
            header('Location: '.$this->url);
        }

    }


    /**
     * Show complete list of running tasks.
     *
     * @return boolean Success or not.
     */
    public function showList()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return false;
        }

        // Get all recon servers.
        $servers = db_get_all_rows_sql('SELECT * FROM tserver WHERE server_type = 3');
        if ($servers === false) {
            $servers = [];
            ui_print_error_message(__('Discovery Server is disabled'));
            return false;
        } else {
            $recon_task = db_get_all_rows_sql('SELECT * FROM trecon_task');
            if ($recon_task === false) {
                return false;
            } else {
                include_once $config['homedir'].'/include/functions_graph.php';
                include_once $config['homedir'].'/include/functions_servers.php';
                include_once $config['homedir'].'/include/functions_network_profiles.php';

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
                        header(
                            'Location: '.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist'
                            )
                        );
                    }
                }

                foreach ($servers as $serverItem) {
                    $id_server = $serverItem['id_server'];
                    $server_name = servers_get_name($id_server);
                    $recon_tasks = db_get_all_rows_field_filter(
                        'trecon_task',
                        'id_recon_server',
                        $id_server
                    );

                    $user_groups = implode(',', array_keys(users_get_groups()));
                    $defined_tasks = db_get_all_rows_filter(
                        'tuser_task_scheduled',
                        'id_grupo IN ('.$user_groups.')'
                    );

                    if (isset($tasks_console) === true
                        && is_array($tasks_console) === true
                    ) {
                        foreach ($tasks_console as $key => $value) {
                            $value['parameters'] = unserialize(
                                $value['parameters']
                            );

                            $value['type'] = 'Cron';
                            array_push($recon_tasks, $value);
                        }
                    }

                    // Show network tasks for Recon Server.
                    if ($recon_tasks === false) {
                        $recon_tasks = [];
                    }

                    $table = new StdClass();
                    $table->cellpadding = 4;
                    $table->cellspacing = 4;
                    $table->width = '100%';
                    $table->class = 'databox data';
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

                    $table->head[5] = __('Task type');
                    $table->align[5] = 'left';

                    $table->head[6] = __('Progress');
                    $table->align[6] = 'left';

                    $table->head[7] = __('Updated at');
                    $table->align[7] = 'left';

                    $table->head[8] = __('Operations');
                    $table->align[8] = 'left';

                    foreach ($recon_tasks as $task) {
                        $data = [];

                        if ($task['disabled'] == 0) {
                            $data[0] = '<a href="'.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&server_id='.$id_server.'&force='.$task['id_rt']
                            ).'">';
                            $data[0] .= html_print_image('images/target.png', true, ['title' => __('Force')]);
                            $data[0] .= '</a>';
                        } else if ($task['disabled'] == 2) {
                            $data[0] = ui_print_help_tip(
                                __('This task has not been completely defined, please edit it'),
                                true
                            );
                        } else {
                            $data[0] = '';
                        }

                        $data[1] = '<b>'.$task['name'].'</b>';

                        if ($task['interval_sweep'] > 0) {
                            $data[2] = human_time_description_raw(
                                $task['interval_sweep']
                            );
                        } else {
                            $data[2] = __('Manual');
                        }

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
                            // Discovery NetScan.
                            $data[5] = html_print_image(
                                'images/network.png',
                                true,
                                ['title' => __('Discovery NetScan')]
                            ).'&nbsp;&nbsp;';
                            $data[5] .= network_profiles_get_name(
                                $task['id_network_profile']
                            );
                        } else {
                            // APP recon task.
                            $data[5] = html_print_image(
                                'images/plugin.png',
                                true
                            ).'&nbsp;&nbsp;';
                            $data[5] .= db_get_sql(
                                sprintf(
                                    'SELECT name FROM trecon_script WHERE id_recon_script = %d',
                                    $task['id_recon_script']
                                )
                            );
                        }

                        if ($task['status'] <= 0 || $task['status'] > 100) {
                            $data[6] = '-';
                        } else {
                            $data[6] = progress_bar(
                                $task['status'],
                                100,
                                20,
                                __('Progress').':'.$task['status'].'%',
                                1
                            );
                        }

                        if ($task['utimestamp'] > 0) {
                            $data[7] = ui_print_timestamp(
                                $task['utimestamp'],
                                true
                            );
                        } else {
                            $data[7] = __('Not executed yet');
                        }

                        if (check_acl(
                            $config['id_user'],
                            $task['id_group'],
                            'PM'
                        )
                        ) {
                            // Check if is a H&D, Cloud or Application.
                            $data[8] = '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&task=%d',
                                    $this->getTargetWiz($task),
                                    $task['id_rt']
                                )
                            ).'">'.html_print_image(
                                'images/config.png',
                                true
                            ).'</a>';
                            $data[8] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&delete=1&task='.$task['id_rt']
                            ).'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image(
                                'images/cross.png',
                                true
                            ).'</a>';
                        } else {
                            $data[8] = '';
                        }

                        array_push($table->data, $data);
                    }

                    if (empty($table->data)) {
                        echo '<div class="nf">'.__('Server').' '.$server_name.' '.__('has no recon tasks assigned').'</div>';
                    } else {
                        echo '<h2>'.__('Server task').'</h2>';
                        html_print_table($table);
                    }

                    unset($table);
                }
            }
        }

        $form = [
            'form'   => [
                'method' => 'POST',
                'action' => ui_get_full_url(
                    'index.php?sec=gservers&sec2=godmode/servers/discovery'
                ),
            ],
            'inputs' => [
                [
                    'arguments' => [
                        'name'       => 'submit',
                        'label'      => __('Go back'),
                        'type'       => 'submit',
                        'attributes' => 'class="sub cancel"',
                        'return'     => true,
                    ],
                ],
            ],
        ];

        $this->printForm($form);

        return true;
    }


    /**
     * Show complete list of running tasks.
     *
     * @return boolean Success or not.
     */
    public function showListConsoleTask()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return false;
        }

        $read_perms = check_acl(
            $config['id_user'],
            0,
            'RR'
        );
        $write_perms = check_acl(
            $config['id_user'],
            0,
            'RW'
        );
        $manage_perms = check_acl(
            $config['id_user'],
            0,
            'RM'
        );
        $manage_pandora = check_acl(
            $config['id_user'],
            0,
            'PM'
        );

        $url = 'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&';

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
            echo '<h2>'.__('Console task').'</h2>';

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
            $table->head[7] = __('Operations');
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
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                        $data[2] .= ui_print_truncate_text(
                            $email,
                            60,
                            false
                        ).'</a>';
                    break;

                    case 'cron_task_generate_report_by_template':
                        if ($write_perms || $manage_pandora) {
                            $data[0]  = '<a href="'.$url;
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                            $data[0] .= 'force_run=1&id_console_task='.$task['id'].'">';
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
                        $data[7] = '<a href="'.ui_get_full_url(
                            sprintf(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&task=%d',
                                $this->getTargetWiz(['description' => 'console_task']),
                                $task['id']
                            )
                        ).'">';
                        $data[7] .= html_print_image(
                            'images/config.png',
                            true,
                            ['title' => __('Edit')]
                        ).'</a>';
                    }

                    if ($manage_pandora) {
                        $data[7] .= '<a href="'.$url;
                        $data[7] .= 'delete_console_task=1&id_console_task='.$task['id'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
                        $data[7] .= html_print_image(
                            'images/cross.png',
                            true,
                            ['title' => __('Delete')]
                        );
                        $data[7] .= '</a>';
                    }
                } else {
                    if ($write_perms || $manage_pandora) {
                        $data[7] = '<a href="'.ui_get_full_url(
                            sprintf(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&task=%d',
                                $this->getTargetWiz(['description' => 'console_task']),
                                $task['id']
                            )
                        ).'">';
                        $data[7] .= html_print_image(
                            'images/config.png',
                            true,
                            ['title' => __('Edit')]
                        ).'</a>';
                    }

                    if ($manage_perms || $manage_pandora) {
                        $data[7] .= '<a href="'.$url;
                        $data[7] .= 'delete_console_task=1&id_console_task='.$task['id'].'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">';
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
        } else {
            return false;
        }

        return true;
    }


    /**
     * Return target url sub-string to edit target task.
     *
     * @param array $task With all data.
     *
     * @return string
     */
    public function getTargetWiz($task)
    {
        // TODO: Do not use description. Use recon_script ID instead.
        switch ($task['description']) {
            case 'Discovery.Application.VMware':
            return 'wiz=app&mode=vmware&page=0';

            case CLOUDWIZARD_AWS_DESCRIPTION:
            return 'wiz=cloud&mode=amazonws&page=1';

            case 'console_task':
            return 'wiz=ctask';

            default:
            return 'wiz=hd&mode=netscan';
        }
    }


}
