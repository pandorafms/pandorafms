<?php
/**
 * Agent deploy wizard
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Agent deploy wizard
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

global $config;

/**
 * Provides functionality for agent deploy wizard.
 */
class AgentDeployWizard
{

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * References datatables object identifier.
     *
     * @var string
     */
    public $tableId;

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = ['loadModal'];


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod($method)
    {
        return in_array($method, $this->AJAXMethods);
    }


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
     * Minor function to dump json message as ajax response.
     *
     * @param string  $type   Type: result || error.
     * @param string  $msg    Message.
     * @param boolean $delete Deletion messages.
     *
     * @return void
     */
    private function ajaxMsg($type, $msg, $delete=false)
    {
        if ($type === 'error') {
            $msg_title = ($delete === true) ? 'Failed while removing' : 'Failed while saving';
        } else {
            $msg_title = ($delete === true) ? 'Successfully deleted' : 'Successfully saved into keystore';
        }

        echo json_encode(
            [ $type => __($msg_title).':<br>'.$msg ]
        );

        exit;
    }


    /**
     * Initializes object and validates user access.
     *
     * @param string $ajax_controller Path of ajaxController, is the 'page'
     *                               variable sent in ajax calls.
     *
     * @return object
     */
    public function __construct($ajax_controller)
    {
        global $config;

        // Check access.
        check_login();

        if ((bool) check_acl($config['id_user'], 0, 'AR') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access agent deploy wizard'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            } else {
                include 'general/noaccess.php';
            }

            exit;
        }

        $this->ajaxController = $ajax_controller;

