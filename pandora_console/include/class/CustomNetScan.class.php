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

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * CustomNetScan. Host and devices child class.
 */
class CustomNetScan extends Wizard
{

    /**
     * Number of pages to control breadcrum.
     *
     * @var integer
     */
    public $MAXPAGES = 2;

    /**
     * Labels for breadcrum.
     *
     * @var array
     */
    public $pageLabels = [
        'Netscan Custom definition',
        'Netscan Custom script',
    ];


    /**
     * Constructor.
     *
     * @param integer $page      Page.
     * @param array   $breadcrum Breadcrum.
     *
     * @return void
     */
    public function __construct(int $page, array $breadcrum)
    {
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd'
        );

        $this->access = 'AW';
        $this->page = $page;
        $this->breadcrum = $breadcrum;
    }


    /**
     * Retrieves and validates information given by user in NetScan wizard.
     *
     * @return boolean Data OK or not.
     */
    public function parseNetScan()
    {
        global $config;

        if (isset($this->page) === true && $this->page === 0) {
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
        if (isset($this->page) === true && $this->page === 1) {
            $task_id = get_parameter('task', null);
            $taskname = io_safe_input(strip_tags(io_safe_output(get_parameter('taskname'))));
            $comment = get_parameter('comment', '');
            $server_id = get_parameter('id_recon_server', '');
            $id_group = get_parameter('id_group', '');
            $interval = get_parameter('interval', 0);

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
            } else if (isset($taskname) === true) {
                // Avoid double creation.
                $task = db_get_row_filter(
                    'trecon_task',
                    ['name' => $taskname]
                );

                if ($task !== false) {
                    $this->task = $task;
                    $this->msg = __('This task has been already defined. Please edit it or create a new one.');
                    return false;
                }
            }

            if ($task_id !== null
                && $taskname == null
                && $server_id == null
                && $id_group == null
                && $server == null
                && $datacenter == ''
                && $user == ''
                && $pass == ''
                && $encrypt == null
                && $interval == 0
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

                if ($id_group == '') {
                    $this->msg = __('You must select a valid group.');
                    return false;
                }

                // Assign fields.
                $this->task['name'] = $taskname;
                $this->task['description'] = $comment;
                $this->task['id_recon_server'] = $server_id;
                $this->task['id_group'] = $id_group;
                $this->task['interval_sweep'] = $interval;
                $this->task['type'] = DISCOVERY_HOSTDEVICES_CUSTOM;

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

            $id_recon_script = get_parameter('id_recon_script', null);
            $field1 = get_parameter('_field1_', '');
            $field2 = get_parameter('_field2_', '');
            $field3 = get_parameter('_field3_', '');
            $field4 = get_parameter('_field4_', '');

            // Get macros.
            $macros = get_parameter('macros', null);

            if (empty($macros) === false) {
                $macros = json_decode(
                    base64_decode($macros),
                    true
                );

                foreach ($macros as $k => $m) {
                    $macros[$k]['value'] = get_parameter($m['macro'], '');
                }
            }

            $this->task['id_recon_script'] = $id_recon_script;
            $this->task['macros'] = io_json_mb_encode($macros);
            $this->task['field1'] = $field1;
            $this->task['field2'] = $field2;
            $this->task['field3'] = $field3;
            $this->task['field4'] = $field4;

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

        return false;
    }


    /**
     * Run function. It will be call into HostsDevices class.
     *      Page 0: Upload form.
     *      Page 1: Task resume.
     *
     * @return void
     */
    public function runCustomNetScan()
    {
        global $config;

        if (!check_acl($config['id_user'], 0, $this->access)) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Custom Net Scan.'
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
                    'action' => $this->url.'&mode=customnetscan&page='.($this->page - 1).'&task='.$this->task['id_rt'],
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
                                'icon' => 'cancel',
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
                $form['form']['action'] = $this->url.'&mode=customnetscan&page='.($this->page - 1);
            }

            $this->printForm($form);
            return null;
        }

        $run_url = 'index.php?sec=gservers&sec2=godmode/servers/discovery';

        $task_url = '';
        if (isset($this->task['id_rt']) === true) {
            $task_url = '&task='.$this->task['id_rt'];
        }

        $breadcrum = [
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                'label' => 'Discovery',
            ],
            [
                'link'  => $run_url.'&wiz=hd',
                'label' => __('Host & Devices'),
            ],
        ];

        for ($i = 0; $i < $this->MAXPAGES; $i++) {
            $breadcrum[] = [
                'link'     => $run_url.'&wiz=hd&mode=customnetscan&page='.$i.$task_url,
                'label'    => __($this->pageLabels[$i]),
                'selected' => (($i == $this->page) ? 1 : 0),
            ];
        }

        if ($this->page < $this->MAXPAGES) {
            // Avoid to print header out of wizard.
            $this->prepareBreadcrum($breadcrum);

            // Header.
            ui_print_page_header(__('NetScan Custom'), '', false, '', true, '', false, '', GENERIC_SIZE_TEXT, '', $this->printHeader(true));
        }

        $task_url = '';
        if (isset($this->task['id_rt'])) {
            $task_url = '&task='.$this->task['id_rt'];
        }

        $breadcrum[] = [
            'link'  => $run_url.'&wiz=hd',
            'label' => __($this->label),
        ];
        for ($i = 0; $i < $this->maxPagesNetScan; $i++) {
            $breadcrum[] = [
                'link'     => $run_url.'&wiz=hd&mode=customnetscan&page='.$i.$task_url,
                'label'    => $this->pageLabelsNetScan[$i],
                'selected' => (($i == $this->page) ? 1 : 0),
            ];
        }

        if ($this->page < $this->maxPagesNetScan) {
            // Avoid to print header out of wizard.
            $this->prepareBreadcrum($breadcrum);

            // Header.
            ui_print_page_header(__('NetScan Custom'), '', false, '', true, '', false, '', GENERIC_SIZE_TEXT, '', $this->printHeader(true));
        }

        if (isset($this->page) === true
            && $this->page !== 0
            && isset($this->task['id_rt']) === false
        ) {
            // Error.
            ui_print_error_message(
                __('Internal error, please re-run this wizard.')
            );

            $form = [
                'form'   => [
                    'method' => 'POST',
                    'action' => $this->url.'&mode=customnetscan&page=0',
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
                                'icon' => 'cancel',
                                'mode' => 'secondary',
                            ],
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
        if (isset($this->page) === true || $this->page == 0) {
            if (isset($this->page) === false
                || $this->page == 0
            ) {
                $form = [];

                // Input task name.
                $form['inputs'][] = [
                    'label'     => __('Task name'),
                    'arguments' => [
                        'name'  => 'taskname',
                        'value' => $this->task['name'],
                        'type'  => 'text',
                        'size'  => 50,
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

                // Input task description.
                $form['inputs'][] = [
                    'label'     => __('Comment'),
                    'arguments' => [
                        'name'  => 'comment',
                        'value' => $this->task['description'],
                        'type'  => 'text',
                        'size'  => 50,
                    ],
                ];

                // Input Discovery Server.
                $form['inputs'][] = [
                    'label'     => __('Discovery server').ui_print_help_tip(
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

                // Input Group.
                $form['inputs'][] = [
                    'label'     => __('Group'),
                    'arguments' => [
                        'name'           => 'id_group',
                        'returnAllGroup' => false,
                        'privilege'      => $this->access,
                        'type'           => 'select_groups',
                        'selected'       => $this->task['id_group'],
                        'return'         => true,
                        'size'           => '400px',
                    ],
                ];

                // Interval and schedules.
                $interv_manual = 0;
                if ((int) $this->task['interval_sweep'] == 0) {
                    $interv_manual = 1;
                }

                // Schedule.
                $form['inputs'][] = [
                    'label'           => __('Interval').ui_print_help_tip(
                        __('Manual interval means that it will be executed only On-demand').', '.__('The minimum recomended interval for Recon Task is 5 minutes'),
                        true
                    ),
                    'class'           => 'input-interval',
                    'extra-container' => true,
                    'arguments'       => [
                        'type'     => 'select',
                        'selected' => $interv_manual,
                        'fields'   => [
                            0 => __('Defined'),
                            1 => __('Manual'),
                        ],
                        'name'     => 'interval_manual_defined',
                        'return'   => true,
                    ],
                    'extra'           => '<span id="interval_manual_container">'.html_print_extended_select_for_time(
                        'interval',
                        $this->task['interval_sweep'],
                        '',
                        '',
                        '0',
                        false,
                        true,
                        false,
                        false
                    ).'</span>',
                ];

                $str = __('Next');

                if (isset($this->task['id_rt']) === true) {
                    $str = __('Update and continue');
                }

                $task_url = '';
                if (isset($this->task['id_rt'])) {
                    $task_url = '&task='.$this->task['id_rt'];
                }

                $form['form'] = [
                    'method' => 'POST',
                    'action' => $this->url.'&mode=customnetscan&page='.($this->page + 1).$task_url,
                    'id'     => 'form-netscan-custom-definition',
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
                ';

                // XXX: Could be improved validating inputs before continue (JS)
                // Print NetScan page 0.
                $this->printFormAsList($form);

                html_print_action_buttons(
                    $this->printInput(
                        [
                            'name'       => 'submit',
                            'label'      => $str,
                            'type'       => 'submit',
                            'attributes' => [
                                'icon' => 'next',
                                'form' => 'form-netscan-custom-definition',
                            ],
                            'return'     => true,
                            'width'      => 'initial',
                        ]
                    )
                );
            }
        }

        if (isset($this->page) === true && $this->page === 1) {
            // Recon script.
            $form['inputs'][] = [
                'label'     => __('Recon script'),
                'arguments' => [
                    'type'     => 'select_from_sql',
                    'sql'      => sprintf(
                        'SELECT id_recon_script, name FROM trecon_script ORDER BY name'
                    ),
                    'name'     => 'id_recon_script',
                    'selected' => $this->task['id_recon_script'],
                    'return'   => true,
                ],
            ];

            $form['inputs'][] = [
                'hidden'    => 1,
                'arguments' => [
                    'type'  => 'hidden',
                    'name'  => 'task',
                    'value' => $this->task['id_rt'],
                ],
            ];

            $form['inputs'][] = [
                'hidden'    => 1,
                'arguments' => [
                    'type'   => 'hidden_extended',
                    'name'   => 'macros',
                    'value'  => base64_encode($this->task['macros']),
                    'return' => true,
                ],
            ];

            // Explanation.
            $explanation = db_get_value(
                'description',
                'trecon_script',
                'id_recon_script',
                $this->task['id_recon_script']
            );

            $form['inputs'][] = [
                'label'     => __('Explanation').'<span id="spinner_recon_script" style="display: none">'.html_print_image('images/spinner.gif', true).'</span>',
                'arguments' => [
                    'type'    => 'textarea',
                    'rows'    => 4,
                    'columns' => 60,
                    'name'    => 'explanation',
                    'value'   => $explanation,
                    'return'  => true,
                    'class'   => 'w388px discovery_textarea_input',
                    'style'   => 'width: 388px',
                ],
            ];

            $form['inputs'][] = [
                'hidden'    => 1,
                'id'        => 'table_recon-macro_field',
                'label'     => '<b>'.__('macro_desc').'</b>'.ui_print_help_tip('macro_help', true),
                'arguments' => [
                    'name'   => 'macro_name',
                    'value'  => 'macro_value',
                    'type'   => 'text',
                    'size'   => 50,
                    'return' => true,

                ],
            ];

            if (empty($this->task['macros']) === false) {
                $macros = json_decode($this->task['macros'], true);
                foreach ($macros as $k => $m) {
                    $label_macro = '';
                    $label_macro .= '<b>'.$m['desc'].'</b>';
                    if (!empty($m['help'])) {
                        $label_macro .= ui_print_help_tip(
                            $m['help'],
                            true
                        );
                    }

                    if ($m['hide']) {
                        $form['inputs'][] = [
                            'label'     => $label_macro,
                            'id'        => 'table_recon-macro'.$m['macro'],
                            'class'     => 'macro_field',
                            'arguments' => [
                                'name'   => $m['macro'],
                                'value'  => $m['value'],
                                'type'   => 'password',
                                'size'   => 100,
                                'return' => true,
                            ],
                        ];
                    } else {
                        $form['inputs'][] = [
                            'label'     => $label_macro,
                            'id'        => 'table_recon-macro'.$m['macro'],
                            'class'     => 'macro_field',
                            'arguments' => [
                                'name'   => $m['macro'],
                                'value'  => $m['value'],
                                'type'   => 'text',
                                'size'   => 100,
                                'return' => true,
                            ],
                        ];
                    }
                }
            }

            $form['form'] = [
                'method' => 'POST',
                'action' => $this->url.'&mode=customnetscan&page='.($this->page + 1).'&task='.$this->task['id_rt'],
                'id'     => 'form-netscan-custom-script',
            ];

            $id_task = (isset($this->task['id_rt']) === true) ? $this->task['id_rt'] : 0;

            $url_ajax = $config['homeurl'].'ajax.php';

            $change = '';
            if (empty($this->task['macros']) !== false) {
                $change = '.change();';
            }

            $form['js'] = '
                $("select#id_recon_script").change(function() {
                    get_explanation_recon_script($(this).val(), "'.$id_task.'", "'.$url_ajax.'");
                })'.$change;

            $this->printFormAsList($form);

            html_print_action_buttons(
                $this->printInput(
                    [
                        'name'       => 'submit',
                        'label'      => __('Finish'),
                        'type'       => 'submit',
                        'attributes' => [
                            'icon' => 'update',
                            'form' => 'form-netscan-custom-script',
                        ],
                        'return'     => true,
                        'width'      => 'initial',
                    ]
                )
            );
        }

        if (isset($this->page) === true && $this->page === 2) {
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

        ui_require_javascript_file('pandora_modules');
    }


}
