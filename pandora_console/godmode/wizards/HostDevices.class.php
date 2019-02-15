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
        string $msg='Default message. Not set.',
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
            echo '<div id="contenedor_principal">';
            echo '<div id="contenedor_imagen_texto">';
            echo '<div id="imagen">';
            echo '<a href="'.$this->url.'&mode=importcsv" alt="importcsv"><img src="images/wizard/csv_image.svg" alt="importcsv"></a>';
            echo '</div>';
            echo '<div class="texto">';
            echo '<a href="'.$this->url.'&mode=importcsv" alt="importcsv" id="text_wizard">'.__('Import CSV').'</a>';
            echo '</div>';
            echo '</div>';
            echo '<div id="contenedor_imagen_texto">';
            echo '<div id="imagen">';
            echo '<a href="'.$this->url.'&mode=netscan" alt="netscan"><img src="images/wizard/csv_image.svg" alt="importcsv"></a>';
            echo '</div>';
            echo '<div class="texto">';
            echo '<a href="'.$this->url.'&mode=netscan" alt="netscan" id="text_wizard">'.__('Escanear red').'</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

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
            if ($this->page != 3) {
                // Do not paint breadcrum in last page. Redirected.
                $this->setBreadcrum(
                    [
                        '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                        '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan">Net scan</a>',
                    ]
                );
                $this->printHeader();
            }

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
            // Check if we're updating a task.
            $task_id = get_parameter('task', null);

            if (isset($task_id) === true) {
                // We're updating this task.
                $task = db_get_row(
                    'trecon_task',
                    'id_rt',
                    $task_id
                );

                if ($task !== false) {
                    $this->task = $task;
                }
            }

            return true;
        }

        // Validate response from page 0. No, not a bug, we're always 1 page
        // from 'validation' page.
        if ($this->page == 1) {
            $task_id = get_parameter('task', null);
            $taskname = get_parameter('taskname', '');
            $comment = get_parameter('comment', '');
            $server_id = get_parameter('id_recon_server', '');
            $network = get_parameter('network', '');
            $id_group = get_parameter('id_group', '');

            if (isset($task_id) === true) {
                // We're updating this task.
                $task = db_get_row(
                    'trecon_task',
                    'id_rt',
                    $task_id
                );

                if ($task !== false) {
                    $this->task = $task;
                }
            } else if (isset($taskname) === true
                && isset($network) === true
            ) {
                // Avoid double creation.
                $task = db_get_row_filter(
                    'trecon_task',
                    [
                        'name'   => $taskname,
                        'subnet' => $network,
                    ]
                );

                if ($task !== false) {
                    $this->task = $task;
                }

                $this->msg = __('This network scan task has been already defined. Please edit it or create a new one.');
                return false;
            }

            if (isset($this->task['id_rt']) === false) {
                // Disabled 2 Implies wizard non finished.
                $this->task['disabled'] = 2;
            }

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
            $this->task['id_group'] = $id_group;

            if (isset($this->task['id_rt']) === false) {
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

            $task = db_get_row(
                'trecon_task',
                'id_rt',
                $id_rt
            );

            if ($task !== false) {
                $this->task = $task;
            } else {
                $this->msg = __('Failed to find network scan task.');
                return false;
            }

            $id_network_profile = get_parameter('id_network_profile', null);
            $snmp_enabled = get_parameter_switch('snmp_enabled');
            $os_detect = get_parameter_switch('os_detect');
            $parent_detection = get_parameter_switch('parent_detection');
            $parent_recursion = get_parameter_switch('parent_recursion');
            $vlan_enabled = get_parameter_switch('vlan_enabled');
            $wmi_enabled = get_parameter_switch('wmi_enabled');
            $resolve_names = get_parameter_switch('resolve_names');
            $snmp_version = get_parameter('snmp_version', null);
            $community = get_parameter('community', null);
            $snmp_context = get_parameter('snmp_context', null);
            $snmp_auth_user = get_parameter('snmp_auth_user', null);
            $snmp_auth_pass = get_parameter('snmp_auth_pass', null);
            $snmp_privacy_method = get_parameter('snmp_privacy_method', null);
            $snmp_privacy_pass = get_parameter('snmp_privacy_pass', null);
            $snmp_auth_method = get_parameter('snmp_auth_method', null);
            $snmp_security_level = get_parameter('snmp_security_level', null);
            $auth_strings = get_parameter('auth_strings', null);

            if ($snmp_version == 3) {
                $this->task['snmp_community'] = $snmp_context;
            } else {
                $this->task['snmp_community'] = $community;
            }

            $this->task['id_network_profile'] = $id_network_profile;
            $this->task['snmp_enabled'] = $snmp_enabled;
            $this->task['os_detect'] = $os_detect;
            $this->task['parent_detection'] = $parent_detection;
            $this->task['parent_recursion'] = $parent_recursion;
            $this->task['vlan_enabled'] = $vlan_enabled;
            $this->task['wmi_enabled'] = $wmi_enabled;
            $this->task['resolve_names'] = $resolve_names;
            $this->task['snmp_version'] = $snmp_version;
            $this->task['snmp_auth_user'] = $snmp_auth_user;
            $this->task['snmp_auth_pass'] = $snmp_auth_pass;
            $this->task['snmp_privacy_method'] = $snmp_privacy_method;
            $this->task['snmp_privacy_pass'] = $snmp_privacy_pass;
            $this->task['snmp_auth_method'] = $snmp_auth_method;
            $this->task['snmp_security_level'] = $snmp_security_level;
            $this->task['auth_strings'] = $auth_strings;

            // Update.
            $res = db_process_sql_update(
                'trecon_task',
                $this->task,
                ['id_rt' => $this->task['id_rt']]
            );

            return true;
        }

        if ($this->page == 3) {
            // Interval and schedules.
            // By default manual if not defined.
            $id_rt = get_parameter('task', -1);

            $task = db_get_row(
                'trecon_task',
                'id_rt',
                $id_rt
            );

            if ($task !== false) {
                $this->task = $task;
            } else {
                $this->msg = __('Failed to find network scan task.');
                return false;
            }

            $interval = get_parameter('interval', 0);
            $this->task['interval_sweep'] = $interval;

            if ($this->task['disabled'] == 2) {
                // Wizard finished.
                $this->task['disabled'] = 0;
            }

            // Update.
            $res = db_process_sql_update(
                'trecon_task',
                $this->task,
                ['id_rt' => $this->task['id_rt']]
            );

            return true;
        }

        if ($this->page == 4) {
            // Wizard ended. Load data and return control to Discovery.
            $id_rt = get_parameter('task', -1);

            $task = db_get_row(
                'trecon_task',
                'id_rt',
                $id_rt
            );

            if ($task !== false) {
                $this->task = $task;
            } else {
                $this->msg = __('Failed to find network scan task.');
                return false;
            }

            return true;
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
                    'action' => $this->url.'&mode=netscan&page='.($this->page - 1).'&task='.$this->task['id_rt'],
                ],
                'inputs' => [
                    [
                        'arguments' => [
                            'type'  => 'hidden',
                            'name'  => 'task',
                            'value' => $this->task['id_rt'],
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
                    'action' => $this->url.'&mode=netscan&page=0',
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
                        'value' => $this->task['name'],
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                if (isset($this->task['id_rt']) === true) {
                    // Propagate id.
                    $form['inputs'][] = [
                        'arguments' => [
                            'name'  => 'task',
                            'value' => $this->task['id_rt'],
                            'type'  => 'hidden',
                        ],
                    ];
                }

                // Input task name.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Comment').'</b>',
                    'arguments' => [
                        'name'  => 'comment',
                        'value' => $this->task['description'],
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
                        'selected' => $this->task['id_recon_server'],
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
                        'name'  => 'network',
                        'value' => $this->task['subnet'],
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
                        'selected'  => $this->task['id_group'],
                        'return'    => true,
                    ],
                ];

                $str = __('Next');

                if (isset($this->task['id_rt']) === true) {
                    $str = __('Update and continue');
                }

                // Submit button.
                $form['inputs'][] = [
                    'arguments' => [
                        'name'       => 'submit',
                        'label'      => $str,
                        'type'       => 'submit',
                        'attributes' => 'class="sub next"',
                        'return'     => true,
                    ],
                ];

                $task_url = '';
                if (isset($this->task['id_rt'])) {
                    $task_url = '&task='.$this->task['id_rt'];
                }

                $form['form'] = [
                    'method' => 'POST',
                    'action' => $this->url.'&mode=netscan&page='.($this->page + 1).$task_url,
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
                    'name'     => 'id_network_profile',
                    'type'     => 'select_from_sql',
                    'sql'      => 'SELECT id_np, name
                            FROM tnetwork_profile
                            ORDER BY name',
                    'return'   => true,
                    'selected' => $this->task['id_network_profile'],

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
                    'value'   => (isset($this->task['snmp_enabled'])) ? $this->task['snmp_enabled'] : 1,
                    'onclick' => 'extraSNMP();',

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
                            'name'     => 'snmp_version',
                            'fields'   => [
                                '1'  => 'v. 1',
                                '2c' => 'v. 2c',
                                '3'  => 'v. 3',
                            ],
                            'type'     => 'select',
                            'script'   => 'SNMPExtraShow(this.value)',
                            'selected' => $this->task['snmp_version'],
                            'return'   => true,
                        ],
                    ],
                ],
            ];

            // SNMP Options pack v1.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_basic',
                'block_content' => [
                    [
                        'label'     => '<b>'.__('SNMP Default community').'</b>'.ui_print_help_tip(
                            __(
                                'You can specify several values, separated by commas, for example: public,mysecret,1234'
                            ),
                            true
                        ),
                        'arguments' => [
                            'name'   => 'community',
                            'type'   => 'text',
                            'value'  => $this->task['snmp_community'],
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
                        'label'     => '<b>'.__('Context').'</b>',
                        'arguments' => [
                            'name'   => 'snmp_context',
                            'type'   => 'text',
                            'value'  => $this->task['snmp_community'],
                            'size'   => 15,
                            'return' => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Auth user').'</b>',
                        'arguments' => [
                            'name'   => 'snmp_auth_user',
                            'type'   => 'text',
                            'value'  => $this->task['snmp_auth_user'],
                            'size'   => 15,
                            'return' => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Auth password').'</b>'.ui_print_help_tip(
                            __(
                                'The pass length must be eight character minimum.'
                            ),
                            true
                        ),
                        'arguments' => [
                            'name'   => 'snmp_auth_pass',
                            'type'   => 'password',
                            'value'  => $this->task['snmp_auth_pass'],
                            'size'   => 15,
                            'return' => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Privacy method').'</b>',
                        'arguments' => [
                            'name'     => 'snmp_privacy_method',
                            'type'     => 'select',
                            'fields'   => [
                                'DES' => __('DES'),
                                'AES' => __('AES'),
                            ],
                            'selected' => $this->task['snmp_privacy_method'],
                            'size'     => 15,
                            'return'   => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Privacy pass').'</b>'.ui_print_help_tip(
                            __(
                                'The pass length must be eight character minimum.'
                            ),
                            true
                        ),
                        'arguments' => [
                            'name'   => 'snmp_privacy_pass',
                            'type'   => 'password',
                            'value'  => $this->task['snmp_privacy_pass'],
                            'size'   => 15,
                            'return' => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Auth method').'</b>',
                        'arguments' => [
                            'name'     => 'snmp_auth_method',
                            'type'     => 'select',
                            'fields'   => [
                                'MD5' => __('MD5'),
                                'SHA' => __('SHA'),
                            ],
                            'selected' => $this->task['snmp_auth_method'],
                            'size'     => 15,
                            'return'   => true,

                        ],
                    ],
                    [
                        'label'     => '<b>'.__('Security level').'</b>',
                        'arguments' => [
                            'name'     => 'snmp_security_level',
                            'type'     => 'select',
                            'fields'   => [
                                'noAuthNoPriv' => __('Not auth and not privacy method'),
                                'authNoPriv'   => __('Auth and not privacy method'),
                                'authPriv'     => __('Auth and privacy method'),
                            ],
                            'selected' => $this->task['snmp_security_level'],
                            'size'     => 15,
                            'return'   => true,

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
                    'value'   => (isset($this->task['wmi_enabled'])) ? $this->task['wmi_enabled'] : 0,
                    'return'  => true,
                    'onclick' => 'toggleWMI();',

                ],
            ];

            // WMI CONFIGURATION.
            $form['inputs'][] = [
                'block_id'      => 'wmi_extra',
                'hidden'        => 1,
                'block_content' => [
                    [
                        'label'     => __('WMI Auth. strings'),
                        'arguments' => [
                            'name'   => 'auth_strings',
                            'type'   => 'text',
                            'value'  => $this->task['auth_strings'],
                            'return' => true,
                        ],
                    ],
                ],
            ];

            // Input: Module template.
            $form['inputs'][] = [
                'label'     => __('OS detection'),
                'arguments' => [
                    'name'   => 'os_detect',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['os_detect'])) ? $this->task['os_detect'] : 1,

                ],
            ];

            // Input: Name resolution.
            $form['inputs'][] = [
                'label'     => __('Name resolution'),
                'arguments' => [
                    'name'   => 'resolve_names',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['resolve_names'])) ? $this->task['resolve_names'] : 0,
                ],
            ];

            // Input: Parent detection.
            $form['inputs'][] = [
                'label'     => __('Parent detection'),
                'arguments' => [
                    'name'   => 'parent_detection',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['parent_detection'])) ? $this->task['parent_detection'] : 1,
                ],
            ];

            // Input: Parent recursion.
            $form['inputs'][] = [
                'label'     => __('Parent recursion'),
                'arguments' => [
                    'name'   => 'parent_recursion',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['parent_recursion'])) ? $this->task['parent_recursion'] : 1,
                ],
            ];

            // Input: VLAN enabled.
            $form['inputs'][] = [
                'label'     => __('VLAN enabled'),
                'arguments' => [
                    'name'   => 'vlan_enabled',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['vlan_enabled'])) ? $this->task['vlan_enabled'] : 1,
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

            $form['js'] = '