        return $this;
    }


    /**
     * Prints inputs for modal "Deploy agents".
     *
     * @return void
     */
    public function loadModal()
    {
        ob_start();
        echo '<div id="wizard-modal-content">';
        echo $this->getModalContent();
        echo '</div>';
        echo ob_get_clean();
    }


    /**
     * Run AgentDeployWizard.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        if (check_acl($config['id_user'], 0, 'AR') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access agent deploy.'
            );
            include 'general/noaccess.php';
            return;
        }

        ui_require_css_file('agent_deploy_wizard');

        // Auxiliar div for agent deploy modal.
        echo '<div id="agent_deploy_modal" class="invisible"></div>';

        echo $this->loadJS();
    }


    /**
     * Generates content of modal.
     *
     * @return string Modal content.
     */
    public function getModalContent()
    {
        global $config;

        ob_start();

        $inputs = [];

        // Container div for stepper.
        $stepper_container = html_print_div(
            [
                'id'    => 'stepper_container',
                'class' => 'stepper',
            ],
            true
        );

        html_print_div(
            [
                'id'      => 'modal_header',
                'class'   => 'margin-bottom-10',
                'content' => $stepper_container,
            ]
        );

        // Deploy configuration.
        $tableConfiguration = new stdClass();
        $tableConfiguration->class = 'filter-table-adv w100p';
        $tableConfiguration->data = [];
        $tableConfiguration->style = [];
        $tableConfiguration->cellclass = [];
        $tableConfiguration->colspan = [];
        $tableConfiguration->rowclass['os'] = 'margin-bottom-5';
        $tableConfiguration->rowstyle['block2'] = 'display: flex; justify-content: space-between;';
        $tableConfiguration->rowspan = [];

        $windows_label_img = html_print_image(
            '/images/windows-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon']
        );

        $windows_label = html_print_div(
            [
                'style'   => 'display: flex;align-items: center; margin-top: 5px;margin-bottom: 5px;',
                'content' => $windows_label_img.'Windows',
            ],
            true
        );

        $linux_label_img = html_print_image(
            '/images/linux-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon']
        );

        $linux_label = html_print_div(
            [
                'style'   => 'display: flex;align-items: center; margin-top: 5px;margin-bottom: 5px;',
                'content' => $linux_label_img.'Unix / Linux',
            ],
            true
        );

        $mac_label_img = html_print_image(
            '/images/apple-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon']
        );

        $mac_label = html_print_div(
            [
                'style'   => 'display: flex;align-items: center; margin-top: 5px;margin-bottom: 5px;',
                'content' => $mac_label_img.'Mac OS',
            ],
            true
        );

        // Operating System switch buttons.
        $switchButtons = [];
        $switchButtons[] = html_print_radio_button_extended(
            'os',
            0,
            $windows_label,
            0,
            false,
            '',
            '',
            true
        );
        $switchButtons[] = html_print_radio_button_extended(
            'os',
            1,
            $linux_label,
            0,
            false,
            '',
            '',
            true
        );
        $switchButtons[] = html_print_radio_button_extended(
            'os',
            2,
            $mac_label,
            0,
            false,
            '',
            '',
            true
        );

        $sub_tip = '<span class="input_sub_placeholder_normal">'.__('Please note that all OS must be 64-bit based architecture').'</span>';

        $tableConfiguration->data['os'][] = html_print_label_input_block(
            __('Choose your OS'),
            html_print_div(
                [
                    'id'      => 'os_selector',
                    'class'   => 'switch_radio_button custom-switch-radio-button',
                    'content' => implode('', $switchButtons),
                ],
                true
            ).$sub_tip
        );

        $server_add_help_tip = ui_print_help_tip(
            __('Use your %s Data Server IP address here. It must be possible to establish a connection from the agent to port 41121/tcp of this address.', get_product_name()),
            true
        );

        $tableConfiguration->data['block2'][0] = html_print_label_input_block(
            __('Server address').$server_add_help_tip,
            html_print_input_text(
                'server_addr',
                $_SERVER['SERVER_ADDR'],
                '',
                16,
                100,
                true,
                false,
                true,
                '',
                'w260px'
            )
        );

        $tableConfiguration->data['block2'][1] = html_print_label_input_block(
            __('Group'),
            html_print_select_groups(
                false,
                'AR',
                false,
                'group',
                $group,
                '',
                '',
                0,
                true,
                false,
                true,
                'w260px',
                false,
                '',
                '',
                false,
                'id_grupo',
                false,
                false,
                false,
                '260px',
                false,
                true,
            )
        );

        echo '<div id="config_page">';
        echo '<form id="form_generate_installer" method="post" action="index.php?sec=gagente&amp;sec2=godmode/agentes/agent_deploy">';

        if ($config['language'] === 'es') {
            $instructions_url = 'https://pandorafms.com/manual/es/documentation/02_installation/05_configuration_agents';
        } else {
            $instructions_url = 'https://pandorafms.com/manual/en/documentation/02_installation/05_configuration_agents';
        }

        $instructions_link = '<a class="green-link" style="font-size: 15px;" href="'.$instructions_url.'" target="_blank">'.__('view the following instructions').'</a>';

        $more_info_link = html_print_div(
            [
                'id'      => 'config_form_more_info',
                'class'   => 'warn-box',
                'content' => __('If you need more information regarding agents').', '.$instructions_link,
            ],
            true
        );

        $table_config = html_print_div(
            [
                'style'   => 'flex: 1;',
                'content' => html_print_table($tableConfiguration, true),
            ],
            true
        );

        html_print_div(
            [
                'id'      => 'config_form',
                'class'   => 'white_table_flex agent_details_col modal-content',
                'content' => $table_config.$more_info_link,
            ]
        );

        html_print_div(
            ['id' => 'footer_separator']
        );

        html_print_div(
            [
                'id'      => 'config_buttonset',
                'class'   => 'ui-dialog-buttonset',
                'content' => html_print_submit_button(
                    __('Generate installer'),
                    'generate_installer',
                    false,
                    [],
                    true,
                ),
            ]
        );
        echo '</form>';
        echo '</div>';

        echo '<div id="installer_page">';
        echo '<div id="installer_data" class="white_table_flex agent_details_col modal-content">';

        // Start of Unix / Linux installer section.
        $title = html_print_image(
            '/images/linux-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon svg-brightness-0']
        );

        $title .= '<span class="header_title">'.__('Linux agent').'</span>';

        $content = html_print_div(
            [
                'class'   => 'installer-title',
                'content' => $title,
            ],
            true
        );

        $content .= '<span>'.__('Run the following command in the shell of your Linux server to perform the installation of the generated agent:').'</span>';
        $content .= html_print_code_picker('run_command_box_linux', '', 'installer-code-fragment', false, true);

        $content .= '<span>'.__('Once installed, you must run the following command to start the software agent service:').'</span>';
        $content .= html_print_code_picker('start_service_box_linux', '', 'installer-code-fragment', true, true);

        if ($config['language'] === 'es') {
            $linux_dependencies_url = 'https://pandorafms.com/manual/es/documentation/02_installation/01_installing#requisitos_para_el_agente';
        } else {
            $linux_dependencies_url = 'https://pandorafms.com/manual/en/documentation/02_installation/01_installing#agent_requirements';
        }

        $linux_dependencies_link = '<a class="green-link" href="'.$linux_dependencies_url.'" target="_blank">'.__('dependencies').'</a>';

        $content .= '<span>'.__('For the correct operation of the Linux agent it is necessary that the server has installed the following ').$linux_dependencies_link.'</span>';

        html_print_div(
            [
                'id'      => 'linux_installer',
                'class'   => 'white_table_flex agent_details_col',
                'style'   => 'margin-bottom: 20px',
                'content' => $content,
            ]
        );

        // Start of Windows installer section.
        $title = html_print_image(
            '/images/windows-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon svg-brightness-0']
        );

        $title .= '<span class="header_title">'.__('Windows agent').'</span>';

        $content = html_print_div(
            [
                'class'   => 'installer-title',
                'content' => $title,
            ],
            true
        );

        $content .= '<span>'.__('Run the following command in cmd.exe as an administrator:').'</span>';
        $content .= html_print_code_picker('run_command_box_windows', '', 'installer-code-fragment', false, true);

        $content .= '<span>'.__('Once installed, you must run the following command to start the software agent service:').'</span>';
        $content .= html_print_code_picker('start_service_box_windows', '', 'installer-code-fragment', true, true);

        html_print_div(
            [
                'id'      => 'win_installer',
                'class'   => 'white_table_flex agent_details_col',
                'style'   => 'margin-bottom: 20px',
                'content' => $content,
            ]
        );

        // Start of MacOS installer section.
        $title = html_print_image(
            '/images/apple-grey@svg.svg',
            true,
            ['class' => 'installer-title-icon main_menu_icon svg-brightness-0']
        );

        $title .= '<span class="header_title">'.__('Mac agent').'</span>';

        $content = html_print_div(
            [
                'class'   => 'installer-title',
                'content' => $title,
            ],
            true
        );

        $mac_warn_box = html_print_div(
            [
                'id'      => 'warn_box_mac',
                'class'   => 'warn-box',
                'content' => __('To complete the installation process, please perform a manual installation and configure the server address to XXX and specify the group as XXX. Thank you for your cooperation.'),
            ],
            true
        );

        $mac_installer_link = 'http://firefly.pandorafms.com/pandorafms/latest/macOS/Pandora_FMS_MacOS_agent-7.0NG.dmg';
        $mac_content_link = '<a class="green-link" style="font-size: 15px;" href="'.$mac_installer_link.'" target="_blank">'.__('Click to Download the agent').'</a>';
        html_print_div(
            [
                'id'      => 'mac_installer',
                'class'   => 'white_table_flex agent_details_col',
                'style'   => 'margin-bottom: 20px',
                'content' => $content.$mac_warn_box.$mac_content_link,
            ]
        );

        // Footer.
        html_print_div(['id' => 'footer_separator']);

        echo '</div>';

        html_print_div(
            [
                'id'      => 'installer_buttonset',
                'class'   => 'flex-row',
                'style'   => '',
                'content' => html_print_button(
                    __('Change configuration'),
                    'change_configuration',
                    false,
                    '',
                    ['class' => 'secondary'],
                    true,
                ).html_print_button(
                    __('Done'),
                    'done',
                    false,
                    '',
                    ['style' => 'min-width: 0;'],
                    true
                ),
            ]
        );

        echo '</div>';

        return ob_get_clean();
    }


    /**
     * Loads JS content.
     *
     * @return string JS content.
     */
    public function loadJS()
    {
        ob_start();

        ui_require_javascript_file('stepper', 'include/javascript/', true);

        // Javascript content.
        ?>
        <script type="text/javascript">
            /**
             * Cleanup current dom entries.
             */
            function cleanupDOM() {
                $('#div-identifier').empty();
                $('#div-product').empty();
                $('#div-username').empty();
                $('#div-password').empty();
                $('#div-extra_1').empty();
                $('#div-extra_2').empty();
            }

            /**
            * Process ajax responses and shows a dialog with results.
            */
            function showMsg(data) {
                var title = "<?php echo __('Success'); ?>";
                var text = '';
                var failed = 0;
                try {
                    data = JSON.parse(data);
                    text = data['result'];
                } catch (err) {
                    title =  "<?php echo __('Failed'); ?>";
                    text = err.message;
                    failed = 1;
                }
                if (!failed && data['error'] != undefined) {
                    title =  "<?php echo __('Failed'); ?>";
                    text = data['error'];
                    failed = 1;
                }
                if (data['report'] != undefined) {
                    data['report'].forEach(function (item){
                        text += '<br>'+item;
                    });
                }

                $('#msg').empty();
                $('#msg').html(text);
                $('#msg').dialog({
                    width: 450,
                    position: {
                        my: 'center',
                        at: 'center',
                        of: window,
                        collision: 'fit'
                    },
                    title: title,
                    buttons: [
                        {
                            class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                            text: 'OK',
                            click: function(e) {
                                if (!failed) {
                                    $(".ui-dialog-content").dialog("close");
                                    $('.info').hide();
                                    cleanupDOM();
                                    dt_keystore.draw(false);
                                } else {
                                    $(this).dialog('close');
                                }
                            }
                        }
                    ]
                });
            }

            function generate_installer() {
                $('#config_page').hide();
                $('#installer_page').show();

                var os_val = $('input[name="os"]:checked').val();
                var server_addr_val = $('input[name="server_addr"]').val();
                var group_val = $('[name="group"] option:selected').text();

                var win_installer_command = `Invoke-WebRequest -Uri https://firefly.pandorafms.com/pandorafms/latest/Windows/Pandora%20FMS%20Windows%20Agent%20v7.0NG.x86_64.exe -OutFile \$\{env:tmp\}\\\pandora-agent-windows.exe; & \$\{env:tmp\}\\\pandora-agent-windows.exe /S  --ip ${server_addr_val} --group \"${group_val}\" --remote_config 1`;
                var linux_installer_command = `export PANDORA_SERVER_IP='${server_addr_val}' && \\\nexport PANDORA_REMOTE_CONFIG=1 && \\\nexport PANDORA_GROUP='${group_val}' && \\\ncurl -Ls https://pfms.me/agent-deploy | bash`;
                var mac_installer_text = `To complete the installation process, please perform a manual installation and configure the server IP to ${server_addr_val} and specify the group as ${group_val}. Thank you for your cooperation`;
                var linux_service_start = "/etc/init.d/pandora_agent_daemon start";
                var win_service_start = "NET START PandoraFMSAgent";

                switch (os_val) {
                    case '0':
                        $('#run_command_box_windows').text(win_installer_command);
                        $('#start_service_box_windows').text(win_service_start);
                        $('#linux_installer').hide();
                        $('#mac_installer').hide();
                        $('#win_installer').show();
                    break;

                    case '1':
                        $('#run_command_box_linux').text(linux_installer_command);
                        $('#start_service_box_linux').text(linux_service_start);
                        $('#win_installer').hide();
                        $('#mac_installer').hide();
                        $('#linux_installer').show();
                    break;

                    case '2':
                        $('#warn_box_mac').text(mac_installer_text);
                        $('#win_installer').hide();
                        $('#linux_installer').hide();
                        $('#mac_installer').show();
                    break;

                } 
            }

            function display_deploy_configuration() {
                $('#installer_page').hide();
                $('#config_page').show();
            }

            /**
             * Loads modal from AJAX.
             */
            function show_agent_install_modal() {
                var title = '<?php echo __('Deploy agent'); ?>';
                var method = '';

                load_modal({
                    target: $('#agent_deploy_modal'),
                    form: 'modal_form',
                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                    ajax_callback: showMsg,
                    cleanup: cleanupDOM,
                    modal: {
                        title: title,
                    },
                    extradata: [
                        {
                            name: 'identifier',
                        }
                    ],
                    onload: function() {
                        display_deploy_configuration();

                        var stepper_step_names = [];
                        stepper_step_names.push('<?php echo __('Configuration'); ?>');
                        stepper_step_names.push('<?php echo __('Installer'); ?>');

                        var stepper_container = $('#stepper_container');
                        var stepper = new Stepper(stepper_container, stepper_step_names);

                        stepper.render();

                        // Initial step: 1.
                        stepper.selectStep(1);

                        $("#form_generate_installer").on('submit', function(e) {
                            // We only want the form to be submitted for field validation.
                            e.preventDefault();
                            generate_installer();
                            stepper.selectStep(2);
                        });

                        $("#button-change_configuration").on('click', function() {
                            display_deploy_configuration();
                            stepper.selectStep(1);
                        });

                        $("#button-done").on('click', function() {
                            $(".ui-dialog-content").dialog("close");
                            $('.info').hide();
                            cleanupDOM();
                            dt_keystore.draw(false);
                        });
                    },
                    onshow: {
                        page: '<?php echo $this->ajaxController; ?>',
                        method: 'loadModal'
                    },
                    onsubmit: {
                        page: '<?php echo $this->ajaxController; ?>',
                        method: method
                    }
                });
            }

            $(document).ready(function() {
                var page = 0;

                $("#button-modal_deploy_agent").on('click', function() {
                    show_agent_install_modal();
                });


            });
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
