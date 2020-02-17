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
enterprise_include_once('include/functions_tasklist.php');
enterprise_include_once('include/functions_cron.php');

ui_require_css_file('task_list');

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
     * @param string  $message Redirected input.
     * @param boolean $status  Redirected input.
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

        // Header.
        ui_print_page_header(
            __('Task list'),
            '',
            false,
            '',
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

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
            enterprise_hook('tasklist_checkrunning');

            $ret = $this->showListConsoleTask();
        } else {
            $ret = false;
        }

        $ret2 = $this->showList();

        if ($ret === false && $ret2 === false) {
            include_once $config['homedir'].'/general/first_task/recon_view.php';
        } else {
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

        if (! check_acl($config['id_user'], 0, 'AW')) {
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

        if (!$this->aclMulticheck('RR|RW|RM|PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access recon task viewer'
            );
            include 'general/noaccess.php';
            return;
        }

        $id_console_task = (int) get_parameter('id_console_task');

        if ($id_console_task !== null) {
            enterprise_hook('cron_task_run', [$id_console_task, true]);
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

        if (! check_acl($config['id_user'], 0, 'RM')) {
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

        if (!$this->aclMulticheck('AR|AW|AM')) {
            // Tasklist are allowed only of agent managers.
            return '';
        }

        // Get all discovery servers.
        $servers = db_get_all_rows_sql('SELECT * FROM tserver WHERE server_type = 3');
        if ($servers === false) {
            $servers = [];
            ui_print_error_message(__('Discovery Server is disabled'));
            $check = db_get_all_rows_sql('SELECT * FROM trecon_task');
            return (bool) $check;
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
            if (check_acl($config['id_user'], 0, 'AW')) {
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

            $recon_tasks = db_get_all_rows_sql('SELECT * FROM trecon_task');
            $user_groups = implode(',', array_keys(users_get_groups()));

            // Show network tasks for Recon Server.
            if ($recon_tasks === false) {
                $recon_tasks = [];
            }

            $url_ajax = $config['homeurl'].'ajax.php';

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

            // Status.
            $table->headstyle[5] .= 'min-width: 100px; width: 100px;';
            // Task type.
            $table->headstyle[6] .= 'min-width: 200px; width: 150px;';
            // Progress.
            $table->headstyle[7] .= 'min-width: 150px; width: 150px;';
            // Updated at.
            $table->headstyle[8] .= 'min-width: 150px; width: 150px;';
            // Operations.
            $table->headstyle[9] .= 'min-width: 150px; width: 150px;';

            if (check_acl($config['id_user'], 0, 'AW')) {
                $table->head[0] = __('Force');
                $table->align[0] = 'left';
            }

            $table->head[1] = __('Task name');
            $table->align[1] = 'left';

            $table->head[2] = __('Server name');
            $table->align[2] = 'left';

            $table->head[3] = __('Interval');
            $table->align[3] = 'left';

            $table->head[4] = __('Network');
            $table->align[4] = 'left';

            $table->head[5] = __('Status');
            $table->align[5] = 'left';

            $table->head[6] = __('Task type');
            $table->align[6] = 'left';

            $table->head[7] = __('Progress');
            $table->align[7] = 'left';

            $table->head[8] = __('Updated at');
            $table->align[8] = 'left';

            $table->head[9] = __('Operations');
            $table->align[9] = 'left';

            foreach ($recon_tasks as $task) {
                $no_operations = false;
                $data = [];
                $server_name = servers_get_name($task['id_recon_server']);

                // By default.
                $subnet = $task['subnet'];

                // Exceptions: IPAM.
                $ipam = false;
                if ($task['id_recon_script'] != null) {
                    $recon_script_data = db_get_row(
                        'trecon_script',
                        'id_recon_script',
                        $task['id_recon_script']
                    );
                    if ($recon_script_data !== false) {
                        $recon_script_name = $recon_script_data['name'];
                        if (io_safe_output($recon_script_name) == 'IPAM Recon'
                            && enterprise_installed()
                        ) {
                            $subnet_obj = json_decode($task['macros'], true);
                            $subnet = $subnet_obj['1']['value'];
                            $tipam_task_id = db_get_value(
                                'id',
                                'tipam_network',
                                'id_recon_task',
                                $task['id_rt']
                            );
                            $ipam = true;
                        }
                    }
                } else {
                    $recon_script_data = false;
                    $recon_script_name = false;
                }

                if ($task['disabled'] == 0 && $server_name !== '') {
                    if (check_acl($config['id_user'], 0, 'AW')) {
                        $data[0] = '<a href="'.ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&server_id='.$id_server.'&force='.$task['id_rt']
                        ).'">';
                        $data[0] .= html_print_image('images/target.png', true, ['title' => __('Force')]);
                        $data[0] .= '</a>';
                    }
                } else if ($task['disabled'] == 2) {
                    $data[0] = ui_print_help_tip(
                        __('This task has not been completely defined, please edit it'),
                        true
                    );
                } else {
                    $data[0] = '';
                }

                // Name task.
                $data[1] = '';
                if ($task['disabled'] != 2) {
                    $data[1] .= '<a href="#" onclick="progress_task_list('.$task['id_rt'].',\''.$task['name'].'\')">';
                }

                $data[1] .= '<b>'.$task['name'].'</b>';
                if ($task['disabled'] != 2) {
                    $data[1] .= '</a>';
                }

                $data[2] = $server_name;

                if ($task['interval_sweep'] > 0) {
                    $data[3] = human_time_description_raw(
                        $task['interval_sweep']
                    );
                } else {
                    $data[3] = __('Manual');
                }

                if ($task['id_recon_script'] == 0 || $ipam === true) {
                    $data[4] = $subnet;
                } else {
                    $data[4] = '-';
                }

                if ($task['status'] <= 0) {
                    $data[5] = __('Done');
                } else {
                    $data[5] = __('Pending');
                }

                switch ($task['type']) {
                    case DISCOVERY_CLOUD_AZURE_COMPUTE:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/plugin.png',
                            true,
                            ['title' => __('Discovery Cloud Azure Compute')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Cloud.Azure.Compute');
                    break;

                    case DISCOVERY_CLOUD_AWS_EC2:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/plugin.png',
                            true,
                            ['title' => __('Discovery Cloud AWS EC2')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Cloud.AWS.EC2');
                    break;

                    case DISCOVERY_CLOUD_AWS_RDS:
                        // Discovery Cloud RDS.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Cloud RDS')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.Cloud.Aws.RDS');
                    break;

                    case DISCOVERY_APP_MYSQL:
                        // Discovery Applications MySQL.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Applications MySQL')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.App.MySQL');
                    break;

                    case DISCOVERY_APP_ORACLE:
                        // Discovery Applications Oracle.
                        $data[6] = html_print_image(
                            'images/network.png',
                            true,
                            ['title' => __('Discovery Applications Oracle')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.App.Oracle');
                    break;

                    case DISCOVERY_DEPLOY_AGENTS:
                        // Internal deployment task.
                        $no_operations = true;
                        $data[6] = html_print_image(
                            'images/deploy.png',
                            true,
                            ['title' => __('Agent deployment')]
                        ).'&nbsp;&nbsp;';
                        $data[6] .= __('Discovery.Agent.Deployment');
                    break;

                    case DISCOVERY_HOSTDEVICES:
                    default:
                        if ($task['id_recon_script'] == 0) {
                            // Discovery NetScan.
                            $data[6] = html_print_image(
                                'images/network.png',
                                true,
                                ['title' => __('Discovery NetScan')]
                            ).'&nbsp;&nbsp;';
                            $str = network_profiles_get_name(
                                $task['id_network_profile']
                            );
                            if (!empty($str)) {
                                $data[6] .= $str;
                            } else {
                                $data[6] .= __('Discovery.NetScan');
                            }
                        } else {
                            // APP or external script recon task.
                            $data[6] = html_print_image(
                                'images/plugin.png',
                                true
                            ).'&nbsp;&nbsp;';
                            $data[6] .= $recon_script_name;
                        }
                    break;
                }

                if ($task['status'] <= 0 || $task['status'] > 100) {
                    $data[7] = '-';
                } else {
                    $data[7] = ui_progress($task['status'], '100%', 1.5);
                }

                if ($task['utimestamp'] > 0) {
                    $data[8] = ui_print_timestamp(
                        $task['utimestamp'],
                        true
                    );
                } else {
                    $data[8] = __('Not executed yet');
                }

                if (!$no_operations) {
                    if ($task['disabled'] != 2) {
                        $data[9] = '<a href="#" onclick="progress_task_list('.$task['id_rt'].',\''.$task['name'].'\')">';
                        $data[9] .= html_print_image(
                            'images/eye.png',
                            true
                        );
                        $data[9] .= '</a>';
                    }

                    if ($task['disabled'] != 2 && $task['utimestamp'] > 0
                        && $task['type'] != DISCOVERY_APP_MYSQL
                        && $task['type'] != DISCOVERY_APP_ORACLE
                        && $task['type'] != DISCOVERY_CLOUD_AWS_RDS
                    ) {
                        if (check_acl($config['id_user'], 0, 'MR')) {
                            $data[9] .= '<a href="#" onclick="show_map('.$task['id_rt'].',\''.$task['name'].'\')">';
                            $data[9] .= html_print_image(
                                'images/dynamic_network_icon.png',
                                true
                            );
                            $data[9] .= '</a>';
                        }
                    }

                    if (check_acl(
                        $config['id_user'],
                        $task['id_group'],
                        'AW'
                    )
                    ) {
                        if ($ipam === true) {
                            $data[9] .= '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=godmode/extensions&sec2=enterprise/extensions/ipam&action=edit&id=%d',
                                    $tipam_task_id
                                )
                            ).'">'.html_print_image(
                                'images/config.png',
                                true
                            ).'</a>';
                            $data[9] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=godmode/extensions&sec2=enterprise/extensions/ipam&action=delete&id='.$tipam_task_id
                            ).'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image(
                                'images/cross.png',
                                true
                            ).'</a>';
                        } else {
                            // Check if is a H&D, Cloud or Application or IPAM.
                            $data[9] .= '<a href="'.ui_get_full_url(
                                sprintf(
                                    'index.php?sec=gservers&sec2=godmode/servers/discovery&%s&task=%d',
                                    $this->getTargetWiz($task, $recon_script_data),
                                    $task['id_rt']
                                )
                            ).'">'.html_print_image(
                                'images/config.png',
                                true
                            ).'</a>';
                            $data[9] .= '<a href="'.ui_get_full_url(
                                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist&delete=1&task='.$task['id_rt']
                            ).'" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">'.html_print_image(
                                'images/cross.png',
                                true
                            ).'</a>';
                        }
                    } else {
                        $data[9] = '';
                    }
                } else {
                    $data[9] = '-';
                }

                $table->cellclass[][9] = 'action_buttons';

                // Div neccesary for modal progress task.
                echo '<div id="progress_task_'.$task['id_rt'].'" style="display:none"></div>';

                array_push($table->data, $data);
            }

            if (empty($table->data)) {
                $content = '<div class="nf">'.__('Server').' '.$server_name.' '.__('has no discovery tasks assigned').'</div>';
                $return = false;
            } else {
                $content = html_print_table($table, true);
                $return = true;
            }

            ui_toggle($content, __('Server Tasks'), '', '', false);

            // Div neccesary for modal map task.
            echo '<div id="map_task" style="display:none"></div>';

            unset($table);

            ui_require_javascript_file('pandora_taskList');

            return $return;
        }

        return true;
    }


    /**
     * Show complete list of running tasks.
     *
     * @return boolean Success or not.
     */
    public function showListConsoleTask()
    {
        return enterprise_hook('tasklist_showListConsoleTask', [$this]);
    }


    /**
     * Return target url sub-string to edit target task.
     *
     * @param array $task   With all data.
     * @param array $script With all script data or false if undefined.
     *
     * @return string
     */
    public function getTargetWiz($task, $script=false)
    {
        if ($script !== false) {
            switch ($script['type']) {
                case DISCOVERY_SCRIPT_APP_VMWARE:
                return 'wiz=app&mode=vmware&page=0';

                case DISCOVERY_SCRIPT_IPAM_RECON:
                return '';

                case DISCOVERY_SCRIPT_IPMI_RECON:
                default:
                return 'wiz=hd&mode=customnetscan';

                case DISCOVERY_SCRIPT_CLOUD_AWS:
                    switch ($task['type']) {
                        case DISCOVERY_CLOUD_AWS_EC2:
                        return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&page=1';

                        case DISCOVERY_CLOUD_AZURE_COMPUTE:
                        return 'wiz=cloud&mode=azure&ki='.$task['auth_strings'].'&sub=compute&page=0';

                        default:
                        return 'wiz=cloud';
                    }
            }
        }

        switch ($task['type']) {
            case DISCOVERY_APP_MYSQL:
            return 'wiz=app&mode=mysql&page=0';

            case DISCOVERY_APP_ORACLE:
            return 'wiz=app&mode=oracle&page=0';

            case DISCOVERY_CLOUD_AWS:
            case DISCOVERY_CLOUD_AWS_EC2:
            return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&page=1';

            case DISCOVERY_CLOUD_AWS_RDS:
            return 'wiz=cloud&mode=amazonws&ki='.$task['auth_strings'].'&sub=rds&page=0';

            case DISCOVERY_APP_SAP:
            return 'wiz=app&mode=SAP&page=0';

            default:
                if ($task['description'] == 'console_task') {
                    return 'wiz=ctask';
                } else {
                    return 'wiz=hd&mode=netscan';
                }
            break;
        }
    }


}
