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

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
ui_require_css_file('pandora');
/**
 * Class NewInstallationWelcomeWindow.
 */
class NewInstallationWelcomeWindow extends Wizard
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
    public function __construct($ajax_controller)
    {
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
                'class'         => 'content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_email_conf"',
                            'name'       => 'btn_email_conf',
                            'id'         => 'btn_email_conf',
                            'script'     => 'configureEmail()',
                        ],
                    ],
                ],
            ],[
                'wrapper'       => 'div',
                'block_id'      => 'div_create_agent',
                'class'         => 'content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_agent"',
                            'name'       => 'btn_create_agent',
                            'id'         => 'btn_create_agent',
                            'script'     => '',
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
                'class'         => 'learn_content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_agent_online"',
                            'name'       => 'btn_check_agent',
                            'id'         => 'btn_check_agent',
                            'script'     => '',
                        ],
                    ],
                ],
            ],
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_alert_module"',
                            'name'       => 'btn_create_alert',
                            'id'         => 'btn_create_alert',
                            'script'     => '',
                        ],
                    ],
                ],
            ],
            /*
                [
                'wrapper'       => 'div',
                'block_id'      => 'div_monitor_actions',
                'class'         => 'learn_content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_remote-command"',
                            'name'       => 'btn_monitor_commmands',
                            'id'         => 'btn_monitor_commmands',
                            'script'     => 'monitorRemoteCommands()',
                        ],
                    ],
                ],
            ],*/
            [
                'wrapper'       => 'div',
                'block_id'      => 'div_discover',
                'class'         => 'content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_discover"',
                            'name'       => 'btn_discover_devices',
                            'id'         => 'btn_discover_devices',
                            'script'     => '',
                        ],
                    ],
                ],
            ],
            /*
                [
                'wrapper'       => 'div',
                'block_id'      => 'div_not_working',
                'class'         => 'content_position',
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
                            'label'      => __(''),
                            'type'       => 'button',
                            'attributes' => 'class="btn_is_not_ok"',
                            'name'       => 'btn_not_working',
                            'id'         => 'btn_not_working',
                            'script'     => 'reportIsNotWorking()',
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


    public static function actions_done()
    {
        $sec2 = get_parameter('sec2', '');
        $mail_user = get_parameter('email_username', '');
        $_SESSION['agente'] = db_get_value('MAX(id_agente)', 'tagente');

        if ($sec2 == '' && $config['initial_wizard'] == false) {
            $welcome = new NewInstallationWelcomeWindow(
                'general/new_installation_welcome_window'
            );
            $welcome->run();
        }

        if ($mail_user !== '' && $sec2 === 'godmode/setup/setup' && !$_SESSION['mail_configured']) {
            $welcome = new NewInstallationWelcomeWindow(
                'general/new_installation_welcome_window'
            );
            $welcome->run();
            $_SESSION['mail_configured'] = true;
        }

        $create_agent = (bool) get_parameter('create_agent');
        $_SESSION['create_module'] = false;
        if ($create_agent && $sec2 === 'godmode/agentes/configurar_agente' && $_SESSION['create_agent']) {
            $welcome = new NewInstallationWelcomeWindow(
                'general/new_installation_welcome_window'
            );
            $welcome->run();
            $_SESSION['create_agent'] = true;
        }

        $create_module = (bool) get_parameter('create_module');
        $sec2_module = explode('&', ui_get_full_url());

        if ($create_agent && $sec2_module[2] === 'tab=module' && $_SESSION['create_module']) {
            $welcome = new NewInstallationWelcomeWindow(
                'general/new_installation_welcome_window'
            );
            $welcome->run();
            $_SESSION['create_module'] = true;
        }

        $sec2_alert = explode('&', ui_get_full_url());

        $create_alert = (int) get_parameter('create_alert', 0);

        $check_module = db_get_value('MAX(id_module)', 'tmodule');
        if ($create_alert && $sec2_alert[2] == 'tab=alert' && $_SESSION['create_alert']) {
            $welcome = new NewInstallationWelcomeWindow(
                'general/new_installation_welcome_window'
            );
            $welcome->run();
            $_SESSION['create_alert'] = true;
        }

        $_SESSION['create_module'] = true;

    }


    public function loadJS()
    {
        ob_start();
        ?>
    <script type="text/javascript">

    if( '.<?php echo $_SESSION['mail_configured']; ?>.') {
        document.getElementById("button-btn_email_conf").className = "btn_email_conf_ok";
        document.getElementById("button-btn_email_conf").onclick = "";
        document.getElementById("button-btn_create_agent").className = "btn_email_conf";
        document.getElementById("button-btn_create_agent").setAttribute('onclick', 'createNewAgent()');
        <?php $_SESSION['mail_configured'] = false; ?>
    }

    if( '.<?php echo $_SESSION['create_module']; ?>.') {
        document.getElementById("button-btn_create_agent").className = "btn_agent_ok";
        document.getElementById("button-btn_check_agent").className = "btn_email_conf";
        document.getElementById("button-btn_check_agent").setAttribute('onclick', 'checkAgentOnline()');
        <?php $_SESSION['create_module'] = false; ?>
    }
    

    if( '.<?php echo $_SESSION['create_alert']; ?>.') {
        document.getElementById("button-btn_create_alert").className = "btn_alert_module_ok";
        document.getElementById("button-btn_create_alert").onclick = "";
        document.getElementById("button-btn_discover_devices").className = "btn_email_conf";
        document.getElementById("button-btn_discover_devices").setAttribute('onclick', 'discoverDevicesNetwork()');
        <?php $_SESSION['create_alert'] = false; ?>
    }



    function configureEmail() {
        window.open('<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table3'); ?>');
    }

    function createNewAgent()
    {
        window.open('<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&new_agent=1&crt-2=Create+agent'); ?>');
    }

    function checkAgentOnline()
    {
        window.open('<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=module&id_agente='.$_SESSION['agente'].''); ?>');

    }

    function createAlertModule()
    {
        window.open('<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$_SESSION['agente'].''); ?>');
    }

    function monitorRemoteCommands()
    {        
        window.open('<?php echo ui_get_full_url(''); ?>'); 
        //document.getElementById("button-btn_monitor_commmands").className = "btn_remote-command_ok";

    }

    function discoverDevicesNetwork()
    {

        window.open('<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan'); ?>');


    }

    function reportIsNotWorking()
    {
      

    }


    </script>   
        <?php
        return ob_get_clean();
    }


}
