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
        string $icon='images/wizard/tasklist.svg',
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
    public function run()
    {
        // Load styles.
        parent::run();

        $delete = (bool) get_parameter('delete', false);

        if ($delete) {
            return $this->deleteTask();
        }

        return $this->showList();
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
            db_process_sql_delete(
                'trecon_task',
                ['id_rt' => $task]
            );
        }

        return [
            'result' => 0,
            'msg'    => __('Task successfully deleted'),
            'id'     => false,
        ];
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
                include_once $config['homedir'].'/general/firts_task/recon_view.php';
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
                    $recon_tasks = db_get_all_rows_field_filter('trecon_task', 'id_recon_server', $id_server);

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

                    $table->head[5] = __('Template');
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
                            // Network recon task.
                            $data[5] = html_print_image('images/network.png', true, ['title' => __('Network recon task')]).'&nbsp;&nbsp;';
                            $data[5] .= network_profiles_get_name($task['id_network_profile']);
                        } else {
                            // APP recon task.
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
                            // Check if is a H&D, Cloud or Application.
                            $data[8] = '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&page=0&task=%d',
                                    $this->getTargetWiz($task),
                                    $task['id_rt']
                                )
                            ).'">'.html_print_image(
                                'images/wrench_orange.png',
                                true
                            ).'</a>';
                            $data[8] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&delete=1&task='.$task['id_rt']
                            ).'">'.html_print_image(
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
            return 'wiz=app&mode=vmware';

            default:
            return 'wiz=hd&mode=netscan';
        }
    }


}
