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
enterprise_include('include/class/CSVImportAgents.class.php');

/**
 * Undocumented class
 */
class HostDevices extends Wizard
{
    // CSV constants.
    const HDW_CSV_NOT_DATA = 0;
    const HDW_CSV_DUPLICATED = 0;
    const HDW_CSV_GROUP_EXISTS = 0;

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $values = [];

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $result;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $msg;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $icon;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $label;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $url;

    /**
     * Stores all needed parameters to create a recon task.
     *
     * @var array
     */
    public $task;


    /**
     * Undocumented function.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Mensajito.
     * @param string  $icon  Mensajito.
     * @param string  $label Mensajito.
     *
     * @return class HostDevices
     */
    public function __construct(
        int $page=0,
        string $msg='hola',
        string $icon='hostDevices.png',
        string $label='Host & Devices'
    ) {
        $this->setBreadcrum([]);

        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = $label;
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd'
        );

        return $this;
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();

        $mode = get_parameter('mode', null);

        if ($mode === null) {
            $this->setBreadcrum(['<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>']);
            $this->printHeader();

            echo '<a href="'.$this->url.'&mode=importcsv" alt="importcsv">Importar csv</a>';
            echo '<a href="'.$this->url.'&mode=netscan" alt="netscan">Escanear red</a>';

            return;
        }

        if (enterprise_installed()) {
            if ($mode == 'importcsv') {
                $this->setBreadcrum(
                    [
                        '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                        '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=importcsv">Import CSV</a>',
                    ]
                );
                $this->printHeader();
                $csv_importer = new CSVImportAgents($this->page, $this->breadcrum);
                return $csv_importer->runCSV();
            }
        }

        if ($mode == 'netscan') {
            $this->setBreadcrum(
                [
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan">Net scan</a>',
                ]
            );
            $this->printHeader();
            return $this->runNetScan();
        }

