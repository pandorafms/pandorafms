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

        $btn_configure_mail_class = 'pending';
        $btn_create_agent_class = 'pending';
        $btn_create_module_class = '';
        $btn_create_alert_class = '';
        $btn_create_discovery_class = 'pending';

        $li_configure_mail_class = 'row_green';
        $li_create_agent_class = 'row_green';
        $li_create_module_class = 'row_grey';
        $li_create_alert_class = 'row_grey';
        $li_create_discovery_class = 'row_green';

        if (empty($config['welcome_mail_configured']) === false) {
            $btn_configure_mail_class = ' completed';
        }

        if (empty($config['welcome_id_agent']) === false) {
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' pending';
            $li_create_module_class = 'row_green';
        }

        if (empty($config['welcome_module']) === false) {
            $btn_create_module_class = ' completed';
            $btn_create_alert_class = ' pending';
            $li_create_module_class = 'row_green';
        }

        if (empty($config['welcome_alert']) === false) {
            $btn_create_alert_class = ' completed';
            $li_create_alert_class = 'row_green';
        }

        if (empty($config['welcome_task']) === false) {
            $btn_create_discovery_class = ' completed';
        }

        if ((int) $config['welcome_state'] === WELCOME_FINISHED) {
            // Nothing left to do.
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' completed';
            $btn_create_alert_class = ' completed';
            $btn_create_discovery_class = ' completed';
            $li_create_module_class = 'row_green';
            $li_create_alert_class = 'row_green';
        }

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
            [
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
            ],
            [
                'label'     => 'Learn to monitor',
                'class'     => 'extra',
                'arguments' => [
                    'class' => 'class="lbl_learn"',
                    'name'  => 'lbl_learn',
                    'id'    => 'lbl_learn',
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_create_agent',
                'class'         => 'learn_content_indented flex-row flex-items-center w98p '.$li_create_agent_class,
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Create an agent'),
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
                                'class' => (empty($btn_create_agent_class) === false) ? $btn_create_agent_class : 'invisible_important',
                                'mode'  => 'onlyIcon',
                            ],
                            'name'       => 'btn_create_agent',
                            'id'         => 'btn_create_agent',
                        ],
                    ],
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_indented flex-row flex-items-center w98p '.$li_create_module_class,
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Create a module to check if an agent is online'),
                        'arguments' => [
                            'class' => 'second_lbl',
                            'name'  => 'lbl_check_agent',
                            'id'    => 'lbl_check_agent',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => '',
                            'type'       => 'button',
                            'attributes' => [
                                'class' => (empty($btn_create_module_class) === false) ? $btn_create_module_class : 'invisible_important',
                                'mode'  => 'onlyIcon',
                            ],
                            'name'       => 'btn_create_module',
                            'id'         => 'btn_create_module',
                        ],
                    ],
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'hole learn_content_indented flex-row flex-items-center w98p '.$li_create_alert_class,
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Be warned if something is wrong, create an alert on the module'),
                        'arguments' => [
                            'class' => 'second_lbl',
                            'name'  => 'lbl_create_alert',
                            'id'    => 'lbl_create_alert',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => '',
                            'type'       => 'button',
                            'attributes' => [
                                'class' => (empty($btn_create_alert_class) === false) ? $btn_create_alert_class : 'invisible_important',
                                'mode'  => 'onlyIcon',
                            ],
                            'name'       => 'btn_create_alert',
                            'id'         => 'btn_create_alert',
                        ],
                    ],
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_discover',
                'class'         => 'hole flex-row flex-items-center w98p '.$li_create_discovery_class,
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('Discover hosts and devices in your network'),
                        'arguments' => [
                            'class' => 'first_lbl',
                            'name'  => 'lbl_discover_devices',
                            'id'    => 'lbl_discover_devices',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => '',
                            'type'       => 'button',
                            'attributes' => [
                                'class' => (empty($btn_create_discovery_class) === false) ? $btn_create_discovery_class : 'invisible_important',
                                'mode'  => 'onlyIcon',
                            ],
                            'name'       => 'btn_discover_devices',
                            'id'         => 'btn_discover_devices',
                        ],
                    ],
                ],
            ],
        ];

        if (enterprise_installed() === true) {
            $inputs[] = [
                'wrapper'       => 'div',
                'block_id'      => 'div_not_working',
                'class'         => 'hole flex-row flex-items-center w98p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('If something is not working as expected, look for this icon and report!'),
                        'arguments' => [
                            'class' => 'first_lbl',
                            'name'  => 'lbl_not_working',
                            'id'    => 'lbl_not_working',
                        ],
                    ],
                    [
                        'label' => html_print_image(
                            'images/feedback-header.png',
                            true,
                            [
                                'onclick' => '$(\'#feedback-header\').click()',
                                'style'   => 'cursor: pointer;',
                            ]
                        ),

                    ],
                ],
            ];
        }

        $output = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        $output .= $this->loadJS();
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
    public function loadJS()
    {
        ob_start();
        ?>
    <script type="text/javascript">
        <?php
        if ($this->step > W_CREATE_AGENT) {
            switch ($this->step) {
                case W_CREATE_MODULE:
                    ?>
                document.getElementById("button-btn_create_module").setAttribute(
                    'onclick',
                    'checkAgentOnline()'
                );
                    <?php
                break;

                case W_CREATE_ALERT:
                    ?>
                document.getElementById("button-btn_create_alert").setAttribute(
                    'onclick',
                    'createAlertModule()'
                );
                    <?php
                break;

                default:
                    // Ignore.
                break;
            }
        }
        ?>

    document.getElementById("button-btn_email_conf").setAttribute(
        'onclick',
        'configureEmail()'
    );
    document.getElementById("button-btn_create_agent").setAttribute(
        'onclick',
        'createNewAgent()'
    );
    document.getElementById("button-btn_discover_devices").setAttribute(
        'onclick',
        'discoverDevicesNetwork()'
    );

    function configureEmail() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table3'); ?>';
    }

    function createNewAgent() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&new_agent=1&crt-2=Create+agent'); ?>';
    }

    function checkAgentOnline() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$this->getWelcomeAgent().''); ?>';
    }

    function createAlertModule() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$this->getWelcomeAgent().''); ?>';
    }

    function monitorRemoteCommands() {
        window.location = '<?php echo ui_get_full_url(''); ?>';
    }

    function discoverDevicesNetwork() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan'); ?>';
    }

    function reportIsNotWorking() {
    }

    function cierre_dialog(){
        this.dialog("close");
    }
    </script>
        <?php
        return ob_get_clean();
    }


}