function SNMPExtraShow(target) {
    $("#snmp_options_basic").hide();
    $("#snmp_options_v3").hide();
    if (document.getElementsByName("snmp_enabled")[0].checked) {
        if (target == 3) {
            $("#snmp_options_v3").show();
        } else {
            $("#snmp_options_basic").show();
        }
    }

}

function extraSNMP() {
    if (document.getElementsByName("snmp_enabled")[0].checked) {
        SNMPExtraShow($("#snmp_version").val());
        $("#snmp_extra").show();
    } else {
        // Hide unusable sections
        $("#snmp_extra").hide();
        $("#snmp_options_basic").hide();
        $("#snmp_options_v3").hide();

        // Disable snmp dependant checks
        if (document.getElementsByName("parent_recursion")[0].checked)
            $("input[name=parent_recursion]").click();

        if (document.getElementsByName("parent_detection")[0].checked)
            $("input[name=parent_detection]").click();

        if (document.getElementsByName("vlan_enabled")[0].checked)
            $("input[name=vlan_enabled]").click();
        
    }
}

function toggleWMI() {
    if (document.getElementsByName("wmi_enabled")[0].checked)
        $("#wmi_extra").show();
    else
        $("#wmi_extra").hide();
}

$(function() {
    SNMPExtraShow($("#snmp_version").val());
    toggleWMI();
});
            ';

            $form['form'] = [
                'method' => 'POST',
                'action' => $this->url.'&mode=netscan&page='.($this->page + 1).'&task='.$this->task['id_rt'],
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
                'action' => $this->url.'&mode=netscan&page='.($this->page + 1).'&task='.$this->task['id_rt'],
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
            return null;
        }

        if ($this->page == 3) {
            if ($this->task['id_rt']) {
                // 0 - Is OK.
                $this->result = 0;
                $this->msg = __('Task configured.');
            } else {
                // 1 - Is NOT OK.
                $this->result = 1;
                $this->msg = __('Wizard failed. Cannot configure task.');
            }

            return [
                'result' => $this->result,
                'id'     => $this->task['id_rt'],
                'msg'    => $this->msg,
            ];
        }
    }


}