        return null;
    }


    /**
     * Checks if environment is ready,
     * returns array
     *   icon: icon to be displayed
     *   label: label to be displayed
     *
     * @return array With data.
     **/
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,
        ];
    }


    // Extra methods.


    /**
     * Retrieves and validates information given by user in NetScan wizard.
     *
     * @return boolean Data OK or not.
     */
    public function parseNetScan()
    {
        if ($this->page == 0) {
            // Error. Must not be here.
            return true;
        }

        // Validate response from page 0. No, not a bug, we're always 1 page
        // from 'validation' page.
        if ($this->page == 1) {
            $taskname = get_parameter('taskname', '');
            $comment = get_parameter('comment', '');
            $server_id = get_parameter('id_recon_server', '');
            $network = get_parameter('name', '');
            $id_group = get_parameter('id_group', '');

            if ($taskname == '') {
                $this->msg = __('You must provide a task name.');
                return false;
            }

            if ($server_id == '') {
                $this->msg = __('You must select a Discovery Server.');
                return false;
            }

            if ($network == '') {
                // XXX: Could be improved validating provided network.
                $this->msg = __('You must provide a valid network.');
                return false;
            }

            if ($id_group == '') {
                $this->msg = __('You must select a valid group.');
                return false;
            }

            // Assign fields.
            $this->task['name'] = $taskname;
            $this->task['description'] = $comment;
            $this->task['subnet'] = $network;
            $this->task['id_recon_server'] = $server_id;
            // Disabled 2 Implies wizard non finished.
            $this->task['disabled'] = 2;

            $this->task['id_rt'] = 5;

            if (!isset($this->task['id_rt'])) {
                // Create.
                $this->task['id_rt'] = db_process_sql_insert(
                    'trecon_task',
                    $this->task
                );
            } else {
                // Update.
                db_process_sql_update(
                    'trecon_task',
                    $this->task,
                    ['id_rt' => $this->task['id_rt']]
                );
            }

            return true;
        }

        // Validate response from page 1.
        if ($this->page == 2) {
            $id_rt = get_parameter('task', -1);

            $this->task = db_get_row(
                'trecon_task',
                'id_rt',
                $id_rt
            );

            hd($this->task);

            return false;
        }

        if ($this->page == 3) {
            // Interval and schedules.
            // By default manual if not defined.
            $interval = get_parameter('interval', 0);

            $this->task['interval_sweep'] = $interval;
            return false;
        }

        return false;

    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function runNetScan()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Agent Management'
            );
            include 'general/noaccess.php';
            return;
        }

        $user_groups = users_get_groups(false, 'AW', true, false, null, 'id_grupo');
        $user_groups = array_keys($user_groups);

        if ($this->parseNetScan() === false) {
            // Error.
            ui_print_error_message(
                $this->msg
            );

            $form = [
                'form'   => [
                    'method' => 'POST',
                    'action' => '#',
                ],
                'inputs' => [
                    [
                        'arguments' => [
                            'type'  => 'hidden',
                            'name'  => 'page',
                            'value' => ($this->page - 1),
                        ],
                    ],
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
            return null;
        }

        if (isset($this->page)
            && $this->page != 0
            && isset($this->task['id_rt']) === false
        ) {
            // Error.
            ui_print_error_message(
                __('Internal error, please re-run this wizard.')
            );

            $form = [
                'form'   => [
                    'method' => 'POST',
                    'action' => '#',
                ],
                'inputs' => [
                    [
                        'arguments' => [
                            'type'  => 'hidden',
                            'name'  => 'page',
                            'value' => 0,
                        ],
                    ],
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
            return null;
        }

        // -------------------------------.
        // Page 0. wizard starts HERE.
        // -------------------------------.
        if (!isset($this->page) || $this->page == 0) {
            if (isset($this->page) === false
                || $this->page == 0
            ) {
                $form = [];

                // Input task name.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Task name').'</b>',
                    'arguments' => [
                        'name'  => 'taskname',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                // Input task name.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Comment').'</b>',
                    'arguments' => [
                        'name'  => 'comment',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                // Input Discovery Server.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Discovery server').'</b>'.ui_print_help_tip(
                        __('You must select a Discovery Server to run the Task, otherwise the Recon Task will never run'),
                        true
                    ),
                    'arguments' => [
                        'type'     => 'select_from_sql',
                        'sql'      => sprintf(
                            'SELECT id_server, name
                                    FROM tserver
                                    WHERE server_type = %d
                                    ORDER BY name',
                            SERVER_TYPE_DISCOVERY
                        ),
                        'name'     => 'id_recon_server',
                        'selected' => 0,
                        'return'   => true,
                    ],
                ];

                // Input Network.
                $form['inputs'][] = [

                    'label'     => '<b>'.__('Network').'</b>'.ui_print_help_tip(
                        __('You can specify several networks, separated by commas, for example: 192.168.50.0/24,192.168.60.0/24'),
                        true
                    ),
                    'arguments' => [
                        'name'  => 'name',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                // Input Group.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Group').'</b>',
                    'arguments' => [
                        'name'      => 'id_group',
                        'privilege' => 'PM',
                        'type'      => 'select_groups',
                        'return'    => true,
                    ],
                ];

                // Hidden, page.
                $form['inputs'][] = [
                    'arguments' => [
                        'name'   => 'page',
                        'value'  => ($this->page + 1),
                        'type'   => 'hidden',
                        'return' => true,
                    ],
                ];

                // Submit button.
                $form['inputs'][] = [
                    'arguments' => [
                        'name'       => 'submit',
                        'label'      => __('Next'),
                        'type'       => 'submit',
                        'attributes' => 'class="sub next"',
                        'return'     => true,
                    ],
                ];

                $form['form'] = [
                    'method' => 'POST',
                    'action' => '#',
                ];

                // XXX: Could be improved validating inputs before continue (JS)
                // Print NetScan page 0.
                $this->printForm($form);
            }
        }

        if ($this->page == 1) {
            // Page 1.
            $form = [];
            // Hidden, id_rt.
            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'task',
                    'value'  => $this->task['id_rt'],
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ];

            // Hidden, page.
            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'page',
                    'value'  => ($this->page + 1),
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ];

            $form['inputs'][] = [
                'extra' => '<p>Please, configure task <b>'.io_safe_output($this->task['name']).'</b></p>',
            ];

            // Input: Module template.
            $form['inputs'][] = [
                'label'     => __('Module template'),
                'arguments' => [
                    'name'   => 'id_network_profile',
                    'type'   => 'select_from_sql',
                    'sql'    => 'SELECT id_np, name
                            FROM tnetwork_profile
                            ORDER BY name',
                    'return' => true,

                ],
            ];

            // Feature configuration.
            // Input: Module template.
            $form['inputs'][] = [
                'label'     => __('SNMP enabled'),
                'arguments' => [
                    'name'    => 'snmp_enabled',
                    'type'    => 'switch',
                    'return'  => true,
                    'onclick' => "\$('#snmp_extra').toggle();",

                ],
            ];

            // SNMP CONFIGURATION.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_extra',
                'block_content' => [
                    [
                        'label'     => __('SNMP version'),
                        'arguments' => [
                            'name'   => 'auth_strings',
                            'fields' => [
                                '1'  => 'v. 1',
                                '2c' => 'v. 2c',
                                '3'  => 'v. 3',
                            ],
                            'type'   => 'select',
                            'script' => "\$('#snmp_options_v'+this.value).toggle()",
                            'return' => true,
                        ],
                    ],
                ],
            ];

            // SNMP Options pack v1.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_v1',
                'block_content' => [
                    [
                        'label'     => __('Community'),
                        'arguments' => [
                            'name'   => 'community',
                            'type'   => 'text',
                            'size'   => 25,
                            'return' => true,

                        ],
                    ],
                ],
            ];

            // SNMP Options pack v2c.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_v2c',
                'block_content' => [
                    [
                        'label'     => __('Community'),
                        'arguments' => [
                            'name'   => 'community',
                            'type'   => 'text',
                            'size'   => 25,
                            'return' => true,

                        ],
                    ],
                ],
            ];

            // SNMP Options pack v3.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_v3',
                'block_content' => [
                    [
                        'label'     => __(''),
                        'arguments' => [
                            'name'   => 'community',
                            'type'   => 'text',
                            'size'   => 25,
                            'return' => true,

                        ],
                    ],
                ],
            ];

            // Input: WMI enabled.
            $form['inputs'][] = [
                'label'     => __('WMI enabled'),
                'arguments' => [
                    'name'    => 'wmi_enabled',
                    'type'    => 'switch',
                    'return'  => true,
                    'onclick' => "\$('#wmi_extra').toggle();",

                ],
            ];

            // WMI CONFIGURATION.
            $form['inputs'][] = [
                'label'     => __('WMI Auth. strings'),
                'hidden'    => 1,
                'id'        => 'wmi_extra',
                'arguments' => [
                    'name'   => 'auth_strings',
                    'type'   => 'text',
                    'return' => true,

                ],
            ];

            // Input: Module template.
            $form['inputs'][] = [
                'label'     => __('OS detection'),
                'arguments' => [
                    'name'   => 'os_detect',
                    'type'   => 'switch',
                    'return' => true,

                ],
            ];

            // Input: Name resolution.
            $form['inputs'][] = [
                'label'     => __('Name resolution'),
                'arguments' => [
                    'name'   => 'resolve_names',
                    'type'   => 'switch',
                    'return' => true,
                ],
            ];

            // Input: Parent detection.
            $form['inputs'][] = [
                'label'     => __('Parent detection'),
                'arguments' => [
                    'name'   => 'parent_detection',
                    'type'   => 'switch',
                    'return' => true,
                ],
            ];

            // Input: VLAN enabled.
            $form['inputs'][] = [
                'label'     => __('VLAN enabled'),
                'arguments' => [
                    'name'   => 'os_detect',
                    'type'   => 'switch',
                    'return' => true,
                ],
            ];

            // Submit button.
            $form['inputs'][] = [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub next"',
                    'return'     => true,
                ],
            ];

            $form['form'] = [
                'method' => 'POST',
                'action' => '#',
            ];

            $this->printForm($form);
        }

        if ($this->page == 2) {
            // Interval and schedules.
            $interv_manual = 0;
            if ((int) $interval == 0) {
                $interv_manual = 1;
            }

            $form['inputs'][] = [
                'label'     => '<b>'.__('Interval').'</b>'.ui_print_help_tip(
                    __('Manual interval means that it will be executed only On-demand'),
                    true
                ),
                'arguments' => [
                    'type'     => 'select',
                    'selected' => $interv_manual,
                    'fields'   => [
                        0 => __('Defined'),
                        1 => __('Manual'),
                    ],
                    'name'     => 'interval_manual_defined',
                    'return'   => true,
                ],
                'extra'     => '<span id="interval_manual_container">'.html_print_extended_select_for_time(
                    'interval',
                    $interval,
                    '',
                    '',
                    '0',
                    false,
                    true,
                    false,
                    false
                ).ui_print_help_tip(
                    __('The minimum recomended interval for Recon Task is 5 minutes'),
                    true
                ).'</span>',
            ];

            // Hidden, id_rt.
            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'task',
                    'value'  => $this->task['id_rt'],
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ];

            // Hidden, page.
            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'page',
                    'value'  => ($this->page + 1),
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ];

            // Submit button.
            $form['inputs'][] = [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub next"',
                    'return'     => true,
                ],
            ];

            $form['form'] = [
                'method' => 'POST',
                'action' => '#',
            ];

            $form['js'] = '
$("select#interval_manual_defined").change(function() {
    if ($("#interval_manual_defined").val() == 1) {
        $("#interval_manual_container").hide();
        $("#text-interval_text").val(0);
        $("#hidden-interval").val(0);
    }
    else {
        $("#interval_manual_container").show();
        $("#text-interval_text").val(10);
        $("#hidden-interval").val(600);
        $("#interval_units").val(60);
    }
}).change();';

            $this->printForm($form);
        }

        if ($this->page == 100) {
            return [
                'result' => $this->result,
                'id'     => $this->id,
                'msg'    => $this->msg,
            ];
        }
    }


}
