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
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
                ok: '<?php echo __('Close wizard'); ?>',
                overlay: true,
                overlayExtraClass: 'welcome-overlay',
            },
            onshow: {
                page: '<?php echo $this->ajaxController; ?>',
                method: 'loadWelcomeWindow',
                width: 1000,
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
            },
            onload: () => {
                $(document).ready(function () {
                    var buttonpane = $("div[aria-describedby='welcome_modal_window'] .ui-dialog-buttonpane.ui-widget-content.ui-helper-clearfix");
                    $(buttonpane).append(`
                    <div class="welcome-wizard-buttons">
                        <label>
                            <input type="checkbox" class="welcome-wizard-do-not-show" value="1" />
                            <?php echo __('Do not show anymore'); ?>
                        </label>
                        <button class="close-wizard-button"><?php echo __('Close wizard'); ?></button>
                    </div>
                    `);

                    var closeWizard = $("button.close-wizard-button");

                    $(closeWizard).click(function (e) {
                        var close = $("div[aria-describedby='welcome_modal_window'] button.sub.ok.submit-next.ui-button");
                        var cancel = $("div[aria-describedby='welcome_modal_window'] button.sub.upd.submit-cancel.ui-button");
                        var checkbox = $("div[aria-describedby='welcome_modal_window'] .welcome-wizard-do-not-show:checked").length;

                        if (checkbox === 1) {
                            $(cancel).click();
                        } else {
                            $(close).click()
                        }
                    });
                });
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

        if (enterprise_installed() === true) {
            $logo_url = ENTERPRISE_DIR.'/'.$logo_url;
        }

        if (check_acl($config['id_user'], 0, 'PM')) {
            $flag_um = false;
            $flag_cm = false;
            $flag_su = false;
            $flag_lv = false;

            $btn_update_manager_class = ' fail';
            $btn_configure_mail_class = ' fail';
            $btn_servers_up_class = ' fail';
            $btn_license_valid_class = ' fail';

            $li_update_manager_class = 'row_grey';
            $li_configure_mail_class = 'row_grey';
            $li_servers_up_class = 'row_grey';
            $li_license_valid_class = 'row_grey';

            include_once 'include/functions_update_manager.php';
            if (update_manager_verify_registration()) {
                $btn_update_manager_class = '';
                $li_update_manager_class = 'row_green';
                $flag_um = true;
            }

            if (empty($config['email_username']) === false && empty($config['email_password']) === false) {
                $btn_configure_mail_class = '';
                $li_configure_mail_class = 'row_green';
                $flag_cm = true;
            }

            include_once 'include/functions_servers.php';
            if (check_all_servers_up() === true) {
                $btn_servers_up_class = '';
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
                    $btn_license_valid_class = '';
                    $li_license_valid_class = 'row_green';
                    $flag_lv = true;
                } else {
                    $btn_license_valid_class = 'fail';
                    $li_license_valid_class = 'row_grey';
                    $flag_lv = false;
                }
            } else {
                $btn_license_valid_class = 'fail';
                $li_license_valid_class = 'row_grey';
                $flag_lv = false;
            }

            $inputs[] = [
                'wrapper'       => 'div',
                'block_id'      => 'div_diagnosis',
                'class'         => 'flex-row flex-items-center ',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => __('This is your post-installation status diagnostic:'),
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
                            'label'     => '<span class="status"></span>'.__('Warp Update registration'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_update_manager',
                                'id'    => 'lbl_update_manager',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => __('Cancel'),
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
                            'label'     => '<span class="status"></span>'.__('Default mail to send alerts'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_create_agent',
                                'id'    => 'lbl_create_agent',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => __('Cancel'),
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
                            'label'     => '<span class="status"></span>'.__('All servers running'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_servers_up',
                                'id'    => 'lbl_servers_up',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => __('Cancel'),
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
                            'label'     => '<span class="status"></span>'.__('Enterprise licence valid'),
                            'arguments' => [
                                'class' => 'first_lbl',
                                'name'  => 'lbl_license_valid',
                                'id'    => 'lbl_license_valid',
                            ],
                        ],
                        [
                            'arguments' => [
                                'label'      => __('Cancel'),
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
                $inputs[] = [
                    'wrapper'       => 'div',
                    'block_id'      => 'div_all_correct',
                    'class'         => 'hole flex-row flex-items-center w98p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label'     => __('It seems that your Pandora FMS is working correctly and registered with ID:<br> #'.$config['pandora_uid'].'.<br>For more information use the self-diagnosis tool.'),
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
            'class'         => 'flex-row flex-items-center',
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

        $fields['load_demo_data'] = __('Load demo data');
        $fields['wizard_agent'] = __('Agent installation wizard');
        $fields['check_web'] = __('Create WEB monitoring');
        $fields['check_connectivity'] = __('Create network monitoring');
        $fields['check_net'] = __('Discover my network');
        $fields['check_mail_alert'] = __('Create email alert');

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_wizard_agent',
            'class'         => 'flex space-between',
            'direct'        => 1,
            'block_content' => [
                [
                    'arguments' => [
                        'type'          => 'select',
                        'fields'        => $fields,
                        'name'          => 'task_to_perform',
                        'selected'      => 'check_net',
                        'return'        => true,
                        'nothing'       => \__('Please select one'),
                        'nothing_value' => '',
                    ],
                ],
                [
                    'arguments' => [
                        'label'      => __('Let&apos;s do it!'),
                        'type'       => 'button',
                        'attributes' => [
                            'class' => 'secondary',
                            'icon'  => 'next',
                        ],
                        'name'       => 'go_wizard',
                        'id'         => 'go_wizard',
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

        echo '
            <div class="welcome-wizard-right-content">
                <ul class="welcome-circles">
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                        <li></li>
                </ul>
                <img src="images/welcome-wizard-image.png" />
            </div>
        ';
        ?>
        <div id="dialog_goliat" class="invisible">
            <?php
            echo html_print_input_hidden('check_web', 1);
            echo html_print_label_input_block(
                __('URL'),
                html_print_input_text(
                    'url_goliat',
                    '',
                    '',
                    false,
                    255,
                    true,
                    false,
                    true,
                    '',
                    'w100p'
                )
            );
            echo html_print_label_input_block(
                __('Text to search'),
                html_print_input_text(
                    'text_to_search',
                    '',
                    '',
                    false,
                    255,
                    true,
                    false,
                    false,
                    '',
                    'w100p'
                )
            );
            echo html_print_label_input_block(
                __('Modules name'),
                html_print_input_text(
                    'module_name',
                    '',
                    '',
                    false,
                    255,
                    true,
                    false,
                    false,
                    '',
                    'w100p'
                )
            );
            echo html_print_label_input_block(
                __('Agent group'),
                html_print_select_from_sql(
                    'SELECT * FROM tgrupo ORDER BY nombre',
                    'id_group',
                    '',
                    '',
                    '',
                    false,
                    true,
                    false,
                    true,
                    false,
                    'width: 100%;'
                )
            );

            echo html_print_submit_button(__('Create'), 'create_goliat', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
            ?>
        </div>
        <div id="dialog_demo" class="invisible">
            <?php
            $agent_sel_values = [
                30   => '30',
                50   => '50',
                500  => '500',
                1000 => '1000',
                2000 => '2000',
            ];

            echo '<form action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=demo_data" method="post">';
            echo html_print_input_hidden('create_data', 1, true);
            echo html_print_input_hidden('display_loading', 1, true);
            echo html_print_div(
                [
                    'class'   => '',
                    'content' => 'This wizard will create a complete data set, with history, reports, visual consoles, dashboard and network maps so you can explore the power of Pandora FMS. You will be able to configure it and delete the demo data in the setup.<br><br>',
                ],
                true
            );
            echo html_print_label_input_block(
                __('Number of agents to be created'),
                html_print_div(
                    [
                        'class'   => '',
                        'content' => html_print_select(
                            $agent_sel_values,
                            'agents_num',
                            $agents_num,
                            '',
                            '',
                            30,
                            true,
                            false,
                            true,
                            'w100px'
                        ),
                    ],
                    true
                )
            );

            echo html_print_submit_button(__('Create'), 'create_demo_data', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
            echo '</form>';
            ?>
        </div>
        <div id="dialog_connectivity" class="invisible">
            <?php
            echo html_print_input_hidden('check_connectivity', 1);
            echo html_print_label_input_block(
                __('IP address target'),
                html_print_input_text(
                    'ip_target',
                    '',
                    '',
                    false,
                    15,
                    true,
                    false,
                    true,
                    '',
                    'w100p'
                )
            );
            echo html_print_label_input_block(
                __('Agent alias'),
                html_print_input_text(
                    'agent_name',
                    '',
                    '',
                    false,
                    255,
                    true,
                    false,
                    false,
                    '',
                    'w100p'
                )
            );
            echo html_print_label_input_block(
                __('Agent group'),
                html_print_select_from_sql(
                    'SELECT * FROM tgrupo ORDER BY nombre',
                    'id_group',
                    '',
                    '',
                    '',
                    false,
                    true,
                    false,
                    true,
                    false,
                    'width: 100%;'
                )
            );
            echo html_print_submit_button(__('Create'), 'create_conectivity', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
            ?>
        </div>
        <div id="dialog_basic_net" class="invisible">
            <?php
            echo html_print_input_hidden('create_net_scan', 1);
            echo html_print_label_input_block(
                __('Ip target'),
                html_print_input_text(
                    'ip_target_discovery',
                    '192.168.10.0/24',
                    '192.168.10.0/24',
                    false,
                    18,
                    true,
                    false,
                    true,
                    '',
                    'w100p',
                    '',
                    'off',
                    false,
                    '',
                    '',
                    '',
                    false,
                    '',
                    '192.168.10.0/24'
                )
            );

            echo html_print_submit_button(__('Create'), 'basic_net', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
            ?>
        </div>
        <div id="dialog_alert_mail" class="invisible">
            <?php
            echo html_print_input_hidden('create_mail_alert', 1);
            $params = [];
            $params['return'] = true;
            $params['show_helptip'] = true;
            $params['input_name'] = 'id_agent';
            $params['selectbox_id'] = 'id_agent_module';
            $params['javascript_is_function_select'] = true;
            $params['metaconsole_enabled'] = false;
            $params['use_hidden_input_idagent'] = true;
            $params['print_hidden_input_idagent'] = true;
            echo html_print_label_input_block(
                __('Agent'),
                ui_print_agent_autocomplete_input($params)
            );
            echo html_print_label_input_block(
                __('Module'),
                html_print_select(
                    $modules,
                    'id_agent_module',
                    '',
                    true,
                    '',
                    '',
                    true,
                    false,
                    true,
                    'w100p',
                    false,
                    'width: 100%;',
                    false,
                    false,
                    false,
                    '',
                    false,
                    false,
                    true
                ).'<span id="latest_value" class="invisible">'.__('Latest value').':
                <span id="value">&nbsp;</span></span>
                <span id="module_loading" class="invisible">'.html_print_image('images/spinner.gif', true).'</span>'
            );

            $condition = alerts_get_alert_templates(['(id IN (1,3) OR name = "'.io_safe_input('Unknown condition').'")'], ['id', 'name']);

            echo html_print_label_input_block(
                __('Contition'),
                html_print_select(
                    index_array($condition, 'id', 'name'),
                    'id_condition',
                    '',
                    '',
                    __('Select'),
                    '',
                    true,
                    false,
                    true,
                    'w100p',
                    false,
                    'width: 100%;',
                    false,
                    false,
                    false,
                    '',
                    false,
                    false,
                    true
                )
            );

            echo html_print_submit_button(__('Create'), 'alert_mail', false, ['icon' => 'next', 'style' => 'margin-top:15px; float:right;']);
            ?>
        </div>
        <?php
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
        $('#button-go_wizard').click(function(){
            if ($('#task_to_perform :selected').val() === '') {
                alert("<?php echo __('You must chose an option'); ?>");
            } else {
                switch($('#task_to_perform :selected').val()) {
                    case 'load_demo_data':
                        openCreateDemoDataDialog();
                    break;
                    case 'wizard_agent':
                        deployAgent();
                    break;
                    case 'check_mail_alert':
                        openCreateAlertMailDialog();
                    break;
                    case 'check_connectivity':
                        openCreateConnectivityDialog();
                    break;
                    case 'check_web':
                        openCreateModulesDialog();
                    break;
                    case 'check_net':
                        openCreateBasicNetDialog();
                    break;
                };
            }
        });

        function configureUpdateManager() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online'); ?>';
        }

        function configureEmail() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table4'); ?>';
        }

        function serversUp() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60'); ?>';
        }

        function messageLicense() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=message_list&sec2=operation/messages/message_list'); ?>';
        }

        // Task to do actions.
        function deployAgent() {
            window.location = '<?php echo ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/modificar_agente&show_deploy_agent=1'); ?>';
        }

        function openCreateModulesDialog() {
            $('#dialog_goliat').dialog({
                title: '<?php echo __('Create WEB monitoring'); ?>',
                resizable: true,
                draggable: true,
                modal: true,
                close: false,
                height: 400,
                width: 500,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            })
            .show();
        }

        function openCreateDemoDataDialog() {
            $('#dialog_demo').dialog({
                title: '<?php echo __('Create demo data'); ?>',
                resizable: true,
                draggable: true,
                modal: true,
                close: false,
                height: 300,
                width: 480,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            })
            .show();
        }

        function openCreateConnectivityDialog() {
            $('#dialog_connectivity').dialog({
                title: '<?php echo __('Create network monitoring'); ?>',
                resizable: true,
                draggable: true,
                modal: true,
                close: false,
                height: 350,
                width: 480,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            })
            .show();
        }

        function openCreateBasicNetDialog() {
            $('#dialog_basic_net').dialog({
                title: '<?php echo __('Discover my network'); ?>',
                resizable: true,
                draggable: true,
                modal: true,
                close: false,
                height: 200,
                width: 480,
                overlay: {
                    opacity: 0.5,
                    background: "black"
                }
            })
            .show();
        }

        function openCreateAlertMailDialog() {
            $.ajax({
                async: false,
                type: "POST",
                url: "include/ajax/task_to_perform.php",
                data: {
                    create_unknown_template_alert: 1,
                },
                success: function(data) {
                    $('#dialog_alert_mail').dialog({
                        title: '<?php echo __('Create email alert'); ?>',
                        resizable: true,
                        draggable: true,
                        modal: true,
                        close: false,
                        height: 350,
                        width: 480,
                        overlay: {
                            opacity: 0.5,
                            background: "black"
                        }
                    })
                    .show();

                    $('#text-id_agent').autocomplete({
                        appendTo: '#dialog_alert_mail'
                    });

                    $("#id_agent_module").select2({
                            dropdownParent: $("#dialog_alert_mail")
                    });
                }
            });
        }

        $('#button-create_goliat').click(function(){
            $.ajax({
                async: false,
                type: "POST",
                url: "include/ajax/task_to_perform.php",
                data: {
                    check_web: 1,
                    id_group: $('#id_group :selected').val(),
                    module_name: $('#text-module_name').val(),
                    text_to_search: $('#text-text_to_search').val(),
                    url_goliat: $('#text-url_goliat').val(),
                },
                success: function(data) {
                    if (data !== 0) {
                        data = data.replace(/(\r\n|\n|\r)/gm, "");
                        console.log(data);
                        $('body').append(data);
                        // Close dialog
                        $('.ui-dialog-titlebar-close').trigger('click');
                        return false;
                    }
                }
            });
        });

        $('#button-create_conectivity').click(function(e){
            if($("#text-ip_target")[0].checkValidity() == false) {
                $("#text-ip_target")[0].reportValidity();
                return false;
            }
            $.ajax({
                async: false,
                type: "POST",
                url: "include/ajax/task_to_perform.php",
                data: {
                    check_connectivity: 1,
                    id_group: $('#id_group1 option:selected').val(),
                    ip_target: $('#text-ip_target').val(),
                    agent_name: $('#text-agent_name').val(),
                },
                success: function(data) {
                    if (data !== 0) {
                        data = data.replace(/(\r\n|\n|\r)/gm, "");
                        console.log(data);
                        $('body').append(data);
                        // Close dialog
                        $('.ui-dialog-titlebar-close').trigger('click');
                        return false;
                    }
                }
            });
        });

        $('#button-basic_net').click(function(){
            $.ajax({
                async: false,
                type: "POST",
                url: "include/ajax/task_to_perform.php",
                data: {
                    create_net_scan: 1,
                    ip_target: $('#text-ip_target_discovery').val(),
                },
                success: function(data) {
                    if (data !== 0) {
                        data = data.replace(/(\r\n|\n|\r)/gm, "");
                        console.log(data);
                        $('body').append(data);
                        // Close dialog
                        $('.ui-dialog-titlebar-close').trigger('click');
                        return false;
                    }
                }
            });
        });

        $('#button-alert_mail').click(function(){
            $.ajax({
                async: false,
                type: "POST",
                url: "include/ajax/task_to_perform.php",
                data: {
                    create_mail_alert: 1,
                    id_condition: $('#id_condition').val(),
                    id_agent_module: $('#id_agent_module').val(),
                },
                success: function(data) {
                    if (data !== 0) {
                        data = data.replace(/(\r\n|\n|\r)/gm, "");
                        console.log(data);
                        $('body').append(data);
                        // Close dialog
                        $('.ui-dialog-titlebar-close').trigger('click');
                        return false;
                    }
                }
            });
        });

    </script>
        <?php
        return ob_get_clean();
    }


}
