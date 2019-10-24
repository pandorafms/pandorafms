<?php
/**
 * Credential store
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

// Begin.
global $config;


defined('WELCOME_STARTED') || define('WELCOME_STARTED', 1);
defined('WELCOME_FINISHED') || define('WELCOME_FINISHED', 2);



require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
ui_require_css_file('pandora');
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
    public $AJAXMethods = ['loadWelcomeWindow'];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Current step.
     *
     * @var string
     */
    public $step;


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

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access event viewer'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param string $ajax_controller Controller.
     *
     * @return object
     */
    public function __construct(
        $ajax_controller='include/ajax/welcome_window'
    ) {
        $this->ajaxController = $ajax_controller;
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

            echo '<div id="welcome_modal_window" style="display: none"; >';
        ?>
    <script type="text/javascript">
        load_modal({
            target: $('#welcome_modal_window'),
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            ajax_callback: function() {
                console.log("se dispara callback");
            },
            modal: {
                title: "<?php echo __('Welcome to Pandora FMS'); ?>",
                ok: '<?php echo __('OK'); ?>',
                cancel: '<?php echo __('Cancel'); ?>',
            },
            onshow: {
                page: '<?php echo $this->ajaxController; ?>',
                method: 'loadWelcomeWindow'
            },
        });
    </script>

        <?php
        echo '</div>';
    }


    /**
     * Loads a welcome window form
     *
     * @return​ ​string HTML code for form.
     *
     * @return Function loadWelcomeWindow.
     */
    public function loadWelcomeWindow()
    {
        global $config;
        $btn_configure_mail_class = '';
        $btn_create_agent_class = '';
        $btn_create_module_class = '';
        $btn_create_alert_class = '';
        $btn_create_discovery_class = '';
        $action = '';

        if (($_SESSION['step'] === 'create_mail') || $_SESSION['step'] === null) {
            // Pending mail.
            $btn_configure_mail_class = ' pending';
        } else if ($_SESSION['step'] === 'create_agent') {
            $this->step = 'create_agent';
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' pending';
        } else if ($_SESSION['step'] === 'create_module') {
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' pending';
        } else if ($_SESSION['step'] === 'create_alert') {
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' completed';
            $btn_create_alert_class = ' pending';
        } else if ($_SESSION['step'] === 'create_discovery') {
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' completed';
            $btn_create_alert_class = ' completed';
            $btn_create_discovery_class = ' pending';
        } else if ($_SESSION['step'] === 'end') {
            // Nothing left to do.
            $btn_configure_mail_class = ' completed';
            $btn_create_agent_class = ' completed';
            $btn_create_module_class = ' completed';
            $btn_create_alert_class = ' completed';
            $btn_create_discovery_class = ' completed';
        }

        $form = [
            'action'   => '#',
            'id'       => 'welcome_form',
            'onsubmit' => 'this.dialog("close");',
            'class'    => 'modal',
        ];

        $inputs = [
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_configure_mail',
                'class'         => 'flex-row w100p',
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
                            'attributes' => 'class="go '.$btn_configure_mail_class.'"',
                            'name'       => 'btn_email_conf',
                            'id'         => 'btn_email_conf',
                        ],
                    ],
                ],
            ],[
                'wrapper'       => 'div',
                'block_id'      => 'div_create_agent',
                'class'         => 'flex-row w100p',
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
                            'attributes' => 'class="go '.$btn_create_agent_class.'"',
                            'name'       => 'btn_create_agent',
                            'id'         => 'btn_create_agent',
                        ],
                    ],
                ],
            ],
            [
                'label'     => 'Learn to monitor',
                'arguments' => [
                    'class' => 'class="lbl_learn"',
                    'name'  => 'lbl_learn',
                    'id'    => 'lbl_learn',
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_indented flex-row w100p',
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
                            'attributes' => 'class="go '.$btn_create_module_class.'"',
                            'name'       => 'btn_create_module',
                            'id'         => 'btn_create_module',
                        ],
                    ],
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_indented flex-row w100p',
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
                            'attributes' => 'class="go '.$btn_create_alert_class.'"',
                            'name'       => 'btn_create_alert',
                            'id'         => 'btn_create_alert',
                        ],
                    ],
                ],
            ],
            /*
                [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_indented flex-row w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => 'Monitor remote commmands',
                        'arguments' => [
                            'class' => 'lbl_monitor_commands',
                            'name'  => 'lbl_monitor_commands',
                            'id'    => 'lbl_monitor_commands',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => '',
                            'type'       => 'button',
                            'attributes' => 'class="go '.$btn_create.'"',
                            'name'       => 'btn_monitor_commmands',
                            'id'         => 'btn_monitor_commmands',
                        ],
                    ],
                ],
            ],*/
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_discover',
                'class'         => 'flex-row w100p',
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
                            'attributes' => 'class="go '.$btn_create_discovery_class.'"',
                            'name'       => 'btn_discover_devices',
                            'id'         => 'btn_discover_devices',
                        ],
                    ],
                ],
            ],
            /*
                [
                'wrapper'       => 'div',
                'block_id'      => 'div_not_working',
                'class'         => 'flex-row w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('If something is not working as expected... Report!'),
                        'arguments' => [
                            'class' => 'first_lbl',
                            'name'  => 'lbl_not_working',
                            'id'    => 'lbl_not_working',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => '',
                            'type'       => 'button',
                            'attributes' => 'class="go pending '.$btn_create.'"',
                            'name'       => 'btn_not_working',
                            'id'         => 'btn_not_working',
                        ],
                    ],
                ],
            ],*/
        ];

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
     * Esto es un constructor realmente...
     * se llama desde la navegación normal , no ajax
     */
    public static function initialize($must_run)
    {
        global $config;

        if ($must_run === false) {
            // Do not start unless already started.
            if ($config['welcome_started'] != WELCOME_STARTED) {
                return false;
            }
        }

        // Calculate steps.
        $sec2 = get_parameter('sec2', '');
        $mail_user = get_parameter('email_username', '');
        $mail_configured = db_get_value('value', 'tconfig', 'token', 'email_username');
        $create_agent = (bool) get_parameter('create_agent');
        $create_module = (bool) get_parameter('create_module');
        $sec2_url = explode('&', ui_get_full_url());
        $create_alert = (int) get_parameter('create_alert', 0);
        $task_id = get_parameter('task', null);
        $_SESSION['agent'] = db_get_value(
            'MAX(id_agente)',
            'tagente'
        );

        if ($sec2 === '') {
            $welcome = new WelcomeWindow();
            $welcome->run();
            // Nothing done yet. Launch mail.
            $_SESSION['step'] = null;
        } else if (($mail_user !== ''
            || $mail_configured )
            && $sec2 === 'godmode/setup/setup'
        ) {
            $welcome = new WelcomeWindow();
            $welcome->run();
            $_SESSION['create_agent'] = true;
            $_SESSION['step'] = 'create_agent';
            // Mail configured.
        } else if ($create_agent
            && $sec2 === 'godmode/agentes/configurar_agente'
        ) {
            $welcome = new WelcomeWindow();
            $welcome->run();
            // Agent created.
            // Store id_agent created.
            $_SESSION['create_module'] = true;
            $_SESSION['step'] = 'create_module';
        } else if ($create_module
            && $sec2_url[2] == 'tab=module'
        ) {
            $welcome = new WelcomeWindow();
            $welcome->run();
            // Module created.
            $_SESSION['create_alert'] = true;
            $_SESSION['step'] = 'create_alert';
        } else if ($create_alert && $sec2_url[2] == 'tab=alert') {
            $welcome = new WelcomeWindow();
            $welcome->run();
            // Alert created.
            $_SESSION['create_discovery'];
            $_SESSION['step'] = 'create_discovery';
        } else if ($sec2_url[3] === 'mode=netscan') {
            // Discovery task created.
            $_SESSION['step'] = 'end';

            // Welcome is finished.
            config_update_value('welcome_started', WELCOME_FINISHED);

            // No more 'welcomes' to show.
            return false;
        } else {
            // No step found. Retrieve from session.
            $_SESSION['step'] = null;
            if (empty($_SESSION['create_mail'] === true)) {
                $_SESSION['step'] = 'create_mail';
            }

            if (empty($_SESSION['create_mail']) === false) {
                $_SESSION['step'] = 'create_agent';
            }

            if (empty($_SESSION['create_agent']) === false) {
                $_SESSION['step'] = 'create_module';
            }

            if (empty($_SESSION['create_module']) === false) {
                $_SESSION['step'] = 'create_alert';
            }

            if (empty($_SESSION['create_alert']) === false) {
                $_SESSION['step'] = 'create_discovery';
            }

            if (empty($_SESSION['create_discovery']) === false) {
                // No more 'welcomes' to show.
                return false;
            }
        }

        // Return a reference to the new object.
        return $welcome;
    }


    /**
     * DOCUMENTA!!!
     */
    public function loadJS()
    {
        ob_start();
        ?>
    <script type="text/javascript">
    console.log('vale');
    if('.<?php echo $_SESSION['step'] == 'create_mail'; ?>.'){
        document.getElementById("button-btn_email_conf").setAttribute('onclick', 'configureEmail()');
        console.log('mail');
    }
    if( '.<?php echo $_SESSION['step'] == 'create_agent'; ?>.') {
        document.getElementById("button-btn_create_agent").setAttribute('onclick', 'createNewAgent()');
        console.log('agente true');
    }
    if( '.<?php echo $_SESSION['step'] == 'create_module'; ?>.') {
        document.getElementById("button-btn_create_module").setAttribute('onclick', 'checkAgentOnline()');
        console.log('modulo entra true');
    }
    if( '.<?php echo $_SESSION['step'] == 'create_alert'; ?>.') {
        
        document.getElementById("button-btn_create_alert").setAttribute('onclick', 'createAlertModule()');
    }
     if( '.<?php echo $_SESSION['step'] == 'create_discover'; ?>.') {
        document.getElementById("button-btn_discover_devices").setAttribute('onclick', 'discoverDevicesNetwork()');
    }else if('.<?php echo $_SESSION['create_discovery']; ?>.'){
        document.getElementById("button-btn_discover_devices").onclick= '';
    }

    function configureEmail() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table3'); ?>';
    }

    function createNewAgent() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&new_agent=1&crt-2=Create+agent'); ?>';
    }

    function checkAgentOnline() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$_SESSION['agent'].''); ?>';
    }

    function createAlertModule() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$_SESSION['agent'].''); ?>';
    }

    function monitorRemoteCommands() {        
        window.location = '<?php echo ui_get_full_url(''); ?>'; 
    }

    function discoverDevicesNetwork() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan'); ?>';
    }

    function reportIsNotWorking() {
    }


    </script>   
        <?php
        return ob_get_clean();
    }


}
