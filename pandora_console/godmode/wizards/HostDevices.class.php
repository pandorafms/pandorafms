<?php
/**
 * Defines wizard to configure discovery tasks (Host&devices)
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
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
require_once $config['homedir'].'/include/class/CustomNetScan.class.php';
require_once $config['homedir'].'/include/class/ManageNetScanScripts.class.php';

enterprise_include_once('include/class/CSVImportAgents.class.php');
enterprise_include_once('include/class/DeploymentCenter.class.php');
enterprise_include_once('include/functions_hostdevices.php');

/**
 * Wizard section Host&devices.
 * Provides classic recon task creation.
 * In enterprise environments, provides also CSV agent import features.
 */
class HostDevices extends Wizard
{

     /**
      * Number of pages to control breadcrum.
      *
      * @var integer
      */
    public $maxPagesNetScan = 2;

    /**
     * Labels for breadcrum.
     *
     * @var array
     */
    public $pageLabelsNetScan = [
        'NetScan definition',
        'NetScan features',
    ];

    /**
     * Stores all needed parameters to create a recon task.
     *
     * @var array
     */
    public $task;


    /**
     * Constructor.
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
        string $icon='images/wizard/hostdevices.png',
        string $label='Host & Devices'
    ) {
        $this->setBreadcrum([]);

        $this->access = 'AW';
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
     * Checks if environment is ready,
     * returns array
     *   icon: icon to be displayed
     *   label: label to be displayed
     *
     * @return array With data.
     **/
    public function load()
    {
        global $config;
        // Check access.
        check_login();

        if (! $this->aclMulticheck('AW|PM')) {
            return false;
        }

        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,
        ];
    }


    /**
     * Run wizard manager.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();

        ui_require_css_file('hostdevices');

        $mode = get_parameter('mode', null);

        if ($mode === null) {
            $buttons = [];

            if (check_acl($config['id_user'], 0, $this->access)) {
                $buttons[] = [
                    'url'   => $this->url.'&mode=netscan',
                    'icon'  => 'images/wizard/netscan.png',
                    'label' => __('Net Scan'),
                ];

                if (enterprise_installed()) {
                    $buttons[] = [
                        'url'   => $this->url.'&mode=importcsv',
                        'icon'  => ENTERPRISE_DIR.'/images/wizard/csv.png',
                        'label' => __('Import CSV'),
                    ];

                    $buttons[] = [
                        'url'   => $this->url.'&mode=deploy',
                        'icon'  => ENTERPRISE_DIR.'/images/wizard/deployment.png',
                        'label' => __('Agent deployment'),
                    ];
                }

                $buttons[] = [
                    'url'   => $this->url.'&mode=customnetscan',
                    'icon'  => '/images/wizard/customnetscan.png',
                    'label' => __('Custom NetScan'),
                ];
            }

            if (check_acl($config['id_user'], 0, 'PM')) {
                $buttons[] = [
                    'url'   => $this->url.'&mode=managenetscanscripts',
                    'icon'  => '/images/wizard/managenetscanscripts.png',
                    'label' => __('Manage NetScan scripts'),
                ];
            }

            $this->prepareBreadcrum(
                [
                    [
                        'link'  => ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery'
                        ),
                        'label' => __('Discovery'),
                    ],
                    [
                        'link'     => ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd'
                        ),
                        'label'    => __('Host & Devices'),
                        'selected' => true,
                    ],
                ],
                true
            );

            ui_print_page_header(
                __('Host & devices'),
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

            $this->printBigButtonsList($buttons);
            return;
        }

        if (enterprise_installed()) {
            if ($mode === 'importcsv') {
                $csv_importer = new CSVImportAgents(
                    $this->page,
                    $this->breadcrum
                );
                return $csv_importer->runCSV();
            }

            if ($mode === 'deploy') {
                $deployObject = new DeploymentCenter(
                    $this->page,
                    $this->breadcrum
                );
                return $deployObject->run();
            }
        }

        if ($mode === 'customnetscan') {
            $customnetscan_importer = new CustomNetScan(
                $this->page,
                $this->breadcrum
            );
            return $customnetscan_importer->runCustomNetScan();
        }

        if ($mode === 'managenetscanscripts') {
            $managenetscanscript_importer = new ManageNetScanScripts(
                $this->page,
                $this->breadcrum
            );
            return $managenetscanscript_importer->runManageNetScanScript();
        }

        if ($mode == 'netscan') {
            return $this->runNetScan();
        }

        return null;
    }


    // Extra methods.


    /**
     * Retrieves and validates information given by user in NetScan wizard.
     *
     * @return boolean Data OK or not.
     */
    public function parseNetScan()
    {
        global $config;

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
            $network_csv_enabled = (bool) get_parameter_switch(
                'network_csv_enabled',
                false
            );
            $id_group = get_parameter('id_group', '');
            $interval = get_parameter('interval', 0);

            if ($network_csv_enabled) {
                if (empty($_FILES['network_csv']['type']) === false) {
                    if ($_FILES['network_csv']['type'] != 'text/csv'
                        && $_FILES['network_csv']['type'] != 'text/plain'
                        && $_FILES['network_csv']['type'] != 'application/octet-stream'
                        && $_FILES['network_csv']['type'] != 'application/vnd.ms-excel'
                        && $_FILES['network_csv']['type'] != 'text/x-csv'
                        && $_FILES['network_csv']['type'] != 'application/csv'
                        && $_FILES['network_csv']['type'] != 'application/x-csv'
                        && $_FILES['network_csv']['type'] != 'text/csv'
                        && $_FILES['network_csv']['type'] != 'text/comma-separated-values'
                        && $_FILES['network_csv']['type'] != 'text/x-comma-separated-values'
                        && $_FILES['network_csv']['type'] != 'text/tab-separated-values'
                    ) {
                        $this->msg = __(
                            'Invalid mimetype for csv file: %s',
                            $_FILES['network_csv']['type']
                        );
                        return false;
                    }

                    $network = preg_split(
                        "/\n|,|;/",
                        trim(
                            file_get_contents(
                                $_FILES['network_csv']['tmp_name']
                            )
                        )
                    );

                    // Forbidden chars cleaning.
                    foreach ($network as $key => $singleNetwork) {
                        $network[$key] = preg_replace('/[-()\']/', '', $singleNetwork);
                    }

                    unlink($_FILES['network_csv']['tmp_name']);
                    if (empty($network) || is_array($network) === false) {
                        $this->msg = __(
                            'Invalid content readed from csv file: %s',
                            $_FILES['network_csv']['name']
                        );
                        return false;
                    }

                    // Sanitize.
                    $network = array_unique($network);
                    $network = array_filter(
                        $network,
                        function ($item) {
                            return (!empty($item));
                        }
                    );
                    $network = join(',', $network);
                }
            }

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
                    $this->msg = __('This network scan task has been already defined. Please edit it or create a new one.');
                    return false;
                }
            }

            if ($task_id !== null
                && $taskname == null
                && $server_id == null
                && empty($id_group) === true
                && empty($network) === true
                && empty($network_csv) === true
                && $interval === 0
            ) {
                // Default values, no data received.
                // User is accesing directly to this page.
                if (check_acl(
                    $config['id_user'],
                    $this->task['id_group'],
                    $this->access
                ) != true
                ) {
                    $this->msg = __('You have no access to edit this task.');
                    return false;
                }
            } else {
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
                $this->task['interval_sweep'] = $interval;
                $this->task['subnet_csv'] = $network_csv_enabled;

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

            $id_network_profile = get_parameter('id_network_profile', []);
            $review_results = get_parameter_switch('review_results');
            $review_limited = (bool) get_parameter('review_limited', 0);
            $auto_monitor = get_parameter_switch('auto_monitor');
            $recon_ports = get_parameter('recon_ports', null);
            $autoconf_enabled = get_parameter_switch(
                'autoconfiguration_enabled'
            );
            $snmp_enabled = get_parameter_switch('snmp_enabled');
            $os_detect = get_parameter_switch('os_detect');
            $parent_detection = get_parameter_switch('parent_detection');
            $parent_recursion = get_parameter_switch('parent_recursion');
            $vlan_enabled = get_parameter_switch('vlan_enabled');
            $wmi_enabled = get_parameter_switch('wmi_enabled');
            $rcmd_enabled = get_parameter_switch('rcmd_enabled');
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
            $snmp_skip_non_enabled_ifs = get_parameter_switch('snmp_skip_non_enabled_ifs');
            $auth_strings = get_parameter('auth_strings', []);

            if ($snmp_version == 3) {
                $this->task['snmp_community'] = $snmp_context;
            } else {
                $this->task['snmp_community'] = $community;
            }

            $this->task['autoconfiguration_enabled'] = $autoconf_enabled;
            $this->task['id_network_profile'] = '';
            if (is_array($id_network_profile) === true) {
                $this->task['id_network_profile'] = join(
                    ',',
                    $id_network_profile
                );
            }

            if ($review_limited === true) {
                // License limited, force review.
                $this->task['review_mode'] = DISCOVERY_REVIEW;
            } else {
                if ($review_results) {
                    if ($this->task['review_mode'] != DISCOVERY_RESULTS) {
                        $this->task['review_mode'] = DISCOVERY_REVIEW;
                    }
                } else {
                    $this->task['review_mode'] = DISCOVERY_STANDARD;
                }
            }

            $this->task['auto_monitor'] = $auto_monitor;
            $this->task['recon_ports'] = $recon_ports;
            $this->task['snmp_enabled'] = $snmp_enabled;
            $this->task['os_detect'] = $os_detect;
            $this->task['parent_detection'] = $parent_detection;
            $this->task['parent_recursion'] = $parent_recursion;
            $this->task['vlan_enabled'] = $vlan_enabled;
            $this->task['wmi_enabled'] = $wmi_enabled;
            $this->task['rcmd_enabled'] = $rcmd_enabled;
            $this->task['resolve_names'] = $resolve_names;
            $this->task['snmp_version'] = $snmp_version;
            $this->task['snmp_auth_user'] = $snmp_auth_user;
            $this->task['snmp_auth_pass'] = $snmp_auth_pass;
            $this->task['snmp_privacy_method'] = $snmp_privacy_method;
            $this->task['snmp_privacy_pass'] = $snmp_privacy_pass;
            $this->task['snmp_auth_method'] = $snmp_auth_method;
            $this->task['snmp_security_level'] = $snmp_security_level;
            $this->task['snmp_skip_non_enabled_ifs'] = $snmp_skip_non_enabled_ifs;
            $this->task['auth_strings'] = '';
            if (is_array($auth_strings) === true) {
                $this->task['auth_strings'] = join(
                    ',',
                    $auth_strings
                );
            }

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

        if ($this->page == 3) {
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

        if (! check_acl($config['id_user'], 0, $this->access)) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Agent Management'
            );
            include 'general/noaccess.php';
            return;
        }

        if ($this->parseNetScan() === false) {
            // Error.
            ui_print_error_message(
                $this->msg
            );

            $form = [
                'form'   => [
                    'method' => 'POST',
                    'action' => $this->url.'&mode=netscan&page='.($this->page - 1).'&task='.$this->task['id_rt'],
                    'class'  => 'flex_center',
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
                            'attributes' => [
                                'icon' => 'back',
                                'mode' => 'secondary',
                            ],
                            'return'     => true,
                        ],
                    ],
                ],
            ];

            // Check ACL. If user is not able to manage target task,
            // redirect him to main page.
            if (check_acl(
                $config['id_user'],
                $this->task['id_group'],
                $this->access
            ) != true
            ) {
                $form['form']['action'] = $this->url.'&mode=netscan&page='.($this->page - 1);
            }

            html_print_action_buttons($this->printForm($form, true));
            return null;
        }

        $task_url = '';
        if (isset($this->task['id_rt'])) {
            $task_url = '&task='.$this->task['id_rt'];
        }

        $breadcrum = [
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                'label' => 'Discovery',
            ],
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd',
                'label' => __($this->label),
            ],
        ];
        for ($i = 0; $i < $this->maxPagesNetScan; $i++) {
            $breadcrum[] = [
                'link'     => 'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan&page='.$i.$task_url,
                'label'    => $this->pageLabelsNetScan[$i],
                'selected' => (($i == $this->page) ? 1 : 0),
            ];
        }

        if ($this->page < $this->maxPagesNetScan) {
            $title = __('NetScan');

            if ($this->page == 1) {
                $title = __(
                    '"%s" features',
                    $this->task['name']
                );
            }

            // Avoid to print header out of wizard.
            $this->prepareBreadcrum($breadcrum);
            ui_print_page_header(
                $title,
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
        }

        if (isset($this->page) === true
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
                    'class'  => 'flex_center',
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
                            'attributes' => [
                                'icon' => 'back',
                                'mode' => 'secondary',
                            ],
                            'return'     => true,
                        ],
                    ],
                ],
            ];

            html_print_action_buttons($this->printForm($form, true));
            return null;
        }

        // -------------------------------.
        // Page 0. wizard starts HERE.
        // -------------------------------.
        if (isset($this->page) === true || $this->page == 0) {
            if (isset($this->page) === false
                || $this->page == 0
            ) {
                $form = [];

                $str = __('Next');

                if (isset($this->task['id_rt']) === true) {
                    $str = __('Update and continue');
                }

                // Interval and schedules.
                $interv_manual = 0;
                if ((int) $this->task['interval_sweep'] == 0) {
                    $interv_manual = 1;
                }

                $form['rows'][0]['new_form_block'] = true;

                $form['rows'][0]['columns'][0] = [
                    'width'  => '30%',
                    'style'  => 'padding: 9px;min-width: 188px;',
                    'inputs' => [
                        '1' => '<div class="height_50p mrgn_btn_35px">'.html_print_image('images/wizard/netscan_green.png', true, ['title' => __('Close')], false).'</div>',
                        '2' => [
                            'label'     => '<b>'.__('Interval').':</b>'.ui_print_help_tip(
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
                            'extra'     => '<div id="interval_manual_container"><div class="time_selection_container mrgn_top_20px">'.html_print_extended_select_for_time(
                                'interval',
                                $this->task['interval_sweep'],
                                '',
                                '',
                                '0',
                                false,
                                true,
                                false,
                                false
                            ).'</div></div>',

                        ],
                    ],
                ];
                $form['rows'][0]['columns'][1] = [
                    'width'         => '40%',
                    'padding-right' => '8%',
                    'padding-left'  => '0%',
                    'style'         => 'min-width: 230px',
                    'inputs'        => [
                        '0' => [
                            'label'     => '<b>'.__('Task name').':</b>',
                            'arguments' => [
                                'name'  => 'taskname',
                                'value' => $this->task['name'],
                                'type'  => 'text',
                                'size'  => 25,
                                'class' => 'discovery_full_width_input',
                            ],
                        ],
                        '1' => [
                            'label'     => '<b>'.__('Discovery server').':</b>'.ui_print_help_tip(
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
                                'style'    => 'width: 100%;',
                                'selected' => $this->task['id_recon_server'],
                                'return'   => true,
                            ],
                        ],
                        '2' => [
                            'label'     => '<b>'.__('Use CSV file definition').':</b>'.ui_print_help_tip(
                                __('Define targets using csv o network definition.'),
                                true
                            ),
                            'class'     => 'no-margin',
                            'arguments' => [
                                'name'    => 'network_csv_enabled',
                                'value'   => $this->task['subnet_csv'],
                                'type'    => 'switch',
                                'inline'  => true,
                                'class'   => 'discovery_full_width_input',
                                'onclick' => 'toggleNetwork(this);',
                            ],
                        ],
                        '3' => [
                            'hidden'        => (($this->task['subnet_csv'] == '1') ? 0 : 1),
                            'block_id'      => 'csv_subnet',
                            'block_content' => [
                                [
                                    'label'     => '<b>'.__('Networks (csv)').':</b>'.ui_print_help_tip(
                                        __('You can upload a CSV file. Each line must contain a network in IP/MASK format. For instance: 192.168.1.1/32'),
                                        true
                                    ),
                                    'arguments' => [
                                        'name'    => 'network_csv',
                                        'type'    => 'file',
                                        'columns' => 25,
                                        'rows'    => 10,
                                        'class'   => 'discovery_full_width_input',
                                    ],
                                ],
                                [
                                    'label'     => '<b>'.__('Networks (current)').':</b>'.ui_print_help_tip(
                                        __('Please upload a new file to overwrite this content.'),
                                        true
                                    ),
                                    'arguments' => [
                                        'attributes' => 'readonly',
                                        'type'       => 'textarea',
                                        'size'       => 25,
                                        'value'      => $this->task['subnet'],
                                    ],
                                ],
                            ],
                        ],
                        '4' => [
                            'hidden'    => (($this->task['subnet_csv'] == '1') ? 1 : 0),
                            'id'        => 'std_subnet',
                            'label'     => '<b>'.__('Network').':</b>'.ui_print_help_tip(
                                __('You can specify networks or fully qualified domain names of a specific host, separated by commas, for example: 192.168.50.0/24,192.168.60.0/24, hostname.artica.es'),
                                true
                            ),
                            'arguments' => [
                                'name'  => 'network',
                                'value' => $this->task['subnet'],
                                'type'  => 'text',
                                'size'  => 25,
                                'class' => 'discovery_full_width_input',
                            ],
                        ],
                    ],
                ];

                $form['rows'][0]['columns'][2] = [
                    'width'         => '40%',
                    'padding-right' => '5%',
                    'padding-left'  => '0',
                    'style'         => 'min-width: 144px;',
                    'inputs'        => [
                        '0' => [
                            'label'     => '<b>'.__('Group').':</b>',
                            'arguments' => [
                                'name'                    => 'id_group',
                                'returnAllGroup'          => false,
                                'privilege'               => $this->access,
                                'type'                    => 'select_groups',
                                'selected'                => $this->task['id_group'],
                                'return'                  => true,
                                'class'                   => 'discovery_list_input',
                                'simple_multiple_options' => true,
                                'required'                => true,
                            ],
                        ],
                    ],
                ];

                $form['rows'][1]['style'] = 'style de row';
                $form['rows'][1]['columns'][0] = [
                    'padding-right' => '0',
                    'inputs'        => [
                        '0' => [
                            'label'     => '<b>'.__('Comment').':</b>',
                            'arguments' => [
                                'name'    => 'comment',
                                'rows'    => 1,
                                'columns' => 1,
                                'value'   => $this->task['description'],
                                'type'    => 'textarea',
                                'size'    => 25,
                                'class'   => 'discovery_textarea_input',
                                'return'  => true,
                            ],
                        ],
                    ],
                ];

                $task_url = '';
                if (isset($this->task['id_rt'])) {
                    $task_url = '&task='.$this->task['id_rt'];
                }

                $form['form'] = [
                    'method'  => 'POST',
                    'enctype' => 'multipart/form-data',
                    'action'  => $this->url.'&mode=netscan&page='.($this->page + 1).$task_url,
                    'id'      => 'form-netscan-definition',
                ];

                // Default.
                $interval = 600;
                $unit = 60;
                if (isset($this->task['interval_sweep']) === true) {
                    $interval = $this->task['interval_sweep'];
                    $unit = $this->getTimeUnit($interval);
                }

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
                            $("#hidden-interval").val('.$interval.');
                            $("#interval_units").val('.$unit.');
                            $("#interval_units").trigger("change");
                        }
                    }).change();
                    
                    function toggleNetwork(e) {
                        if (e.checked) {
                            $(\'#csv_subnet\').removeClass("hidden");
                            $(\'#std_subnet\').addClass("hidden");
                        } else {
                            $(\'#csv_subnet\').addClass("hidden");
                            $(\'#std_subnet\').removeClass("hidden");
                        }
                    };
                    
                    ';

                $this->printFormAsGrid($form);

                $output_form = $this->printInput(
                    [
                        'name'       => 'submit',
                        'label'      => $str,
                        'type'       => 'submit',
                        'attributes' => [
                            'icon' => 'next',
                            'form' => 'form-netscan-definition',
                        ],
                        'return'     => true,
                        'width'      => 'initial',
                    ]
                );

                $output_form .= $this->printGoBackButton($this->url.'&page='.($this->page - 1), true);

                html_print_action_buttons($output_form);
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

            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'page',
                    'value'  => ($this->page + 1),
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ];

            $form['inputs'][] = [
                'label'     => __('Filter by opened ports').ui_print_help_tip(
                    __(
                        'Targets will be scanned if at least one of defined ports (comma separated) is open.'
                    ),
                    true
                ),
                'arguments' => [
                    'name'   => 'recon_ports',
                    'type'   => 'text',
                    'return' => true,
                    'value'  => $this->task['recon_ports'],
                ],
            ];

            $form['inputs'][] = [
                'label'     => __('Auto discover known hardware').ui_print_help_tip(
                    __(
                        'Targets will be monitorized based on its <i>Private Enterprise Number</i>. Requires SNMP.'
                    ),
                    true
                ),
                'arguments' => [
                    'name'   => 'auto_monitor',
                    'type'   => 'switch',
                    'return' => true,
                    'value'  => (isset($this->task['auto_monitor'])) ? $this->task['auto_monitor'] : 1,
                ],
            ];

            $form['inputs'][] = [
                'label'     => __('Module templates').ui_print_help_tip(
                    __(
                        'Module <i>Host Alive</i> will be added to discovered agents by default.'
                    ),
                    true
                ),
                'arguments' => [
                    'name'          => 'id_network_profile[]',
                    'type'          => 'select_from_sql',
                    'sql'           => 'SELECT tn.id_np, tn.name
                            FROM tnetwork_profile tn
                            LEFT JOIN `tnetwork_profile_pen` tp
                                ON tp.id_np = tn.id_np
                            WHERE tp.id_np IS NULL
                            ORDER BY tn.name',
                    'return'        => true,
                    'selected'      => explode(
                        ',',
                        $this->task['id_network_profile']
                    ),
                    'nothing_value' => 0,
                    'nothing'       => __('None'),
                    'multiple'      => true,
                    'class'         => 'select_multiple',
                ],
            ];

            // License precheck.
            $license = enterprise_hook('license_get_info');
            $n_agents = 0;
            foreach (explode(',', $this->task['subnet']) as $net) {
                $mask = explode('/', $net, 2)[1];
                if (empty($mask)) {
                    $n_agents++;
                } else {
                    $n_agents += pow(2, (32 - $mask));
                }
            }

            $limited = false;
            if (is_array($license) === true
                && $n_agents > ($license['limit'] - $license['count'])
            ) {
                $limit = ($license['limit'] - $license['count']);
                $limited = true;
            }

            if ($limited === true) {
                ui_print_warning_message(
                    __(
                        'Configured networks could generate %d agents, your license only allows %d, \'review results\' is mandatory.',
                        $n_agents,
                        $limit
                    )
                );
            }

            $form['inputs'][] = [
                'label'     => __('Review results').ui_print_help_tip(
                    __(
                        'Targets must be validated by user before create agents.'
                    ),
                    true
                ),
                'arguments' => [
                    'name'     => 'review_results',
                    'type'     => 'switch',
                    'return'   => true,
                    'value'    => ($this->task['review_mode'] == DISCOVERY_STANDARD) ? (($limited) ? 1 : 0) : 1,
                    'disabled' => $limited,
                ],
            ];

            // Review limited.
            $form['inputs'][] = [
                'arguments' => [
                    'name'   => 'review_limited',
                    'type'   => 'hidden',
                    'return' => true,
                    'value'  => (($limited === true) ? 1 : 0),
                ],
            ];

            if (enterprise_installed() === true) {
                // Input: Enable auto configuration.
                $form['inputs'][] = [
                    'label'     => __('Apply autoconfiguration rules').ui_print_help_tip(
                        __(
                            'System is able to auto configure detected host & devices by applying your defined configuration rules.'
                        ),
                        true
                    ),
                    'arguments' => [
                        'name'   => 'autoconfiguration_enabled',
                        'type'   => 'switch',
                        'return' => true,
                        'value'  => (isset($this->task['autoconfiguration_enabled'])) ? $this->task['autoconfiguration_enabled'] : 0,

                    ],
                ];
            }

            // Input: SNMP enabled.
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
                'class'         => 'indented',
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

            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_skip_non_enabled_ifs',
                'class'         => 'indented',
                'block_content' => [
                    [
                        'label'     => __('Skip non-enabled interfaces'),
                        'arguments' => [
                            'name'   => 'snmp_skip_non_enabled_ifs',
                            'type'   => 'switch',
                            'value'  => (isset($this->task['snmp_enabled']) === true) ? $this->task['snmp_skip_non_enabled_ifs'] : 1,
                            'size'   => 25,
                            'return' => true,
                        ],
                    ],
                ],
            ];

            // SNMP Options pack v1.
            $form['inputs'][] = [
                'hidden'        => 1,
                'block_id'      => 'snmp_options_basic',
                'class'         => 'indented',
                'block_content' => [
                    [
                        'label'     => __('SNMP communities to try with').ui_print_help_tip(
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
                'class'         => 'indented',
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
                ],
            ];

            // Input: Enforce os detection.
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
                    'value'  => (isset($this->task['resolve_names'])) ? $this->task['resolve_names'] : 1,
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

            // Input: WMI enabled.
            $form['inputs'][] = [
                'label'     => __('WMI enabled'),
                'arguments' => [
                    'name'    => 'wmi_enabled',
                    'type'    => 'switch',
                    'value'   => (isset($this->task['wmi_enabled'])) ? $this->task['wmi_enabled'] : 0,
                    'return'  => true,
                    'onclick' => 'toggleAuth();',

                ],
            ];

             // AUTH CONFIGURATION.
            $show_auth = false;
            if ((isset($this->task['wmi_enabled']) && $this->task['wmi_enabled'] > 0)
                || (isset($this->task['rcmd_enabled']) && $this->task['rcmd_enabled'] > 0)
            ) {
                $show_auth = true;
            }

            include_once $config['homedir'].'/include/class/CredentialStore.class.php';
            $available_keys = CredentialStore::getKeys('WMI');
            if (check_acl($config['id_user'], 0, 'UM')) {
                $link_to_cs = '<a class="ext_link" href="'.ui_get_full_url(
                    'index.php?sec=gmodules&sec2=godmode/groups/group_list&tab=credbox'
                ).'" >';
                $link_to_cs .= __('No credentials available').', ';
                $link_to_cs .= strtolower(__('Manage credentials')).'</a>';
            } else {
                $link_to_cs = __('No credentials available');
            }

            if (count($available_keys) > 0) {
                $form['inputs'][] = [
                    'block_id'      => 'auth_block',
                    'class'         => 'indented',
                    'hidden'        => !$show_auth,
                    'block_content' => [
                        [
                            'label'     => __('Credentials to try with'),
                            'arguments' => [
                                'type'     => 'select',
                                'name'     => 'auth_strings[]',
                                'fields'   => CredentialStore::getKeys('WMI'),
                                'selected' => explode(
                                    ',',
                                    $this->task['auth_strings']
                                ),

                                'multiple' => true,
                                'class'    => 'select_multiple',
                            ],
                        ],
                    ],
                ];
            } else {
                $form['inputs'][] = [
                    'block_id'      => 'auth_block',
                    'class'         => 'indented',
                    'hidden'        => !$show_auth,
                    'block_content' => [
                        [
                            'label' => __('Credentials'),
                            'extra' => $link_to_cs,
                        ],
                    ],
                ];
            }

            ui_require_jquery_file('tag-editor.min');
            ui_require_jquery_file('caret.min');
            ui_require_css_file('jquery.tag-editor');

            $form['js'] = '
                $(\'#text-community\').tagEditor({
                    forceLowercase: false
                });

                function SNMPExtraShow(target) {
                    $("#snmp_options_basic").hide();
                    $("#snmp_options_skip_non_enabled_ifs").hide();
                    $("#snmp_options_v3").hide();
                    if (document.getElementsByName("snmp_enabled")[0].checked) {
                        $("#snmp_extra").show();
                        if (target == 3) {
                            $("#snmp_options_v3").show();
                        } else {
                            $("#snmp_options_basic").show();
                            $("#snmp_options_skip_non_enabled_ifs").show();
                        }
                    }
                }

                function extraSNMP() {
                    if (document.getElementsByName("snmp_enabled")[0].checked) {
                        SNMPExtraShow($("#snmp_version").val());
                        $("#snmp_extra").show();

                        // Enable snmp dependant checks
                        if (!document.getElementsByName("parent_recursion")[0].checked)
                            $("input[name=parent_recursion]").click();

                        if (!document.getElementsByName("parent_detection")[0].checked)
                            $("input[name=parent_detection]").click();

                        if (!document.getElementsByName("resolve_names")[0].checked)
                            $("input[name=resolve_names]").click();

                        if (!document.getElementsByName("vlan_enabled")[0].checked)
                            $("input[name=vlan_enabled]").click();
                    } else {
                        // Hide unusable sections
                        $("#snmp_extra").hide();
                        $("#snmp_options_basic").hide();
                        $("#snmp_options_skip_non_enabled_ifs").hide();
                        $("#snmp_options_v3").hide();

                        // Disable snmp dependant checks
                        if (document.getElementsByName("parent_recursion")[0].checked)
                            $("input[name=parent_recursion]").click();

                        if (document.getElementsByName("parent_detection")[0].checked)
                            $("input[name=parent_detection]").click();

                        if (document.getElementsByName("resolve_names")[0].checked)
                            $("input[name=resolve_names]").click();

                        if (document.getElementsByName("vlan_enabled")[0].checked)
                            $("input[name=vlan_enabled]").click();
                    }
                }

                function toggleAuth() {
                    if (document.getElementsByName("wmi_enabled")[0].checked
                        || (typeof document.getElementsByName("rcmd_enabled")[0] != "undefined"
                            && document.getElementsByName("rcmd_enabled")[0].checked)
                    ) {
                        $("#auth_block").show();
                    } else {
                        $("#auth_block").hide();
                    }
                }

                $(function() {
                    SNMPExtraShow($("#snmp_version").val());
                });

                $("#id_network_profile").select2({
                    placeholder: "'.__('Please select...').'"
                });
            ';

            if (enterprise_installed()) {
                // Feature configuration.
                $extra = enterprise_hook('hd_showextrainputs', [$this]);
                if (is_array($extra) === true) {
                    $form['inputs'] = array_merge(
                        $form['inputs'],
                        $extra['inputs']
                    );
                    $form['js'] .= $extra['js'];
                }
            }

            $form['form'] = [
                'method' => 'POST',
                'action' => $this->url.'&mode=netscan&page='.($this->page + 1).'&task='.$this->task['id_rt'],
                'id'     => 'form-netscan-feature',
            ];

            $this->printFormAsList($form);

            $output_form = $this->printInput(
                [
                    'name'       => 'submit-finish',
                    'label'      => __('Finish'),
                    'type'       => 'submit',
                    'attributes' => [
                        'icon' => 'next',
                        'form' => 'form-netscan-feature',
                    ],
                    'return'     => true,
                    'width'      => 'initial',
                ]
            );

            $output_form .= $this->printGoBackButton($this->url.'&mode=netscan&task='.$this->task['id_rt'].'&page='.($this->page - 1), true);

            html_print_action_buttons($output_form);
        }

        if ($this->page == 2) {
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
