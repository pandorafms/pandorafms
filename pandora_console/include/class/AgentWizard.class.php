<?php
/**
 * Agent Wizard for SNMP and WMI
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Agent Configuration
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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

// Get global data.
global $config;

// Necessary class for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
/**
 * AgentWizard class
 */
class AgentWizard extends HTML
{

    /**
     * Var that contain very cool stuff
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Contains the URL of this
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Id of this current agent
     *
     * @var integer
     */
    private $idAgent;

    /**
     * Wizard Section (SNMP or WMI)
     *
     * @var string
     */
    private $wizardSection;


    /**
     * Constructor
     *
     * @param string $ajax_controller Pues hace cosas to wapas.
     */
    public function __construct(string $ajax_controller)
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

        // Set baseUrl for use it in several locations in this class.
        $this->baseUrl          = ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard');
        // Capture all parameters before start.
        $this->ajaxController   = $ajax_controller;
        $this->wizardSection    = get_parameter('wizard_section', '');
        $this->idAgent          = get_parameter('id_agente', '');
        return $this;
    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        // Javascript.
        // ui_require_javascript_file('jquery.caret.min');
        $this->loadMainForm();
        // Load integrated JS
        $this->loadJS();
    }


    /**
     * Common Main Wizard form
     *
     * @return void
     */
    private function loadMainForm()
    {
        // Define name of explorer button
        if ($this->wizardSection === 'snmp_explorer') {
            $btnActionLabel = __('SNMP Walk');
        } else if ($this->wizardSection === 'wmi_explorer') {
            $btnActionLabel = __('WMI Explorer');
        } else {
            $btnActionLabel = 'Nothing';
        }

        // Fill with servers to perform SNMP walk.
        $fieldsServers = [
            'Local',
            'Network',
        ];
        // Fill with SNMP versions allowed.
        $fieldsVersions = [
            '1',
            '2c',
            '3',
        ];
        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'main_wizard_form',
            'method' => 'POST',
            'class'  => 'discovery databox filters',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'id'        => 'inp-id_np',
            'arguments' => [
                'name'   => 'id_np',
                'type'   => 'hidden',
                'value'  => $this->id_np,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Target IP'),
            'id'        => 'txt-target-ip',
            'arguments' => [
                'name'        => 'target-ip',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'class'       => '',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Port'),
            'id'        => 'txt-target-port',
            'arguments' => [
                'name'        => 'target-port',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'size'        => '20',
                'class'       => '',
                'return'      => true,
            ],
        ];

        if ($this->wizardSection === 'snmp_explorer') {
            $inputs[] = [
                'label'     => __('Use agent IP'),
                'id'        => 'txt-use-agent-ip',
                'arguments' => [
                    'name'        => 'use-agent-ip',
                    'input_class' => 'flex-row',
                    'type'        => 'checkbox',
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        if ($this->wizardSection === 'wmi_explorer') {
            $inputs[] = [
                'label'     => __('Namespace'),
                'id'        => 'txt-namespace',
                'arguments' => [
                    'name'        => 'namespace',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Username'),
                'id'        => 'txt-username',
                'arguments' => [
                    'name'        => 'username',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Password'),
                'id'        => 'txt-password',
                'arguments' => [
                    'name'        => 'password',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        $inputs[] = [
            'label'     => __('Server to execute command'),
            'id'        => 'txt-target-port',
            'arguments' => [
                'name'        => 'target-port',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $fieldsServers,
                'class'       => '',
                'return'      => true,
            ],
        ];

        if ($this->wizardSection === 'snmp_explorer') {
            $inputs[] = [
                'label'     => __('SNMP community'),
                'id'        => 'txt-snmp-community',
                'arguments' => [
                    'name'        => 'snmp-community',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'size'        => '20',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('SNMP version'),
                'id'        => 'txt-snmnp-version',
                'arguments' => [
                    'name'        => 'snmnp-version',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'fields'      => $fieldsVersions,
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        $inputs[] = [
            'arguments' => [
                'label'      => $btnActionLabel,
                'name'       => 'action',
                'type'       => 'button',
                'attributes' => 'class="sub next"',
                'return'     => true,
            ],
        ];

        $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ]
        );
    }


    /**
     * Generate the JS needed for use inside
     *
     * @return void
     */
    private function loadJS()
    {
        $str = '';

        ob_start();
        ?>
        <script type="text/javascript">
            // The functions goes here!
        </script>
        <?php
        $str = ob_get_clean();
        echo $str;
        return $str;
    }
}