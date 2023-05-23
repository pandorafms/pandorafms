<?php
/**
 * Welcome to Pandora FMS feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage New Installation Welcome Window
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

// Begin.
global $config;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * Class WelcomeWindow.
 */
class WelcomeWindow extends Wizard
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'loadWelcomeWindow',
        'cancelWelcome',
    ];

    /**
     * Tasks.
     *
     * @var array
     */
    private $tasks = [
        'welcome_mail_configured',
        'welcome_id_agent',
        'welcome_module',
        'welcome_alert',
        'welcome_task',
    ];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Current step.
     *
     * @var integer
     */
    public $step;

    /**
     * Current agent (created example).
     *
     * @var integer
     */
    public $agent;


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function error($msg)
    {
        echo json_encode(
            ['error' => $msg]
        );
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod($method)
    {
        global $config;

        // Check access.
        check_login();

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param boolean $must_run        Must run or not.
     * @param string  $ajax_controller Controller.
     *
     * @return object
     * @throws Exception On error.
     */
    public function __construct(
        bool $must_run=false,
        $ajax_controller='include/ajax/welcome_window'
    ) {
        $this->ajaxController = $ajax_controller;

        if ($this->initialize($must_run) !== true) {
            throw new Exception('Must not be shown');
        }

        return $this;
    }


    /**
     * Main method.
     *
     * @return void
     */
    public function run()
    {
        ui_require_css_file('new_installation_welcome_window');
        echo '<div id="welcome_modal_window" class="invisible"; >';

        ?>
    <script type="text/javascript">
        load_modal({
            target: $('#welcome_modal_window'),
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            modal: {
                title: "<?php echo __('Welcome to').' '.io_safe_output(get_product_name()); ?>",
                cancel: '<?php echo __('Do not show anymore'); ?>',
                ok: '<?php echo __('Close'); ?>'
            },
            onshow: {
                page: '<?php echo $this->ajaxController; ?>',
                method: 'loadWelcomeWindow',
            },
            oncancel: {
                page: '<?php echo $this->ajaxController; ?>',
                title: "<?php echo __('Cancel Configuration Window'); ?>",
                method: 'cancelWelcome',
                confirm: function (fn) {
                    confirmDialog({
                        title: '<?php echo __('Are you sure?'); ?>',
                        message: '<?php echo __('Are you sure you want to cancel this tutorial?'); ?>',
                        ok: '<?php echo __('OK'); ?>',
                        cancel: '<?php echo __('Cancel'); ?>',
                        onAccept: function() {
                            // Continue execution.
                            fn();
                        }
                    })
                }
            }
        });

    </script>

        <?php
        echo '</div>';
    }


    /**
     * Method to cancel welcome modal window.
     *
     * @return void
     */
    public function cancelWelcome()
    {
        // Config update value.
        $this->setStep(WELCOME_FINISHED);
    }


    /**
     * Return current step.
     *
     * @return integer Step.
     */
    public function getStep(): int
    {
        global $config;
        $this->step = $config['welcome_state'];

        // Get step available.
        if (empty($config['welcome_mail_configured']) === true
            && get_parameter('sec2') == 'godmode/setup/setup'
            && get_parameter('section', '') == 'general'
            && get_parameter('update_config', false) !== false
        ) {
            $this->step = W_CONFIGURE_MAIL;
        } else if (empty($config['welcome_id_agent']) === true) {
            $this->step = W_CREATE_AGENT;
        } else if (empty($config['welcome_module']) === true) {
            $this->step = W_CREATE_MODULE;
        } else if (empty($config['welcome_alert']) === true) {
            $this->step = W_CREATE_ALERT;
        } else if (empty($config['welcome_task']) === true) {
            $this->step = W_CREATE_TASK;
        }

        return $this->step;
    }


    /**
     * Sets current step.
     *
     * @param integer $step Current step.
     *
     * @return void
     */
    public function setStep(int $step)
    {
        $this->step = $step;
        config_update_value('welcome_state', $step);
    }


    /**
     * Completes current step.
     *
     * @return void
     */
    public function completeStep()
    {
        switch ($this->step) {
            case W_CONFIGURE_MAIL:
                config_update_value('welcome_mail_configured', true);
            break;

            case W_CREATE_AGENT:
                config_update_value('welcome_id_agent', true);
            break;

            case W_CREATE_MODULE:
                config_update_value('welcome_module', true);
            break;

            case W_CREATE_ALERT:
                config_update_value('welcome_alert', true);
            break;

            case W_CREATE_TASK:
                config_update_value('welcome_task', true);
            break;

            default:
                // Ignore.
            break;
        }

    }


    /**
     * Check if all tasks had been completed.
     *
     * @return boolean All completed or not.
     */
    public function checkAllTasks()
    {
        global $config;

        foreach ($this->tasks as $t) {
            if (empty($config[$t]) === true) {
                return false;
            }
        }

        return true;
    }


    /**
     * Retrieve current welcome agent id.
     *
     * @return integer Agent id (created).
     */
    public function getWelcomeAgent()
    {
        global $config;

        return (isset($config['welcome_id_agent']) === true) ? $config['welcome_id_agent'] : '';
    }


    /**
     * Saves current welcome agent (latest created).
     *
     * @param integer $id_agent Agent id.
     *
     * @return void
     */
    public function setWelcomeAgent(int $id_agent)
    {
        config_update_value('welcome_id_agent', $id_agent);
    }


    /**
     * Loads a welcome window form
     *
     * @return​ ​string HTML code for form.
     *
     * @return void Runs loadWelcomeWindow (AJAX).
     */
    public function loadWelcomeWindow()
    {
        global $config;
        $flag_task = false;

        $form = [
            'action'   => '#',
            'id'       => 'welcome_form',
            'onsubmit' => 'this.dialog("close");',
            'class'    => 'modal',
        ];

        $logo_url = 'images/custom_logo/pandora_logo_head_white_bg.png';

        if (enterprise_installed() === true) {
            $logo_url = ENTERPRISE_DIR.'/'.$logo_url;
        }

        $inputs = [
            [
                'class'         => 'white_box',
                'block_content' => [
                    [
                        'class'     => 'centered_full',
                        'arguments' => [
                            'type'   => 'image',
                            'src'    => $logo_url,
                            'value'  => 1,
                            'return' => true,
                        ],
                    ],
                ],
            ],
        ];

        if (check_acl($config['id_user'], 0, 'PM')) {
            $flag_um = false;
            $flag_cm = false;
            $flag_su = false;
            $flag_lv = false;

            $btn_update_manager_class = ' pending';
            $btn_configure_mail_class = ' pending';
            $btn_servers_up_class = ' pending';
            $btn_license_valid_class = ' pending';

            $li_update_manager_class = 'row_grey';
            $li_configure_mail_class = 'row_grey';
            $li_servers_up_class = 'row_grey';
            $li_license_valid_class = 'row_grey';

            if ($config['pandora_uid'] === 'ONLINE') {
                $btn_update_manager_class = ' completed';
                $li_update_manager_class = 'row_green';
                $flag_um = true;
            }

            if (empty($config['welcome_mail_configured']) === false) {
                $btn_configure_mail_class = ' completed';
                $flag_cm = true;
            }

            include_once 'include/functions_servers.php';
            if (check_all_servers_up() === true) {
                $btn_servers_up_class = ' completed';
                $li_servers_up_class = 'row_green';
                $flag_su = true;
            }

            if (enterprise_installed()) {
                $license_valid = true;
                enterprise_include_once('include/functions_license.php');
                $license = enterprise_hook('license_get_info');
                $days_to_expiry = ((strtotime($license['expiry_date']) - time()) / (60 * 60 * 24));
                if ($license === ENTERPRISE_NOT_HOOK || $days_to_expiry <= 30) {
                    $license_valid = false;
                }

                if ($license_valid === true) {
                    $btn_license_valid_class = ' completed';
                    $li_license_valid_class = 'row_green';
                    $flag_lv = true;
                }
            }

            $inputs[] = [
                'wrapper'       => 'div',
                'block_id'      => 'div_diagnosis',
                'class'         => 'flex-row flex-items-center w98p ',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Mini-diagnosis'),
                        'arguments' => [
                            'class' => 'first_lbl',
                            'name'  => 'lbl_diagnosis',
                            'id'    => 'lbl_diagnosis',
                        ],
                    ],
                ],
            ];

            if ($flag_um === false || $flag_cm === false || $flag_su === false || $flag_lv === false) {
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_update_manager',
                    'class'         => 'hole flex-row flex-items-center w98p '.$li_update_manager_class,
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('Verification update manager register'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_update_manager',
                                'id'    => 'lbl_update_manager',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => '',
                                'type'       => 'button',
                                'attributes' => [
                                    'class' => (empty($btn_update_manager_class) === false) ? $btn_update_manager_class : 'invisible_important',
                                    'mode'  => 'onlyIcon',
                                ],
                                'name'       => 'btn_update_manager_conf',
                                'id'         => 'btn_update_manager_conf',
                            ],
                        ],
                    ],
                ];
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_configure_mail',
                    'class'         => 'hole flex-row flex-items-center w98p '.$li_configure_mail_class,
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('Please ensure mail configuration matches your needs'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_create_agent',
                                'id'    => 'lbl_create_agent',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => '',
                                'type'       => 'button',
                                'attributes' => [
                                    'class' => (empty($btn_configure_mail_class) === false) ? $btn_configure_mail_class : 'invisible_important',
                                    'mode'  => 'onlyIcon',
                                ],
                                'name'       => 'btn_email_conf',
                                'id'         => 'btn_email_conf',
                            ],
                        ],
                    ],
                ];
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_servers_up',
                    'class'         => 'hole flex-row flex-items-center w98p '.$li_servers_up_class,
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('All servers up'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_servers_up',
                                'id'    => 'lbl_servers_up',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => '',
                                'type'       => 'button',
                                'attributes' => [
                                    'class' => (empty($btn_servers_up_class) === false) ? $btn_servers_up_class : 'invisible_important',
                                    'mode'  => 'onlyIcon',
                                ],
                                'name'       => 'btn_servers_up_conf',
                                'id'         => 'btn_servers_up_conf',
                            ],
                        ],
                    ],
                ];
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_license_valid',
                    'class'         => 'hole flex-row flex-items-center w98p '.$li_license_valid_class,
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('Valid license verification and expiration greater than 30 days'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_license_valid',
                                'id'    => 'lbl_license_valid',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => '',
                                'type'       => 'button',
                                'attributes' => [
                                    'class' => (empty($btn_license_valid_class) === false) ? $btn_license_valid_class : 'invisible_important',
                                    'mode'  => 'onlyIcon',
                                ],
                                'name'       => 'btn_license_valid_conf',
                                'id'         => 'btn_license_valid_conf',
                            ],
                        ],
                    ],
                ];
            } else {
                $key = db_get_value_sql('SELECT `value` FROM tupdate_settings WHERE `key` = "customer_key"');
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_all_correct',
                    'class'         => 'hole flex-row flex-items-center w98p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('It seems that your Pandora FMS is working correctly and registered with ID:<br> #'.$key.'.<br>For more information use the self-diagnosis tool.'),
                            'arguments' => [
                                'class' => 'first_lbl w98p',
                                'name'  => 'lbl_all_correct',
                                'id'    => 'lbl_all_correct',
                            ],
                        ],
                    ],
                ];
            }

            if ($flag_um === false || $flag_cm === false || $flag_su === false || $flag_lv === false) {
                $flag_task = true;
            }
        }

        // Task to do.
        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_task_todo',
            'class'         => 'flex-row flex-items-center w98p',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Task to perform'),
                    'arguments' => [
                        'class' => 'first_lbl',
                        'name'  => 'lbl_task_todo',
                        'id'    => 'lbl_task_todo',
                    ],
                ],
            ],
        ];

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_wizard_agent',
            'class'         => 'hole flex-row flex-items-center w98p row_grey',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Wizard install agent'),
                    'arguments' => [
                        'class' => 'first_lbl row_grey',
                        'name'  => 'lbl_wizard_agent',
                        'id'    => 'lbl_wizard_agent',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => '',
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'completed',
                            'mode'  => 'onlyIcon',
                        ],
                        'name'       => 'btn_wizard_agent_conf',
                        'id'         => 'btn_wizard_agent_conf',
                    ],
                ],
            ],
        ];

        $status_webserver = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_WEB], 'status')['status'];
        $check_web_color = 'row_grey';
        if ($status_webserver === '1') {
            $check_web_color = 'row_green';
        }

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_check_web',
            'class'         => 'hole flex-row flex-items-center w98p '.$check_web_color,
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Create check web'),
                    'arguments' => [
                        'class' => 'first_lbl row_grey',
                        'name'  => 'lbl_check_web',
                        'id'    => 'lbl_check_web',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => '',
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'completed',
                            'mode'  => 'onlyIcon',
                        ],
                        'name'       => 'btn_check_web_conf',
                        'id'         => 'btn_check_web_conf',
                    ],
                ],
            ],
        ];

        $status_newtwork = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_NETWORK], 'status')['status'];
        $status_pluggin = db_get_row_filter('tserver', ['server_type' => SERVER_TYPE_PLUGIN], 'status')['status'];
        $check_connectivity = 'row_grey';
        if ($status_newtwork === '1' && $status_pluggin === '1') {
            $check_connectivity = 'row_green';
        }

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_check_connectivity',
            'class'         => 'hole flex-row flex-items-center w98p '.$check_connectivity,
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Create basic connectivity'),
                    'arguments' => [
                        'class' => 'first_lbl row_grey',
                        'name'  => 'lbl_check_connectivity',
                        'id'    => 'lbl_check_connectivity',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => '',
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'completed',
                            'mode'  => 'onlyIcon',
                        ],
                        'name'       => 'btn_check_connectivity_conf',
                        'id'         => 'btn_check_connectivity_conf',
                    ],
                ],
            ],
        ];

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_check_net',
            'class'         => 'hole flex-row flex-items-center w98p row_green',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Create basic net'),
                    'arguments' => [
                        'class' => 'first_lbl row_grey',
                        'name'  => 'lbl_check_net',
                        'id'    => 'lbl_check_net',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => '',
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'completed',
                            'mode'  => 'onlyIcon',
                        ],
                        'name'       => 'btn_check_net_conf',
                        'id'         => 'btn_check_net_conf',
                    ],
                ],
            ],
        ];

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_check_mail_alert',
            'class'         => 'hole flex-row flex-items-center w98p row_green',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => __('Create Alert Mail'),
                    'arguments' => [
                        'class' => 'first_lbl row_grey',
                        'name'  => 'lbl_check_mail_alert',
                        'id'    => 'lbl_check_mail_alert',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => '',
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'completed',
                            'mode'  => 'onlyIcon',
                        ],
                        'name'       => 'btn_check_mail_alert_conf',
                        'id'         => 'btn_check_mail_alert_conf',
                    ],
                ],
            ],
        ];

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        $output .= $this->loadJS($flag_task);
        echo $output;

        // Ajax methods does not continue.
        exit();
    }


    /**
     * This function acts as a constructor. Receive the condition to check with
     * the global config (welcome_state) if continues
     *
     * @param boolean $must_run Must be run or not (check register.php).
     *
     * @return boolean True if initialized or false if must not run.
     */
    public function initialize($must_run)
    {
        global $config;

        if (isset($config['welcome_state']) === false) {
            $this->completeStep();
            $this->setStep(W_CONFIGURE_MAIL);
        }

        // Check current page.
        $sec2 = get_parameter('sec2', '');

        // Search also does not fulfill sec2.
        if (empty($sec2) === true) {
            $sec2 = get_parameter('keywords', '');
        }

        if ($must_run === false
            || ((int) $config['welcome_state']) === WELCOME_FINISHED
        ) {
            // Do not show if finished.
            return false;
        }

        $this->step = $this->getStep();
        $this->agent = $this->getWelcomeAgent();

        /*
         * Configure mail. Control current flow.
         *
         * On empty sec2: show current step.
         * On setup page: do not show.
         * After mail configuration: enable agent step.
         */

        if ($this->step === W_CONFIGURE_MAIL) {
            if ($sec2 === 'godmode/setup/setup'
                && get_parameter('section', '') == 'general'
                && get_parameter('update_config', false) !== false
            ) {
                // Mail configuration have been processed.
                $this->step = W_CONFIGURE_MAIL;
                $this->completeStep();
                $this->setStep(W_CREATE_AGENT);
            } else if ($sec2 === 'godmode/setup/setup'
                && get_parameter('section', '') === 'general'
            ) {
                // Mail configuration is being processed.
                return false;
            } else if (empty($sec2) === true) {
                // Show main page.
                return true;
            }
        }

        /*
         * Create agent. Control current flow.
         *
         * Welcome wizard is shown if you create your first agent.
         *
         */

        if (empty($config['welcome_id_agent']) === true) {
            // Create agent is pending.
            if ($sec2 === 'godmode/agentes/configurar_agente'
                && get_parameter('create_agent', false) !== false
            ) {
                // Agent have been created. Store.
                // Here complete step is not needed because is already done
                // by setWelcomeAgent.
                $this->setWelcomeAgent(
                    // Non yet processed. Get next available ID.
                    db_get_value_sql(
                        sprintf(
                            'SELECT AUTO_INCREMENT
                            FROM information_schema.TABLES
                            WHERE TABLE_SCHEMA = "%s"
                            AND TABLE_NAME = "%s"',
                            $config['dbname'],
                            'tagente'
                        )
                    )
                );
                $this->setStep(W_CREATE_MODULE);
                return true;
            } else if ($sec2 === 'godmode/agentes/configurar_agente') {
                // Agent is being created.
                return false;
            } else if (empty($sec2) === true) {
                // If at main page, show welcome.
                return true;
            }
        } else if ($this->step === W_CREATE_AGENT) {
            $this->step = W_CREATE_MODULE;
        }

        /*
         * Create module. Control current flow.
         *
         * On empty sec2: show current step.
         * On module creation page: do not show.
         * After module creation: enable alert step.
         */

        if ($this->step === W_CREATE_MODULE) {
            // Create module is pending.
            if ($sec2 === 'godmode/agentes/configurar_agente'
                && get_parameter('tab', '') === 'module'
                && get_parameter('create_module', false) !== false
            ) {
                // Module have been created.
                $this->completeStep();
                $this->setStep(W_CREATE_ALERT);
                return true;
            } else if ($sec2 === 'godmode/agentes/configurar_agente'
                && get_parameter('tab', '') === 'module'
            ) {
                // Module is being created.
                return false;
            } else if (empty($sec2) === true) {
                // If at main page, show welcome.
                return true;
            }
        }

        /*
         * Create alert. Control current flow.
         *
         * On empty sec2: show current step.
         * On alert creation page: do not show.
         * After alert creation: enable discovery task step.
         */

        if ($this->step === W_CREATE_ALERT) {
            // Create alert is pending.
            if ($sec2 === 'godmode/agentes/configurar_agente'
                && get_parameter('tab', '') === 'alert'
                && get_parameter('create_alert', false) !== false
            ) {
                // Alert have been created.
                $this->completeStep();
                $this->setStep(W_CREATE_TASK);
                return true;
            } else if ($sec2 === 'godmode/agentes/configurar_agente'
                && get_parameter('tab', '') === 'alert'
            ) {
                // Alert is being created.
                return false;
            } else if (empty($sec2) === true) {
                // If at main page, show welcome.
                return true;
            }
        }

        /*
         * Create discovery task. Control current flow.
         *
         * On empty sec2: show current step.
         * On discovery task creation page: do not show.
         * After discovery task creation: finish.
         */

        // Create Discovery task is pending.
        // Host&Devices finishses on page 2.
        if ($sec2 === 'godmode/servers/discovery'
            && (int) get_parameter('page') === 2
        ) {
            // Discovery task have been created.
            $this->step = W_CREATE_TASK;
            $this->completeStep();

            // Check if all other tasks had been completed.
            if ($this->checkAllTasks() === true) {
                // Finished! do not show.
                $this->setStep(WELCOME_FINISHED);
                return false;
            }

            return true;
        } else if ($sec2 == 'godmode/servers/discovery') {
            // Discovery task is being created.
            return false;
        }

        // Check if all other tasks had been completed.
        if ($this->checkAllTasks() === true) {
            // Finished! do not show.
            $this->setStep(WELCOME_FINISHED);
            return false;
        } else if (empty($sec2) === true) {
            // Pending tasks.
            return true;
        }

        if ($this->step === WELCOME_FINISHED) {
            // Welcome tutorial finished.
            return false;
        }

        // Return a reference to the new object.
        return false;
    }


    /**
     * Load JS content.
     * function that enables the functions to the buttons when its action is
     *  completed.
     * Assign the url of each button.
     *
     * @return string HTML code for javascript functionality.
     */
    public function loadJS($flag_task=false)
    {
        ob_start();
        ?>
    <script type="text/javascript">
        $('#div_all_correct').children().attr('class','w100p').attr('style', 'overflow-wrap: break-word;');
        <?php if ($flag_task === true) { ?>
            document.getElementById("button-btn_update_manager_conf").setAttribute(
                'onclick',
                'configureUpdateManager()'
            );
            document.getElementById("button-btn_email_conf").setAttribute(
                'onclick',
                'configureEmail()'
            );
            document.getElementById("button-btn_servers_up_conf").setAttribute(
                'onclick',
                'serversUp()'
            );
            document.getElementById("button-btn_license_valid_conf").setAttribute(
                'onclick',
                'messageLicense()'
            );
        <?php } ?>

        // Task to do buttons.
        document.getElementById("button-btn_wizard_agent_conf").setAttribute(
            'onclick',
            'deployAgent()'
        );

        document.getElementById("button-btn_check_web_conf").setAttribute(
            'onclick',
            'openCreateModulesDialog()'
        );

        document.getElementById("button-btn_check_connectivity_conf").setAttribute(
            'onclick',
            'openCreateConnectivityDialog()'
        );

        document.getElementById("button-btn_check_net_conf").setAttribute(
            'onclick',
            'openCreateBasicNetDialog()'
        );

        document.getElementById("button-btn_check_mail_alert_conf").setAttribute(
            'onclick',
            'openCreateAlertMailDialog()'
        );

        function configureUpdateManager() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online'); ?>';
        }

        function configureEmail() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table3'); ?>';
        }

        function serversUp() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60'); ?>';
        }

        function messageLicense() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=message_list&sec2=operation/messages/message_list'); ?>';
        }

        function deployAgent() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&show_deploy_agent=1'); ?>';
        }

        function openCreateModulesDialog() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=wizard&sec2=godmode/wizards/task_to_perform&create_modules_dialog=1'); ?>';
        }

        function openCreateConnectivityDialog() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=wizard&sec2=godmode/wizards/task_to_perform&create_connectivity_dialog=1'); ?>';
        }

        function openCreateBasicNetDialog() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=wizard&sec2=godmode/wizards/task_to_perform&create_net_scan_dialog=1'); ?>';
        }

        function openCreateAlertMailDialog() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=wizard&sec2=godmode/wizards/task_to_perform&create_alert_mail_dialog=1'); ?>';
        }

        function cierre_dialog(){
            this.dialog("close");
        }
    </script>
        <?php
        return ob_get_clean();
    }


}
