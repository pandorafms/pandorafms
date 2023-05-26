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

// Get global data.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
require_once $config['homedir'].'/include/functions_snmp_browser.php';
require_once $config['homedir'].'/include/functions_wmi.php';
require_once $config['homedir'].'/include/class/CredentialStore.class.php';


use PandoraFMS\Module;

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
     * Id of this current policy
     *
     * @var integer
     */
    private $idPolicy;

    /**
     * Wizard Section for Explore
     *
     * @var string
     */
    private $wizardSection;

    /**
     * Label to show what action are performing
     *
     * @var string
     */
    private $actionLabel;

    /**
     * Type of action to do
     *
     * @var string
     */
    private $actionType;

    /**
     * URL with the actual section
     *
     * @var string
     */
    private $sectionUrl;

    /**
     * Message to show
     *
     * @var array
     */
    private $message;

    /**
     * Is show message.
     *
     * @var boolean
     */
    private $showMessage;

    /**
     *  Target ip.
     *
     * @var string
     */
    private $targetIp;

    /**
     *  Target Port.
     *
     * @var string
     */
    private $targetPort;

    /**
     *  SNMP Community.
     *
     * @var string
     */
    private $community;

    /**
     *  SNMP Version.
     *
     * @var string
     */
    private $version;

    /**
     *  Server to execute command.
     *
     * @var string
     */
    private $server;

    /**
     *  Type Server to execute command.
     *
     * @var integer
     */
    private $serverType;

    /**
     *  SNMP v3 Authentication User.
     *
     * @var string
     */
    private $authUserV3;

    /**
     *  SNMP v3 Authentication Password.
     *
     * @var string
     */
    private $authPassV3;

    /**
     *  SNMP v3 Authentication Method.
     *
     * @var string
     */
    private $authMethodV3;

    /**
     *  SNMP v3 Security Level.
     *
     * @var string
     */
    private $securityLevelV3;

    /**
     *  SNMP v3 Privacy Method.
     *
     * @var string
     */
    private $privacyMethodV3;

    /**
     *  SNMP v3 Privacy Pass.
     *
     * @var string
     */
    private $privacyPassV3;

    /**
     * WMI Namespace
     *
     * @var string
     */
    private $namespaceWMI;

    /**
     * WMI Username
     *
     * @var string
     */
    private $usernameWMI;

    /**
     * WMI Password
     *
     * @var string
     */
    private $passwordWMI;

    /**
     * Private Enterprise Number name
     *
     * @var string
     */
    private $penName;

    /**
     * Interfaces found
     *
     * @var mixed
     */
    private $interfacesFound;

    /**
     * Some useful information about interfaces:
     * `name` => [
     *    operstatus
     *    adminstatus
     * ]
     *
     * @var array
     */
    private $interfacesData;

    /**
     * X64 Interfaces
     *
     * @var boolean
     */
    private $interfacesx64;

    /**
     * Protocol
     *
     * @var string
     */
    private $protocol;

    /**
     * WMI Command
     *
     * @var string
     */
    private $wmiCommand;

    /**
     * Results for SNMP or WMI queries
     *
     * @var mixed
     */
    private $moduleBlocks;

    /**
     * Extra arguments for SNMP call.
     *
     * @var string
     */
    private $extraArguments = '';

    /**
     * Binary of wmic.
     *
     * @var string
     */
    private $wmiBinary = '';

    /**
     * Default values for SNMP Interfaces.
     *
     * @var string
     */
    private $defaultSNMPValues = [];


    /**
     * Constructor
     *
     * @param string $ajax_controller Path.
     */
    public function __construct(string $ajax_controller)
    {
        global $config;

        // Check access.
        check_login();

        if ((bool) check_acl($config['id_user'], 0, 'AR') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access event viewer'
            );

            if (is_ajax() === true) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        // Capture all parameters before start.
        $this->ajaxController = $ajax_controller;
        $this->wizardSection = get_parameter('wizard_section', 'snmp_explorer');
        $this->idAgent = get_parameter('id_agente', '');
        $this->idPolicy = get_parameter('id', '');
        $this->targetIp = io_safe_input(trim(io_safe_output(get_parameter('targetIp', ''))));
        $this->wmiBinary = $config['wmiBinary'];
        $this->defaultSNMPValues = (array) json_decode(io_safe_output($config['agent_wizard_defaults']));

        if (empty($this->idAgent) === false) {
            $array_aux = db_get_all_rows_sql(
                sprintf(
                    'SELECT ip FROM taddress ta
                    INNER JOIN taddress_agent taa ON taa.id_a = ta.id_a
                    WHERE taa.id_agent = %d',
                    $this->idAgent
                )
            );

            if (empty($array_aux) === false) {
                $this->datalist = [];
                foreach ($array_aux as $key => $value) {
                    $this->datalist[] = $value['ip'];
                }
            }

            if (empty($this->datalist) === false && count($this->datalist) === 1 && $this->targetIp === '') {
                $this->targetIp = $this->datalist[0];
            }
        }

        $this->server = (int) get_parameter('server', '1');
        if ($this->server !== 0) {
            $this->serverType = (int) db_get_value(
                'server_type',
                'tserver',
                'id_server',
                $this->server
            );
        }

        // Capture the parameters.
        $this->protocol = get_parameter('protocol');
        $this->targetPort = get_parameter('targetPort', '161');
        if ($this->protocol === 'snmp') {
            $this->community = get_parameter('community', 'public');
            $this->version   = get_parameter('version', '1');

            // Only for SNMPv3. Catch only if is neccesary.
            if ($this->version === '3') {
                $this->authUserV3 = get_parameter(
                    'authUserV3',
                    ''
                );
                $this->authPassV3 = get_parameter(
                    'authPassV3',
                    ''
                );
                $this->authMethodV3 = get_parameter(
                    'authMethodV3',
                    ''
                );
                $this->securityLevelV3 = get_parameter('securityLevelV3', '');
                $this->privacyMethodV3 = get_parameter('privacyMethodV3', '');
                $this->privacyPassV3 = get_parameter('privacyPassV3', '');
            }
        } else if ($this->protocol === 'wmi') {
            $this->namespaceWMI = get_parameter('namespaceWMI', '');
            $this->usernameWMI = get_parameter('usernameWMI', '');
            $this->passwordWMI = get_parameter('passwordWMI', '');
        }

        // Set baseUrl for use it in several locations in this class.
        if (empty($this->idPolicy) === true) {
            $this->baseUrl = ui_get_full_url(
                'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard&id_agente='.$this->idAgent
            );
        } else {
            if (is_metaconsole() === true) {
                $this->baseUrl = ui_get_full_url(
                    'index.php?sec=gmodules&sec2=advanced/policymanager&tab=agent_wizard&id='.$this->idPolicy
                );
            } else {
                $this->baseUrl = ui_get_full_url(
                    'index.php?sec=gmodules&sec2=enterprise/godmode/policies/policies&tab=agent_wizard&id='.$this->idPolicy
                );
            }
        }

        $this->sectionUrl = $this->baseUrl.'&wizard_section='.$this->wizardSection;

        $this->message['type']    = [];
        $this->message['message'] = [];
        $this->showMessage = false;

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
        $createModules = (bool) get_parameter('create-modules-action', false);
        if ($createModules === true) {
            $this->processModules();
        } else {
            if ($this->protocol === 'snmp') {
                $this->performSNMP();
            } else if ($this->protocol === 'wmi') {
                $this->performWMI();
            }
        }

        // Load main form.
        $this->loadMainForm();

        // Generate the box that allow messages
        // (and show messages if needed).
        $this->showMessage();

        if ($this->showMessage === false) {
            if ($createModules === false) {
                // Show results if we perform any action.
                if (empty($this->protocol) === false) {
                    if ($this->wizardSection === 'snmp_interfaces_explorer') {
                        if (empty($this->interfacesFound) === false) {
                            $this->resultsInterfaceWizard();
                        }
                    } else {
                        $this->moduleBlocks = $this->getModuleBlocks();
                        if ($this->moduleBlocks === false) {
                            $this->message['type'][]    = 'info';
                            $this->message['message'][] = __(
                                'There are not defined Remote components for this performance.'
                            );
                            $this->showMessage();
                        } else {
                            if ($this->wizardSection === 'snmp_explorer') {
                                $this->resultsSNMPExplorerWizard();
                            } else if ($this->wizardSection === 'wmi_explorer') {
                                $this->resultsWMIExplorerWizard();
                            }
                        }
                    }
                }
            }
        }

        // Lodaing div.
        echo '<div class="loading-wizard mrgn_top_20px"></div>';

        // Modal Div.
        echo '<div class="invisible" id="modal_agent_wizard"></div>';
        echo '<div class="invisible" id="msg"></div>';

        // Load integrated JS.
        $this->loadJS();
    }


    /**
     * Generate the message if needed
     *
     * @return void
     */
    private function showMessage()
    {
        if (empty($this->message['type']) === false) {
            $message_error = '';
            $message_success = '';
            foreach ($this->message['type'] as $keyMsg => $typeError) {
                switch ($typeError) {
                    case 'error':
                        $message_error .= ($this->message['message'][$keyMsg] ?? 'Unknown error. Please, review the logs.');
                        $message_error .= '</br></br>';
                    break;

                    case 'success':
                        $message_success .= ($this->message['message'][$keyMsg] ?? 'The action has did successfull');
                        $message_success .= '</br></br>';
                    break;

                    case 'warning':
                        echo ui_print_warning_message(
                            $this->message['message'][$keyMsg]
                        );
                    break;

                    case 'info':
                        echo ui_print_info_message(
                            $this->message['message'][$keyMsg]
                        );
                    break;

                    default:
                        // Nothing to do.
                    break;
                }
            }

            if (empty($message_error) === false) {
                echo ui_print_error_message($message_error);
            }

            if (empty($message_success) === false) {
                echo ui_print_success_message($message_success);
            }

            $this->showMessage = true;
        }

        // Clear the message info.
        $this->message['type'] = [];
        $this->message['message'] = [];
    }


    /**
     * Common Main Wizard form
     *
     * @return void
     */
    private function loadMainForm()
    {
        // Fill with servers to perform the discover.
        $fieldsServers = [];
        $fieldsServers[0] = __('Local console');
        if (enterprise_installed() === true) {
            enterprise_include_once('include/functions_satellite.php');
            // Get the servers.
            $rows = get_proxy_servers();

            // Generate a list with allowed servers.
            if (isset($rows) === true && is_array($rows) === true) {
                foreach ($rows as $row) {
                    if ($row['server_type'] == 13) {
                        $id_satellite = $row['id_server'];
                        $serverType = ' (Satellite)';
                    } else {
                        $serverType = ' (Standard)';
                    }

                    $fieldsServers[$row['id_server']] = $row['name'].$serverType;
                }
            }
        }

        // Set the labels and types.
        switch ($this->wizardSection) {
            case 'snmp_explorer':
            case 'snmp_interfaces_explorer':
                $this->actionType = 'snmp';
                $this->actionLabel = __('SNMP Walk');
            break;

            case 'wmi_explorer':
                $this->actionType = 'wmi';
                $this->actionLabel = __('WMI Explorer');
            break;

            default:
                // Something goes wrong.
            exit;
        }

        // Main form.
        $this->sectionUrl = $this->baseUrl.'&wizard_section='.$this->wizardSection;

        $form = [
            'action' => $this->sectionUrl,
            'id'     => 'form-main-wizard',
            'method' => 'POST',
            'class'  => 'white_box pdd_20px filter-list-adv',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'id'        => 'protocol',
            'arguments' => [
                'name'   => 'protocol',
                'type'   => 'hidden',
                'value'  => $this->actionType,
                'return' => true,
            ],
        ];

        if (empty($this->datalist) === false) {
            $inputs[] = [
                'id'        => 'li_address_list',
                'arguments' => [
                    'name'   => 'address_list',
                    'type'   => 'datalist',
                    'value'  => $this->datalist,
                    'return' => true,
                ],
            ];
        }

        $inputs[] = [
            'label'     => __('Target IP'),
            'id'        => 'txt-targetIp',
            'arguments' => [
                'name'        => 'targetIp',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'class'       => '',
                'value'       => $this->targetIp,
                'return'      => true,
                'list'        => 'address_list',
            ],
        ];

        if ($this->actionType === 'snmp') {
            $inputs[] = [
                'label'     => __('Port'),
                'id'        => 'txt-targetPort',
                'arguments' => [
                    'name'        => 'targetPort',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'size'        => '20',
                    'class'       => '',
                    'value'       => $this->targetPort,
                    'return'      => true,
                ],
            ];
        }

        if ($this->actionType === 'wmi') {
            $inputs[] = [
                'label'     => __('namespace'),
                'id'        => 'txt-namespaceWMI',
                'arguments' => [
                    'name'        => 'namespaceWMI',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'value'       => $this->namespaceWMI,
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Username'),
                'id'        => 'txt-usernameWMI',
                'arguments' => [
                    'name'        => 'usernameWMI',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'value'       => $this->usernameWMI,
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Password'),
                'id'        => 'txt-passwordWMI',
                'arguments' => [
                    'name'        => 'passwordWMI',
                    'input_class' => 'flex-row',
                    'type'        => 'password',
                    'class'       => '',
                    'value'       => $this->passwordWMI,
                    'return'      => true,
                ],
            ];

            $user_groups = users_get_groups(false, 'AR');
            if (users_is_admin() === true || isset($user_groups[0]) === true) {
                $credentials = db_get_all_rows_sql(
                    'SELECT identifier FROM tcredential_store WHERE product LIKE "WMI"'
                );
            } else {
                $credentials = db_get_all_rows_sql(
                    sprintf(
                        'SELECT identifier FROM tcredential_store WHERE product LIKE "WMI" AND id_group IN (%s)',
                        implode(',', array_keys($user_groups))
                    )
                );
            }

            if (empty($credentials) === false) {
                $fields = [];
                foreach ($credentials as $key => $value) {
                    $fields[$value['identifier']] = $value['identifier'];
                }

                $inputs[] = [
                    'label'     => __('Credential store'),
                    'id'        => 'slc-credential',
                    'arguments' => [
                        'name'          => 'credential',
                        'input_class'   => 'flex-row',
                        'type'          => 'select',
                        'nothing'       => __('None'),
                        'nothing_value' => 0,
                        'fields'        => $fields,
                        'class'         => '',
                        'return'        => true,
                        'sort'          => true,
                    ],
                ];
            }
        }

        $hint_server = '&nbsp;';
        $hint_server .= ui_print_help_icon('agent_snmp_explorer_tab', true);
        $inputs[] = [
            'label'     => __('Server to execute command').$hint_server,
            'id'        => 'slc-server',
            'arguments' => [
                'name'        => 'server',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $fieldsServers,
                'class'       => '',
                'selected'    => $this->server,
                'return'      => true,
                'sort'        => false,
            ],
        ];

        if ($this->actionType === 'snmp') {
            $user_groups = users_get_groups(false, 'AR');
            if (users_is_admin() === true || isset($user_groups[0]) === true) {
                $credentials = db_get_all_rows_sql(
                    'SELECT identifier FROM tcredential_store WHERE product LIKE "SNMP"'
                );
            } else {
                $credentials = db_get_all_rows_sql(
                    sprintf(
                        'SELECT identifier FROM tcredential_store WHERE product LIKE "SNMP" AND id_group IN (%s)',
                        implode(',', array_keys($user_groups))
                    )
                );
            }

            if (empty($credentials) === false) {
                $fields = [];
                foreach ($credentials as $key => $value) {
                    $fields[$value['identifier']] = $value['identifier'];
                }

                $inputs[] = [
                    'label'     => __('Credential store'),
                    'id'        => 'slc-credential',
                    'arguments' => [
                        'name'          => 'credential',
                        'input_class'   => 'flex-row',
                        'type'          => 'select',
                        'nothing'       => __('None'),
                        'nothing_value' => 0,
                        'fields'        => $fields,
                        'class'         => '',
                        'return'        => true,
                        'sort'          => true,
                    ],
                ];
            }

            $inputs[] = [
                'label'     => __('SNMP community'),
                'id'        => 'txt-community',
                'arguments' => [
                    'name'        => 'community',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'size'        => '20',
                    'class'       => '',
                    'value'       => $this->community,
                    'return'      => true,
                ],
            ];

            // Fill with SNMP versions allowed.
            $fieldsVersions = [
                '1'  => '1',
                '2'  => '2',
                '2c' => '2c',
                '3'  => '3',
            ];

            $inputs[] = [
                'label'     => __('SNMP version'),
                'id'        => 'txt-version',
                'arguments' => [
                    'name'        => 'version',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'fields'      => $fieldsVersions,
                    'script'      => 'showV3Form();',
                    'class'       => '',
                    'selected'    => $this->version,
                    'return'      => true,
                ],
            ];
        }

        html_print_action_buttons(
            html_print_submit_button(
                $this->actionLabel,
                'sub-protocol',
                false,
                [
                    'icon'    => 'cog',
                    'onclick' => '$("#form-main-wizard").submit();',
                ],
                true
            )
        );

        // Prints main form.
        html_print_div(
            [
                'content' => $this->printForm(
                    [
                        'form'      => $form,
                        'inputs'    => $inputs,
                        'rawInputs' => '<ul class="wizard">'.$this->snmpAuthenticationForm().'</ul>',
                    ],
                    true
                ),
            ]
        );
    }


    /**
     * This form appears when activate SNMP v3
     *
     * @return mixed
     */
    public function snmpAuthenticationForm()
    {
        // Privacy method.
        $privacyMethod = [
            'AES' => 'AES',
            'DES' => 'DES',
        ];
        // Authentication method.
        $authenticationMethod = [
            'MD5' => 'MD5',
            'SHA' => 'SHA',
        ];
        // Security level.
        $securityLevel = [
            'authNoPriv'   => 'Authenticated and non-private method',
            'authPriv'     => 'Authenticated and private method',
            'noAuthNoPriv' => 'Non-authenticated and non-private method',
        ];
        // Main form.
        $form = [
            'action' => '',
            'id'     => 'form-snmp-authentication',
            'method' => 'POST',
        ];
        // Inputs.
        $inputs = [];

        $inputs[] = [
            'label'     => __('Security level'),
            'id'        => 'slc-securityLevelV3',
            'arguments' => [
                'name'        => 'securityLevelV3',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $securityLevel,
                'class'       => '',
                'script'      => 'showSecurityLevelForm();',
                'selected'    => $this->securityLevelV3,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('User authentication'),
            'id'        => 'txt-authUserV3',
            'arguments' => [
                'name'        => 'authUserV3',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'size'        => '20',
                'class'       => '',
                'value'       => $this->authUserV3,
                'return'      => true,
                'form'        => 'form-main-wizard',
            ],
        ];

        $inputs[] = [
            'label'     => __('Authentication method'),
            'id'        => 'txt-authMethodV3',
            'arguments' => [
                'name'        => 'authMethodV3',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $authenticationMethod,
                'class'       => '',
                'selected'    => $this->authMethodV3,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Password authentication'),
            'id'        => 'txt-authPassV3',
            'arguments' => [
                'name'        => 'authPassV3',
                'input_class' => 'flex-row',
                'type'        => 'password',
                'size'        => '20',
                'class'       => '',
                'value'       => $this->authPassV3,
                'return'      => true,
                'form'        => 'form-main-wizard',
            ],
        ];

        $inputs[] = [
            'label'     => __('Privacy method'),
            'id'        => 'txt-privacyMethodV3',
            'arguments' => [
                'name'        => 'privacyMethodV3',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $privacyMethod,
                'class'       => '',
                'selected'    => $this->privacyMethodV3,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Privacy pass'),
            'id'        => 'txt-privacyPassV3',
            'arguments' => [
                'name'   => 'privacyPassV3',
                'type'   => 'password',
                'size'   => '20',
                'class'  => '',
                'value'  => $this->privacyPassV3,
                'return' => true,
                'form'   => 'form-main-wizard',
            ],
        ];

        // Output the form.
        return html_print_div(
            [
                'id'      => 'form-snmp-authentication-box',
                'class'   => 'invisible',
                'style'   => 'margin-top: 10px;',
                'content' => $this->printForm(
                    [
                        'form'   => $form,
                        'inputs' => $inputs,
                    ],
                    true
                ),
            ],
            true
        );
    }


    /**
     * Perform a Interfaces SNMP Walk
     *
     * @param array $receivedOid Array with the raw oid info.
     *
     * @return void
     */
    public function performSNMPInterfaces($receivedOid)
    {
        // Path for get the IPs (ipv4).
        $snmpIpDiscover = '.1.3.6.1.2.1.4.34.1.4.1.4';
        $snmpIpIndexes  = '.1.3.6.1.2.1.4.34.1.3.1.4';

        $ipsResult = [];
        // In this case we need the full information provided by snmpwalk.
        $ipsResult = $this->snmpWalkValues($snmpIpDiscover, false, true);
        $indexes = $this->snmpWalkValues($snmpIpIndexes, false, true);

        $unicastIpReferences = [];
        foreach ($indexes as $k => $v) {
            $key = str_replace($snmpIpIndexes.'.', '', $k);
            // Only catch the unicast records.
            if ((preg_match('/unicast/', $ipsResult[$snmpIpDiscover.'.'.$key]) === 1)) {
                $value = explode(': ', $v)[1];
                $unicastIpReferences[$value] = $key;
            }
        }

        // Create a list with the interfaces.
        $interfaces = [];
        foreach ($receivedOid as $indexKey => $name) {
            if ($indexKey[0] === '.') {
                $indexKey = substr($indexKey, 1, strlen($indexKey));
            }

            // Set the name of interface.
            $interfaces[$indexKey]['name'] = $name;
            // Get the description.
            $interfaces[$indexKey]['descr'] = $this->snmpGetValue(
                '.1.3.6.1.2.1.2.2.1.2.'.$indexKey
            );
            // Get the MAC address.
            $interfaces[$indexKey]['mac'] = $this->snmpGetValue(
                '.1.3.6.1.2.1.2.2.1.6.'.$indexKey
            );
            // Get unicast IP address.
            $interfaces[$indexKey]['ip'] = '';
            if (isset($unicastIpReferences[$indexKey]) === true) {
                $interfaces[$indexKey]['ip'] = '';
            }

            // Get interface alias.
            $interfaces[$indexKey]['alias'] = $this->snmpGetValue(
                '.1.3.6.1.2.1.31.1.1.1.18.'.$indexKey
            );

            // Get interface speed.
            $interfaces[$indexKey]['speed'] = $this->snmpGetValue(
                '.1.3.6.1.2.1.2.2.1.5.'.$indexKey
            );
        }

        // Save the interfaces found for process later.
        $this->interfacesFound = $interfaces;
    }


    /**
     * Perform a General SNMP Walk
     *
     * @param array $receivedOid Array with the raw oid info.
     *
     * @return void
     */
    public function performSNMPGeneral($receivedOid)
    {
        // Getting the Symbolic Name of the OID.
        if (is_array($receivedOid) === false) {
            // No PEN.
            return;
        }

        // The PEN is hosted in the seventh position.
        $tmpPEN = explode('.', array_shift($receivedOid));
        $pen = $tmpPEN[7];

        // Then look in DB if the PEN is registered.
        $penFound = db_get_value('manufacturer', 'tpen', 'pen', $pen);
        if ($penFound === false) {
            // This PEN is not registered. Let's finish.
            $this->message['type'][]    = 'error';
            $this->message['message'][] = __(
                'The PEN (%s) is not registered.',
                $pen
            );
            return;
        } else {
            // Save the PEN for process later.
            $this->penName = $penFound;
        }
    }


    /**
     * Let's do a SNMP Walk
     *
     * @return void
     */
    public function performSNMP()
    {
        // If the target IP is empty, get it form the agent.
        if (empty($this->targetIp) === true) {
            $this->targetIp = db_get_value(
                'direccion',
                'tagente',
                'id_agente',
                $this->idAgent
            );
        }

        if ($this->wizardSection === 'snmp_interfaces_explorer') {
            // Check if thereis x64 counters.
            $snmp_tmp = '.1.3.6.1.2.1.31.1.1.1.6';
            $check_x64 = $this->snmpWalkValues(
                $snmp_tmp,
                false,
                true
            );

            if ($check_x64) {
                $this->interfacesx64 = true;
                $oidExplore = '.1.3.6.1.2.1.31.1.1.1.1';
            } else {
                $this->interfacesx64 = false;
                $oidExplore = '.1.3.6.1.2.1.2.2.1.2';
            }
        } else {
            // Get the device PEN.
            $oidExplore = '.1.3.6.1.2.1.1.2.0';
        }

        // Explore general or interfaces.
        $receivedOid = $this->snmpWalkValues(
            $oidExplore,
            false,
            false
        );

        // The snmpwalk return information.
        if (empty($receivedOid) === false) {
            if ($this->wizardSection === 'snmp_interfaces_explorer') {
                $this->performSNMPInterfaces($receivedOid);
            } else {
                $this->performSNMPGeneral($receivedOid);
            }
        } else {
            // If the snmpwalk returns nothing, finish the execution.
            $this->message['type'][]    = 'error';
            $this->message['message'][] = __(
                'The SNMP Walk does not return anything with the received arguments.'
            );
        }
    }


    /**
     * Let's do a WMI Exploration
     *
     * @return void
     */
    public function performWMI()
    {
        // DOC: Handling WMI Errors -->
        // https://docs.microsoft.com/en-us/windows/win32/wmisdk/wmi-error-constants
        // Capture the parameters.
        // Call WMI Explorer function.
        $this->wmiCommand = wmi_compose_query(
            $this->wmiBinary,
            $this->usernameWMI,
            $this->passwordWMI,
            $this->targetIp,
            $this->namespaceWMI
        );
        // Send basic query to target for check if
        // the host is Windows (and allow WMI).
        $commandQuery = $this->wmiCommand;
        $commandQuery .= ' "SELECT Caption FROM Win32_ComputerSystem"';
        // Declare the vars.
        $result = [];
        $returnVar = 0;
        $tmpError = '';
        $execCorrect = true;
        // Execute the command.
        exec($commandQuery, $result, $returnVar);
        // Only is valid if return code is 0.
        if ($returnVar === 0) {
            // Look for the response if we have ERROR messages.
            foreach ($result as $info) {
                if (preg_match('/ERROR:/', $info) !== 0) {
                    $execCorrect = false;
                    $tmpError = strrchr($info, 'ERROR:');
                    break;
                }
            }
        } else {
            $tmpError = sprintf('Return Code %s', $returnVar);
            $execCorrect = false;
        }

        // FOUND ERRORS: TIMEOUT.
        // [0] => [librpc/rpc/dcerpc_connect.c:790:dcerpc_pipe_connect_b_recv()]
        // failed NT status (c00000b5) in dcerpc_pipe_connect_b_recv.
        // [1] => [wmi/wmic.c:196:main()] ERROR: Login to remote object.
        // If execution gone fine.
        if ($execCorrect === true) {
            $this->moduleBlocks = $this->getModuleBlocks();
        } else {
            $this->message['type'][]    = 'error';
            $this->message['message'][] = sprintf(
                __('The target host response with an error: %s'),
                $tmpError
            );
        }
    }


    /**
     * Show list with info modules at create.
     *
     * @return void
     */
    public function listModulesToCreate()
    {
        $data = get_parameter('data', '');
        $data = json_decode(io_safe_output($data), true);

        $candidateModules = $this->candidateModuleToCreate($data);
        $this->sectionUrl = $this->baseUrl.'&wizard_section='.$this->wizardSection;

        $form = [
            'action' => $this->sectionUrl,
            'id'     => 'reviewed-modules',
            'method' => 'POST',
            'class'  => '',
            'extra'  => '',
        ];

        $inputs = [
            [
                'arguments' => [
                    'type'   => 'hidden',
                    'value'  => htmlspecialchars(json_encode($candidateModules)),
                    'return' => true,
                    'name'   => 'modules-definition',
                ],
            ],
        ];

        $inputs = array_merge($inputs, $this->getCommonDataInputs());

        $content = HTML::printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

        if (empty($candidateModules) === true) {
            echo ui_print_error_message(__('No selected modules'));
            return;
        }

        $table = new StdClass();

        $table->data = [];
        $table->width = '100%';
        $table->class = 'info_table';

        // Header section.
        $table->head = [];
        $table->head[0] = '<b>'.__('Module Name').'</b>';
        $table->head[1] = '<b>'.__('Server').'</b>';
        $table->head[2] = '<b>'.__('Type').'</b>';
        $table->head[3] = '<b>'.__('Description').'</b>';
        $table->head[4] = '<b>'.__('Threshold').'</b>';

        $table->data = [];

        $namesArray = [];
        $data = [];
        $i = 0;

        foreach ($candidateModules as $key => $module) {
            if (isset($namesArray[$module['name']]) === false) {
                $namesArray[$module['name']] = $module['name'];

                if (empty($this->idPolicy) === false) {
                    $sql = sprintf(
                        "SELECT id
                        FROM tpolicy_modules
                        WHERE id_policy = %d
                        AND `name` = '%s'",
                        $this->idPolicy,
                        io_safe_input($module['name'])
                    );
                    $msgError = __('Module exists in policy');
                } else {
                    $sql = sprintf(
                        "SELECT id_agente_modulo
                    FROM tagente_modulo
                    WHERE id_agente = %d
                    AND nombre = '%s'",
                        $this->idAgent,
                        io_safe_input($module['name'])
                    );
                    $msgError = __('Module exists in agent');
                }

                $existInDb = db_get_value_sql($sql);
            } else {
                $existInDb = true;
                $msgError = __(
                    'Module with the same name in the module creation list'
                );
            }

            $data[0] = $module['name'];
            if ($existInDb !== false) {
                $table->rowstyle[$i] = 'color:#ccc;';
                $data[0] .= ' ';
                $data[0] .= html_print_image(
                    'images/alert-warning@svg.svg',
                    true,
                    ['title' => $msgError]
                );
            }

            if ($this->server !== 0) {
                $this->serverType = (int) db_get_value(
                    'server_type',
                    'tserver',
                    'id_server',
                    $this->server
                );
            }

            // Img Server.
            if ($this->serverType == SERVER_TYPE_ENTERPRISE_SATELLITE) {
                $img_server = html_print_image(
                    'images/satellite@os.svg',
                    true,
                    [
                        'title' => __('Enterprise Satellite server'),
                        'class' => 'invert_filter',
                    ]
                );
            } else {
                if ($module['execution_type'] == EXECUTION_TYPE_PLUGIN) {
                    $img_server = html_print_image(
                        'images/plugins@svg.svg',
                        true,
                        [
                            'title' => __('Plugin server'),
                            'class' => 'invert_filter main_menu_icon',
                        ]
                    );
                } else {
                    if ($this->protocol === 'wmi') {
                        $img_server = html_print_image(
                            'images/WMI@svg.svg',
                            true,
                            [
                                'title' => __('WMI server'),
                                'class' => 'invert_filter main_menu_icon',
                            ]
                        );
                    } else {
                        $img_server = html_print_image(
                            'images/network@svg.svg',
                            true,
                            [
                                'title' => __('Network server'),
                                'class' => 'invert_filter main_menu_icon',
                            ]
                        );
                    }
                }
            }

            $data[1] = $img_server;

            $data[2] = \ui_print_moduletype_icon($module['moduleType'], true);

            $data[3] = mb_strimwidth(
                io_safe_output($module['description']),
                0,
                150,
                '...'
            );

            $data[4] = __('Warning').' ';
            $data[4] .= $module['warningMin'].' / '.$module['warningMax'];
            $data[4] .= '</br>';
            $data[4] .= __('Critical').' ';
            $data[4] .= $module['criticalMin'].' / '.$module['criticalMax'];

            array_push($table->data, $data);
            $i++;
        }

        $content .= html_print_table($table, true);

        echo $content;
    }


    /**
     * Build an array with Product credentials.
     *
     * @return array with credentials (pass and id).
     */
    public function getCredentials(string $identifier='')
    {
        if (empty($identifier) === true) {
            $identifier = get_parameter('identifier', '');
        }

        echo json_encode(credentialStore::getKey($identifier));
    }


    /**
     * Prepare data module to create.
     *
     * @param array $data Array Info module.
     *
     * @return array
     */
    public function candidateModuleToCreate(array $data): array
    {
        $modulesActivated = [];
        $generalInterface = false;
        // Lets catch all values.
        foreach ($data as $key => $value) {
            if (empty(preg_match('/module-active-(.*)/m', $key, $tmpModules)) === false
                && (int) $value === 1
            ) {
                $keyData = $tmpModules[1];

                $modulesActivated[] = $keyData;
            } else if (empty(preg_match('/interfaz_select_/', $key)) === false) {
                $tmpInterfaces = explode('interfaz_select_', $key);
                $interfaces[$tmpInterfaces[1]] = $tmpInterfaces[1];
            } else if (empty(preg_match('/box_enable_toggle/', $key)) === false) {
                $generalInterface = true;
            } else {
                if (property_exists($this, $key) === true) {
                    // Reinitialize received values.
                    $this->{$key} = $value;
                }
            }
        }

        $this->wizardSection = $data['wizard_section'];

        $result = [];
        // Only section snmp interfaces explorer.
        if ($data['wizard_section'] === 'snmp_interfaces_explorer') {
            if (isset($interfaces) === true
                && is_array($interfaces) === true
                && empty($interfaces) === false
                && isset($modulesActivated) === true
                && is_array($modulesActivated) === true
                && empty($modulesActivated) === false
            ) {
                foreach ($interfaces as $key => $value) {
                    $valueStr = preg_replace('/\//', '\/', $value);
                    foreach ($modulesActivated as $k => $v) {
                        if (preg_match('/^'.$valueStr.'_\d+-\d+$/', $v) == true) {
                            $tmp[$v] = $v;
                        } else if ($generalInterface === true
                            && preg_match('/^0_\d+-\d+$/', $v) == true
                        ) {
                            $id = preg_replace(
                                '/^0_/',
                                $value.'_',
                                $v
                            );
                            $tmp[$id] = $id;
                        }
                    }
                }
            } else {
                return $result;
            }

            $modulesActivated = $tmp;
        }

        /*
         * Before changing anything inside those loops take in mind, if you
         * change the module definition at interface level, those are the
         * values wich will be applied to final module.
         *
         * There is no 'parse first default then specific' or similar.
         *
         * $modulesActivated has the modules to be created with the information
         * retrieved from those specific modules.
         *
         * Only inherites from 'default' if you made no changes on module
         * definition (at javascript level before calling this function).
         */

        foreach ($modulesActivated as $key => $value) {
            foreach (array_keys($data) as $k) {
                if (isset($data[$k]) === false || $data[$k] === '') {
                    continue;
                }

                $valueStr = preg_replace('/\//', '\/', $value);

                if (empty(preg_match('/-'.$valueStr.'$/', $k)) === false
                    || empty(preg_match('/-'.$valueStr.'_sent$/', $k)) === false
                ) {
                    if (empty(preg_match('/module-name-set/', $k)) === false) {
                        $result[$value]['name'] = $data[$k];
                    } else if (empty(preg_match('/module-description-set/', $k)) === false) {
                        $result[$value]['description'] = $data[$k];
                    }

                    if ($data['wizard_section'] === 'snmp_interfaces_explorer') {
                        if (isset($data['module-active-'.$key]) === false
                            || (bool) $data['module-active-'.$key] === false
                        ) {
                            if (preg_match('/module-name-set/', $k) > 0) {
                                $result[$value]['name'] = $data['module-default_name-'.$key];
                                continue;
                            }

                            if (preg_match('/module-description-set/', $k) > 0) {
                                $result[$value]['description'] = $data['module-description-set-'.$key];
                                continue;
                            }

                            if (preg_match('/module-value/', $k) > 0) {
                                $result[$value]['value'] = $data['module-value-'.$key];
                                continue;
                            }

                            if (preg_match('/module-macros/', $k) > 0) {
                                $result[$value]['macros'] = $data['module-macros-'.$key];
                                continue;
                            }

                            if (preg_match('/module-id_plugin/', $k) > 0) {
                                $result[$value]['id_plugin'] = $data['module-id_plugin-'.$key];
                                continue;
                            }

                            if (preg_match('/module-id_modulo/', $k) > 0) {
                                $result[$value]['id_modulo'] = $data['module-id_modulo-'.$key];
                                continue;
                            }

                            if (preg_match('/module-unit/', $k) > 0) {
                                $result[$value]['unit'] = $data['module-unit-'.$key];
                                continue;
                            }

                            if (preg_match('/module-warning-min/', $k) > 0
                                && '' !== $data['module-warning-min-'.$key]
                            ) {
                                $result[$value]['warningMin'] = $data['module-warning-min-'.$key];
                                continue;
                            }

                            if (preg_match('/module-warning-max/', $k) > 0
                                && '' !== $data['module-warning-max-'.$key]
                            ) {
                                $result[$value]['warningMax'] = $data['module-warning-max-'.$key];
                                continue;
                            }

                            if (preg_match('/module-critical-min/', $k) > 0
                                && '' !== $data['module-critical-min-'.$key]
                            ) {
                                $result[$value]['criticalMin'] = $data['module-critical-min-'.$key];
                                continue;
                            }

                            if (preg_match('/module-critical-max/', $k) > 0
                                && '' !== $data['module-critical-max-'.$key]
                            ) {
                                $result[$value]['criticalMax'] = $data['module-critical-max-'.$key];
                                continue;
                            }

                            if (preg_match('/module-critical-inv/', $k) > 0
                                && isset($data['module-critical-inv-'.$key]) === true
                            ) {
                                $result[$value]['criticalInv'] = $data['module-critical-inv-'.$key.'_sent'];
                                continue;
                            }

                            if (preg_match('/module-warning-inv/', $k) > 0
                                && isset($data['module-warning-inv-'.$key]) === true
                            ) {
                                $result[$value]['warningInv'] = $data['module-warning-inv-'.$key.'_sent'];
                                continue;
                            }

                            if (preg_match('/module-warning-perc/', $k) > 0
                                && isset($data['module-warning-perc-'.$key]) === true
                            ) {
                                $result[$value]['warningPerc'] = $data['module-warning-perc-'.$key.'_sent'];
                                continue;
                            }

                            if (preg_match('/module-critical-perc/', $k) > 0
                                && isset($data['module-critical-perc-'.$key]) === true
                            ) {
                                $result[$value]['criticalPerc'] = $data['module-critical-perc-'.$key.'_sent'];
                                continue;
                            }

                            if (preg_match('/^(.*).*?_(\d+)-+(\d+)$/', $k, $matches) > 0) {
                                $k = $matches[1].'_'.$matches[2].'-'.$matches[3];
                            }
                        } else {
                            if (preg_match('/module-value/', $k) > 0
                                && empty($data[$k]) === false
                            ) {
                                $result[$value]['value'] = $data[$k];
                            }
                        }
                    }

                    // Specific customization only if switch is active.
                    if (preg_match('/module-warning-min/', $k) > 0) {
                        $result[$value]['warningMin'] = $data[$k];
                    } else if (preg_match('/module-warning-max/', $k) > 0) {
                        $result[$value]['warningMax'] = $data[$k];
                    } else if (preg_match('/module-critical-min/', $k) > 0) {
                        $result[$value]['criticalMin'] = $data[$k];
                    } else if (preg_match('/module-critical-max/', $k) > 0) {
                        $result[$value]['criticalMax'] = $data[$k];
                    } else if (preg_match('/module-critical-inv/', $k) > 0) {
                        $result[$value]['criticalInv'] = $data[$k.'_sent'];
                    } else if (preg_match('/module-warning-inv/', $k) > 0) {
                        $result[$value]['warningInv'] = $data[$k.'_sent'];
                    } else if (preg_match('/module-warning-perc/', $k) > 0) {
                        $result[$value]['warningPerc'] = $data[$k.'_sent'];
                    } else if (preg_match('/module-critical-perc/', $k) > 0) {
                         $result[$value]['criticalPerc'] = $data[$k.'_sent'];
                    } else if (preg_match('/module-type/', $k) > 0) {
                        $result[$value]['moduleType'] = $data[$k];
                    } else if (preg_match('/module-unit/', $k) > 0) {
                        $result[$value]['unit'] = $data[$k];
                    } else if (preg_match('/module-scan_type/', $k) > 0) {
                        $result[$value]['scan_type'] = (int) $data[$k];
                    } else if (preg_match('/module-execution_type/', $k) > 0) {
                        $result[$value]['execution_type'] = (int) $data[$k];
                    } else if (($data['wizard_section'] !== 'snmp_interfaces_explorer')
                        && preg_match('/module-value/', $k) > 0
                    ) {
                        $result[$value]['value'] = $data[$k];
                    } else if (preg_match('/module-macros/', $k) > 0) {
                        $result[$value]['macros'] = $data[$k];
                    } else if (preg_match('/module-name-oid/', $k) > 0) {
                        $result[$value]['nameOid'] = $data[$k];
                    } else if (preg_match('/module-query_class/', $k) > 0) {
                        $result[$value]['queryClass'] = $data[$k];
                    } else if (preg_match('/module-query_key_field/', $k) > 0) {
                        $result[$value]['queryKeyField'] = $data[$k];
                    } else if (preg_match('/module-scan_filters/', $k) > 0) {
                        $result[$value]['scanFilters'] = $data[$k];
                    } else if (preg_match('/module-query_filters/', $k) > 0) {
                        $result[$value]['queryFilters'] = $data[$k];
                    } else {
                        $result[$value][$k] = $data[$k];
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Process the information received for modules creation
     *
     * @return void
     */
    public function processModules()
    {
        $modulesCandidates = json_decode(
            io_safe_output(get_parameter('modules-definition', [])),
            true
        );

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->message['type'][] = 'error';
            $this->message['message'][] = json_last_error_msg();
            return;
        }

        if (empty($this->idPolicy) === false) {
            $this->processModulesPolicy($modulesCandidates);
        } else {
            $this->processModulesAgents($modulesCandidates);
        }
    }


    /**
     * Process the information received for modules creation in this policy.
     *
     * @param array $modulesCandidates Modules for create.
     *
     * @return void
     */
    public function processModulesPolicy(array $modulesCandidates)
    {
        $errorflag = false;
        foreach ($modulesCandidates as $candidate) {
            $sql = sprintf(
                "SELECT id
                FROM tpolicy_modules
                WHERE id_policy = %d
                AND `name` = '%s'",
                $this->idPolicy,
                io_safe_input($candidate['name'])
            );

            $existInDb = db_get_value_sql($sql);

            if ($existInDb !== false) {
                $this->message['type'][] = 'error';
                $this->message['message'][] = __(
                    'Module "%s" exits in this policy',
                    $candidate['name']
                );
                $errorflag = true;
                continue;
            }

            $value = [];
            $values['name'] = io_safe_input($candidate['name']);
            $values['description'] = io_safe_input($candidate['description']);
            $values['unit'] = $candidate['unit'];
            $values['id_tipo_modulo'] = $candidate['moduleType'];
            $values['id_policy'] = $this->idPolicy;
            $values['module_interval'] = 300;

            $nameTypeModule = modules_get_moduletype_name(
                $candidate['moduleType']
            );

            if ($this->protocol === 'snmp') {
                if ($candidate['execution_type'] === 0
                    || $candidate['execution_type'] === EXECUTION_TYPE_NETWORK
                ) {
                    if (substr($candidate['value'], 0, 1) !== '.') {
                        $candidate['value'] = '.'.$candidate['value'];
                    }

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $values['id_module'] = MODULE_DATA;
                        $values['module_interval'] = 1;

                        $cf = "module_begin\n";
                        $cf .= 'module_name '.$candidate['name']."\n";
                        $cf .= 'module_type '.$nameTypeModule."\n";
                        $cf .= "module_snmp\n";
                        $cf .= 'module_version '.$this->version."\n";
                        $cf .= 'module_oid '.$candidate['value']."\n";
                        $cf .= 'module_community '.$this->community."\n";
                        if ($this->version === '3') {
                            $cf .= 'module_seclevel '.$this->securityLevelV3."\n";
                            $cf .= 'module_secname '.$this->authUserV3."\n";

                            if ($this->securityLevelV3 === 'authNoPriv'
                                || $this->securityLevelV3 === 'authPriv'
                            ) {
                                $cf .= 'module_authproto '.$this->authMethodV3."\n";
                                $cf .= 'module_authpass '.$this->authPassV3."\n";
                                if ($this->securityLevelV3 === 'authPriv') {
                                    $cf .= 'module_privproto '.$this->privacyMethodV3."\n";
                                    $cf .= 'module_privpass '.$this->privacyPassV3."\n";
                                }
                            }
                        }

                        $cf .= 'module_end';
                        $values['configuration_data'] = io_safe_input($cf);
                    } else {
                        $values['id_module'] = MODULE_NETWORK;
                    }

                    $values['snmp_community'] = $this->community;
                    $values['tcp_send'] = $this->version;
                    $values['snmp_oid'] = $candidate['value'];
                    $values['tcp_port'] = $this->targetPort;
                    if ($this->version === '3') {
                        $values['custom_string_3'] = $this->securityLevelV3;
                        $values['plugin_user'] = $this->authUserV3;
                        if ($this->securityLevelV3 === 'authNoPriv'
                            || $this->securityLevelV3 === 'authPriv'
                        ) {
                            $values['plugin_parameter'] = $this->authMethodV3;
                            $values['plugin_pass'] = $this->authPassV3;
                            if ($this->securityLevelV3 === 'authPriv') {
                                $values['custom_string_1'] = $this->privacyMethodV3;
                                $values['custom_string_2'] = $this->privacyPassV3;
                            }
                        }
                    }
                } else if ($candidate['execution_type'] === EXECUTION_TYPE_PLUGIN) {
                    $infoMacros = json_decode(
                        base64_decode($candidate['macros']),
                        true
                    );

                    if (isset($infoMacros['macros']) === false
                        || is_array($infoMacros['macros']) === false
                    ) {
                        $infoMacros['macros'] = [];
                    }

                    if (isset($candidate['nameOid']) === true
                        && empty($candidate['nameOid']) === false
                    ) {
                        $infoMacros['macros']['_nameOID_'] = $candidate['nameOid'];
                    }

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $values['module_interval'] = 1;
                        if (empty($infoMacros['satellite_execution']) === true) {
                            // Already defined.
                            $this->message['type'][] = 'error';
                            $this->message['message'][] = __(
                                'Module %s module_exec not configuration',
                                $candidate['name']
                            );

                            $errorflag = true;
                            continue;
                        }

                        $moduleExec = $this->replacementMacrosPlugin(
                            $infoMacros['satellite_execution'],
                            $infoMacros['macros']
                        );

                        $values['id_module'] = MODULE_DATA;
                        $cfData = "module_begin\n";
                        $cfData .= 'module_name '.$candidate['name']."\n";
                        $cfData .= 'module_type '.$nameTypeModule."\n";
                        $cfData .= 'module_exec '.io_safe_output($moduleExec)."\n";
                        $cfData .= 'module_end';
                        $values['configuration_data'] = io_safe_input($cfData);
                    } else {
                        $values['ip_target'] = '_address_';
                        $values['id_module'] = MODULE_PLUGIN;
                        $fieldsPlugin = db_get_value_sql(
                            sprintf(
                                'SELECT macros FROM tplugin WHERE id=%d',
                                (int) $infoMacros['server_plugin']
                            )
                        );

                        if ($this->wizardSection === 'snmp_interfaces_explorer'
                            && empty($candidate['macros']) === false
                        ) {
                            // Use definition provided.
                            $values['id_plugin'] = $candidate['id_plugin'];
                            $values['macros'] = base64_decode($candidate['macros']);
                        } else {
                            $fieldsPlugin = db_get_value_sql(
                                sprintf(
                                    'SELECT macros FROM tplugin WHERE id=%d',
                                    (int) $infoMacros['server_plugin']
                                )
                            );

                            if ($fieldsPlugin !== false) {
                                $fieldsPlugin = json_decode($fieldsPlugin, true);
                                $i = 1;
                                foreach ($infoMacros as $key => $value) {
                                    if (empty(preg_match('/_snmp_field/', $key)) === false) {
                                        $new_macros = [];
                                        foreach ($fieldsPlugin as $k => $v) {
                                            if ($v['macro'] === preg_replace('/_snmp_field/', '', $key)) {
                                                $fieldsPlugin[$k]['value'] = $this->replacementMacrosPlugin(
                                                    $value,
                                                    $infoMacros['macros']
                                                );
                                                $i++;
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }

                            $values['id_plugin'] = $infoMacros['server_plugin'];
                            $values['macros'] = json_encode($fieldsPlugin);
                        }
                    }
                }
            } else if ($this->protocol === 'wmi') {
                if ($candidate['execution_type'] === EXECUTION_TYPE_NETWORK) {
                    // Generals fields.
                    $values['plugin_user'] = io_safe_input($this->usernameWMI);
                    $values['plugin_pass'] = io_safe_input($this->passwordWMI);
                    $values['tcp_send'] = io_safe_input($this->namespaceWMI);

                    // Build query WMI.
                    $dataWMI = [
                        'query_class'     => $candidate['queryClass'],
                        'query_filters'   => io_safe_output(
                            base64_decode(
                                $candidate['queryFilters']
                            )
                        ),
                        'macros'          => base64_decode(
                            $candidate['macros']
                        ),
                        'query_key_field' => $candidate['queryKeyField'],
                    ];

                    $candidate['wmi_query'] = $this->wmiQuery(
                        $dataWMI,
                        'execution',
                        true
                    );

                    $queryFilters = json_decode(
                        base64_decode(
                            $candidate['queryFilters']
                        ),
                        true
                    );

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $values['module_interval'] = 1;
                        $values['id_module'] = MODULE_DATA;

                        $cfData = "module_begin\n";
                        $cfData .= 'module_name '.$candidate['name']."\n";
                        $cfData .= 'module_type '.$nameTypeModule."\n";
                        $cfData .= "module_wmi\n";
                        $cfData .= 'module_wmiquery '.$candidate['wmi_query']."\n";
                        $cfData .= 'module_wmicolumn '.(isset($queryFilters['field']) === true) ? $queryFilters['field'] : (0)."\n";
                        $cfData .= 'module_wmiauth '.$this->usernameWMI.'%'.$this->passwordWMI."\n";
                        $cfData .= 'module_end';
                        $values['configuration_data'] = io_safe_input($cfData);
                    } else {
                        $values['id_module'] = MODULE_WMI;
                    }

                    $values['snmp_oid'] = io_safe_input(
                        $candidate['wmi_query']
                    );

                    $values['tcp_port'] = (isset($queryFilters['field']) === true) ? $queryFilters['field'] : 0;
                    $values['snmp_community'] = (isset($queryFilters['key_string']) === true) ? $queryFilters['key_string'] : '';
                } else if ($candidate['execution_type'] === EXECUTION_TYPE_PLUGIN) {
                    $infoMacros = json_decode(
                        base64_decode($candidate['macros']),
                        true
                    );

                    if (isset($infoMacros['macros']) === false
                        || is_array($infoMacros['macros']) === false
                    ) {
                        $infoMacros['macros'] = [];
                    }

                    if (isset($candidate['queryClass']) === true
                        && empty($candidate['queryClass']) === false
                    ) {
                        $infoMacros['macros']['_class_wmi_'] = $candidate['queryClass'];
                    }

                    if (isset($candidate['queryKeyField']) === true
                        && empty($candidate['queryKeyField']) === false
                    ) {
                        $infoMacros['macros']['_field_wmi_0_'] = $candidate['queryKeyField'];
                    }

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $values['module_interval'] = 1;
                        if (empty($infoMacros['satellite_execution']) === true) {
                            // Already defined.
                            $this->message['type'][] = 'error';
                            $this->message['message'][] = __(
                                'Module %s satellite execution not configuration',
                                $candidate['name']
                            );
                            $errorflag = true;
                            continue;
                        }

                        $moduleExec = $this->replacementMacrosPlugin(
                            $infoMacros['satellite_execution'],
                            $infoMacros['macros']
                        );

                        $values['id_module'] = MODULE_DATA;
                        $cfData = "module_begin\n";
                        $cfData .= 'module_name '.$candidate['name']."\n";
                        $cfData .= 'module_type '.$nameTypeModule."\n";
                        $cfData .= 'module_exec '.io_safe_output($moduleExec)."\n";
                        $cfData .= 'module_end';
                        $values['configuration_data'] = io_safe_input($cfData);
                    } else {
                        $values['id_module'] = MODULE_PLUGIN;
                        if ((int) $infoMacros['server_plugin'] === 12) {
                            // Search plugin by execute.
                            $plugin_wmi = db_get_row_sql(
                                'SELECT id, macros FROM tplugin WHERE execute like "%wizard_wmi_module%"'
                            );
                            $fieldsPlugin = $plugin_wmi['macros'];
                            $infoMacros['server_plugin'] = $plugin_wmi['id'];
                        } else {
                            $fieldsPlugin = db_get_value_sql(
                                sprintf(
                                    'SELECT macros FROM tplugin WHERE id=%d',
                                    (int) $infoMacros['server_plugin']
                                )
                            );
                        }

                        if ($fieldsPlugin !== false) {
                            $fieldsPlugin = json_decode($fieldsPlugin, true);
                            $i = 1;
                            foreach ($infoMacros as $key => $value) {
                                if (empty(preg_match('/_wmi_field/', $key)) === false) {
                                    $new_macros = [];
                                    foreach ($fieldsPlugin as $k => $v) {
                                        if ($v['macro'] === preg_replace('/_wmi_field/', '', $key)) {
                                            $fieldsPlugin[$k]['value'] = $this->replacementMacrosPlugin(
                                                $value,
                                                $infoMacros['macros']
                                            );
                                            $i++;
                                            continue;
                                        }
                                    }
                                }
                            }
                        }

                        $values['id_plugin'] = $infoMacros['server_plugin'];
                        $values['macros'] = json_encode($fieldsPlugin);
                    }

                    $values['ip_target'] = '_address_';
                    $values['snmp_oid'] = io_safe_input(
                        $candidate['wmi_query']
                    );
                }
            }

            if (preg_match('/string/', $nameTypeModule) === true) {
                // String module.
                $values['str_warning'] = io_safe_input(
                    $candidate['warningMax']
                );
                $values['str_critical'] = io_safe_input(
                    $candidate['criticalMax']
                );
            } else {
                // Numeric module.
                $values['min_warning'] = $candidate['warningMin'];
                $values['max_warning'] = $candidate['warningMax'];
                $values['min_critical'] = $candidate['criticalMin'];
                $values['max_critical'] = $candidate['criticalMax'];
                $values['percentage_warning'] = $candidate['warningPerc'];
                $values['percentage_critical'] = $candidate['criticalPerc'];
            }

            $values['warning_inverse'] = $candidate['warningInv'];
            $values['critical_inverse'] = $candidate['criticalInv'];

            // Insert modules.
            $result = policies_create_module(
                $values['name'],
                $this->idPolicy,
                $values['id_module'],
                $values
            );

            if ($result === false) {
                $errorflag = true;
                $this->message['type'][] = 'error';
                $this->message['message'][] = __(
                    'Module "%s" problems insert in bbdd',
                    $candidate['name']
                );
            }
        }

        if ($errorflag === false) {
            $this->message['type'][] = 'success';
            $this->message['message'][] = __('Modules created');
        }
    }


    /**
     * Process the information received for modules creation in this agent.
     *
     * @param array $modulesCandidates Modules for create.
     *
     * @return void
     */
    public function processModulesAgents(array $modulesCandidates)
    {
        $modules = [];
        $errorflag = false;

        foreach ($modulesCandidates as $candidate) {
            $tmp = Module::search(
                [
                    'nombre'    => io_safe_input($candidate['name']),
                    'id_agente' => $this->idAgent,
                ],
                1
            );

            if ($tmp !== null) {
                // Already defined.
                $this->message['type'][] = 'error';
                $this->message['message'][] = __(
                    'Module "%s" exists in this agent',
                    $candidate['name']
                );
                $errorflag = true;
                continue;
            }

            // Not found, it is new.
            $tmp = new Module();
            $tmp->nombre(io_safe_input($candidate['name']));
            $tmp->descripcion(io_safe_input($candidate['description']));
            $tmp->unit($candidate['unit']);
            $tmp->id_tipo_modulo($candidate['moduleType']);
            $tmp->id_agente($this->idAgent);
            $tmp->module_interval(agents_get_interval($this->idAgent));

            if ($this->protocol === 'snmp') {
                if ($candidate['execution_type'] === 0
                    || $candidate['execution_type'] === EXECUTION_TYPE_NETWORK
                ) {
                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        if (substr($candidate['value'], 0, 1) !== '.') {
                            $candidate['value'] = '.'.$candidate['value'];
                        }

                        $tmp->module_interval(300);
                        $tmp->id_modulo(MODULE_DATA);
                        $tmp->updateConfigurationData(
                            'module_name',
                            $candidate['name']
                        );
                        $tmp->updateConfigurationData(
                            'module_type',
                            $tmp->moduleType()->nombre()
                        );
                        $tmp->updateConfigurationData(
                            'module_snmp',
                            $this->targetIp
                        );
                        $tmp->updateConfigurationData(
                            'module_version',
                            $this->version
                        );
                        $tmp->updateConfigurationData(
                            'module_oid',
                            $candidate['value']
                        );
                        $tmp->updateConfigurationData(
                            'module_community',
                            $this->community
                        );

                        if ($this->version === '3') {
                            $tmp->updateConfigurationData(
                                'module_seclevel',
                                $this->securityLevelV3
                            );
                            $tmp->updateConfigurationData(
                                'module_secname',
                                $this->authUserV3
                            );

                            if ($this->securityLevelV3 === 'authNoPriv'
                                || $this->securityLevelV3 === 'authPriv'
                            ) {
                                $tmp->updateConfigurationData(
                                    'module_authproto',
                                    $this->authMethodV3
                                );
                                $tmp->updateConfigurationData(
                                    'module_authpass',
                                    $this->authPassV3
                                );
                                if ($this->securityLevelV3 === 'authPriv') {
                                    $tmp->updateConfigurationData(
                                        'module_privproto',
                                        $this->privacyMethodV3
                                    );
                                    $tmp->updateConfigurationData(
                                        'module_privpass',
                                        $this->privacyPassV3
                                    );
                                }
                            }
                        }
                    } else {
                        $tmp->id_modulo(MODULE_NETWORK);
                    }

                    $tmp->ip_target($this->targetIp);
                    $tmp->snmp_community($this->community);
                    $tmp->tcp_send($this->version);
                    $tmp->snmp_oid($candidate['value']);
                    $tmp->tcp_port($this->targetPort);
                    if ($this->version === '3') {
                        $tmp->custom_string_3($this->securityLevelV3);
                        $tmp->plugin_user($this->authUserV3);
                        if ($this->securityLevelV3 === 'authNoPriv'
                            || $this->securityLevelV3 === 'authPriv'
                        ) {
                            $tmp->plugin_parameter($this->authMethodV3);
                            $tmp->plugin_pass($this->authPassV3);
                            if ($this->securityLevelV3 === 'authPriv') {
                                $tmp->custom_string_1($this->privacyMethodV3);
                                $tmp->custom_string_2($this->privacyPassV3);
                            }
                        }
                    }
                } else if ($candidate['execution_type'] === EXECUTION_TYPE_PLUGIN) {
                    $infoMacros = json_decode(
                        base64_decode($candidate['macros']),
                        true
                    );

                    if (isset($infoMacros['macros']) === false
                        || is_array($infoMacros['macros']) === false
                    ) {
                        $infoMacros['macros'] = [];
                    }

                    if (isset($candidate['nameOid']) === true
                        && empty($candidate['nameOid']) === false
                    ) {
                        $infoMacros['macros']['_nameOID_'] = $candidate['nameOid'];
                    }

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $tmp->module_interval(300);
                        $tmp->id_modulo(MODULE_DATA);
                        $tmp->updateConfigurationData(
                            'module_name',
                            $candidate['name']
                        );
                        $tmp->updateConfigurationData(
                            'module_type',
                            $tmp->moduleType()->nombre()
                        );

                        if (empty($infoMacros['satellite_execution']) === true) {
                            // Already defined.
                            $this->message['type'][] = 'error';
                            $this->message['message'][] = __(
                                'Module %s module_exec not configuration',
                                $candidate['name']
                            );

                            $errorflag = true;
                            continue;
                        }

                        $tmp->updateConfigurationData(
                            'module_exec',
                            io_safe_output(
                                $this->replacementMacrosPlugin(
                                    $infoMacros['satellite_execution'],
                                    $infoMacros['macros']
                                )
                            )
                        );
                    } else {
                        $tmp->ip_target($this->targetIp);
                        $tmp->id_modulo(MODULE_PLUGIN);

                        if ($this->wizardSection === 'snmp_interfaces_explorer'
                            && empty($candidate['macros']) === false
                        ) {
                            // Use definition provided.
                            $tmp->id_plugin($candidate['id_plugin']);
                            $tmp->macros(base64_decode($candidate['macros']));
                        } else {
                            $fieldsPlugin = db_get_value_sql(
                                sprintf(
                                    'SELECT macros FROM tplugin WHERE id=%d',
                                    (int) $infoMacros['server_plugin']
                                )
                            );

                            if ($fieldsPlugin !== false) {
                                $fieldsPlugin = json_decode($fieldsPlugin, true);
                                $i = 1;
                                foreach ($infoMacros as $key => $value) {
                                    if (empty(preg_match('/_snmp_field/', $key)) === false) {
                                        $new_macros = [];
                                        foreach ($fieldsPlugin as $k => $v) {
                                            if ($v['macro'] === preg_replace('/_snmp_field/', '', $key)) {
                                                $fieldsPlugin[$k]['value'] = $this->replacementMacrosPlugin(
                                                    $value,
                                                    $infoMacros['macros']
                                                );
                                                $i++;
                                                continue;
                                            }
                                        }
                                    }
                                }
                            }

                            $tmp->id_plugin($infoMacros['server_plugin']);
                            $tmp->macros(io_json_mb_encode($fieldsPlugin));
                        }
                    }
                }
            } else if ($this->protocol === 'wmi') {
                if ($candidate['execution_type'] === EXECUTION_TYPE_NETWORK) {
                    // Generals fields.
                    $tmp->plugin_user(io_safe_input($this->usernameWMI));
                    $tmp->plugin_pass(io_safe_input($this->passwordWMI));
                    $tmp->tcp_send(io_safe_input($this->namespaceWMI));
                    $tmp->ip_target(io_safe_input($this->targetIp));

                    // Build query WMI.
                    $dataWMI = [
                        'query_class'     => $candidate['queryClass'],
                        'query_filters'   => io_safe_output(
                            base64_decode(
                                $candidate['queryFilters']
                            )
                        ),
                        'macros'          => base64_decode(
                            $candidate['macros']
                        ),
                        'query_key_field' => $candidate['queryKeyField'],
                    ];

                    $candidate['wmi_query'] = $this->wmiQuery(
                        $dataWMI,
                        'execution',
                        true
                    );

                    $queryFilters = json_decode(
                        base64_decode(
                            $candidate['queryFilters']
                        ),
                        true
                    );

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $tmp->module_interval(300);
                        $tmp->id_modulo(MODULE_DATA);
                        $tmp->updateConfigurationData(
                            'module_name',
                            $candidate['name']
                        );
                        $tmp->updateConfigurationData(
                            'module_type',
                            $tmp->moduleType()->nombre()
                        );
                        $tmp->updateConfigurationData(
                            'module_wmi',
                            $this->targetIp
                        );
                        $tmp->updateConfigurationData(
                            'module_wmiquery',
                            $candidate['wmi_query']
                        );
                        $tmp->updateConfigurationData(
                            'module_wmiauth',
                            $this->usernameWMI.'%'.$this->passwordWMI
                        );
                        $tmp->updateConfigurationData(
                            'module_wmicolumn',
                            (isset($queryFilters['field']) === true) ? $queryFilters['field'] : 0
                        );
                    } else {
                        $tmp->id_modulo(MODULE_WMI);
                    }

                    $tmp->snmp_oid(io_safe_input($candidate['wmi_query']));

                    $tmp->tcp_port(
                        (isset($queryFilters['field']) === true) ? $queryFilters['field'] : 0
                    );

                    $tmp->snmp_community(
                        (isset($queryFilters['key_string']) === true) ? $queryFilters['key_string'] : ''
                    );
                } else if ($candidate['execution_type'] === EXECUTION_TYPE_PLUGIN) {
                    $infoMacros = json_decode(
                        base64_decode($candidate['macros']),
                        true
                    );

                    if (isset($infoMacros['macros']) === false
                        || is_array($infoMacros['macros']) === false
                    ) {
                        $infoMacros['macros'] = [];
                    }

                    if (isset($candidate['queryClass']) === true
                        && empty($candidate['queryClass']) === false
                    ) {
                        $infoMacros['macros']['_class_wmi_'] = $candidate['queryClass'];
                    }

                    if (isset($candidate['queryKeyField']) === true
                        && empty($candidate['queryKeyField']) === false
                    ) {
                        $infoMacros['macros']['_field_wmi_0_'] = $candidate['queryKeyField'];
                    }

                    if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                        $tmp->module_interval(300);
                        $tmp->id_modulo(MODULE_DATA);
                        $tmp->updateConfigurationData(
                            'module_name',
                            $candidate['name']
                        );
                        $tmp->updateConfigurationData(
                            'module_type',
                            $tmp->moduleType()->nombre()
                        );

                        if (empty($infoMacros['satellite_execution']) === true) {
                            // Already defined.
                            $this->message['type'][] = 'error';
                            $this->message['message'][] = __(
                                'Module %s satellite execution not configuration',
                                $candidate['name']
                            );
                            $errorflag = true;
                            continue;
                        }

                        $tmp->updateConfigurationData(
                            'module_exec',
                            io_safe_output(
                                $this->replacementMacrosPlugin(
                                    $infoMacros['satellite_execution'],
                                    $infoMacros['macros']
                                )
                            )
                        );
                    } else {
                        $tmp->id_modulo(MODULE_PLUGIN);
                        if ((int) $infoMacros['server_plugin'] === 12) {
                            // Search plugin by execute.
                            $plugin_wmi = db_get_row_sql(
                                'SELECT id, macros FROM tplugin WHERE execute like "%wizard_wmi_module%"'
                            );
                            $fieldsPlugin = $plugin_wmi['macros'];
                            $infoMacros['server_plugin'] = $plugin_wmi['id'];
                        } else {
                            $fieldsPlugin = db_get_value_sql(
                                sprintf(
                                    'SELECT macros FROM tplugin WHERE id=%d',
                                    (int) $infoMacros['server_plugin']
                                )
                            );
                        }

                        if ($fieldsPlugin !== false) {
                            $fieldsPlugin = json_decode($fieldsPlugin, true);
                            $i = 1;
                            foreach ($infoMacros as $key => $value) {
                                if (empty(preg_match('/_wmi_field/', $key)) === false) {
                                    $new_macros = [];
                                    foreach ($fieldsPlugin as $k => $v) {
                                        if ($v['macro'] === preg_replace('/_wmi_field/', '', $key)) {
                                            $fieldsPlugin[$k]['value'] = $this->replacementMacrosPlugin(
                                                $value,
                                                $infoMacros['macros']
                                            );
                                            $i++;
                                            continue;
                                        }
                                    }
                                }
                            }
                        }

                        $tmp->id_plugin($infoMacros['server_plugin']);
                        $tmp->macros(io_json_mb_encode($fieldsPlugin));
                    }

                    $tmp->ip_target(io_safe_input($this->targetIp));
                    $tmp->snmp_oid(io_safe_input($candidate['wmi_query']));
                }
            }

            if (preg_match('/string/', $tmp->moduleType()->nombre()) === true) {
                // String module.
                $tmp->str_warning(io_safe_input($candidate['warningMax']));
                $tmp->str_critical(io_safe_input($candidate['criticalMax']));
            } else {
                // Numeric module.
                $tmp->min_warning($candidate['warningMin']);
                $tmp->max_warning($candidate['warningMax']);
                $tmp->min_critical($candidate['criticalMin']);
                $tmp->max_critical($candidate['criticalMax']);
                $tmp->percentage_warning($candidate['warningPerc']);
                $tmp->percentage_critical($candidate['criticalPerc']);
            }

            $tmp->warning_inverse($candidate['warningInv']);
            $tmp->critical_inverse($candidate['criticalInv']);

            // Insert modules.
            try {
                $res = $tmp->save();
            } catch (\Exception $e) {
                $errorflag = true;
                $this->message['type'][] = 'error';
                $this->message['message'][] = $e->getMessage();
            }
        }

        if ($errorflag === false) {
            $this->message['type'][] = 'success';
            $this->message['message'][] = __('Modules created');
        }
    }


    /**
     * Replacement macros.
     *
     * @param string $text   String.
     * @param array  $macros Macros for replacement.
     *
     * @return string Retun string to replacement.
     */
    private function replacementMacrosPlugin(
        string $text,
        array $macros
    ): string {
        // Only agents.
        if (empty($this->idPolicy) === true) {
            // Common.
            $text = preg_replace('/_address_/', $this->targetIp, $text);
        }

        // WMI.
        $text = preg_replace('/_user_wmi_/', $this->usernameWMI, $text);
        $text = preg_replace('/_namespace_wmi_/', $this->namespaceWMI, $text);
        $text = preg_replace('/_pass_wmi_/', $this->passwordWMI, $text);

        // SNMP.
        $text = preg_replace('/_port_/', $this->targetPort, $text);
        $text = preg_replace('/_version_/', $this->version, $text);
        $text = preg_replace('/_community_/', $this->community, $text);
        $text = preg_replace('/_auth_user_/', $this->authUserV3, $text);
        $text = preg_replace('/_auth_pass_/', $this->authPassV3, $text);
        $text = preg_replace('/_auth_method_/', $this->authMethodV3, $text);
        $text = preg_replace('/_priv_method_/', $this->privacyMethodV3, $text);
        $text = preg_replace('/_priv_pass_/', $this->privacyPassV3, $text);
        $text = preg_replace('/_sec_level_/', $this->securityLevelV3, $text);

        // Dinamic.
        if (empty($macros) === false) {
            foreach ($macros as $key => $value) {
                $text = preg_replace('/'.$key.'/', $value, $text);
            }
        }

        return $text;
    }


    /**
     * Value with unit.
     *
     * @param string|null  $value      Value.
     * @param string|null  $unit       Type unit.
     * @param integer|null $moduleType Type Module.
     *
     * @return string
     */
    private function replacementUnit(
        ?string $value,
        ?string $unit='',
        ?int $moduleType=0
    ): string {
        if ($moduleType !== MODULE_TYPE_REMOTE_SNMP_INC
            && $moduleType !== MODULE_TYPE_GENERIC_DATA_INC
            && $moduleType !== MODULE_TYPE_REMOTE_TCP_INC
            && $moduleType !== MODULE_TYPE_REMOTE_CMD_INC
        ) {
            if ($unit === '_timeticks_') {
                preg_match('/\((\d+?)\)/', $value, $match);
                if (isset($match[1]) === true) {
                    $value = human_milliseconds_to_string($match[1]);
                } else {
                    $value = human_milliseconds_to_string($value);
                }
            } else if (empty($unit) === false && $unit !== 'none') {
                $value .= ' '.$unit;
            }
        }

        // If value comes empty, must return a "Empty" value for view it in console.
        $value = (empty($value) === true) ? '<i>'.__('Empty').'</i>' : $value;

        return $value;
    }


    /**
     * Perform Interface Wizard and show a table with results.
     *
     * @return void
     */
    private function resultsInterfaceWizard()
    {
        global $config;

        $generalInterfaceModules = $this->getInterfacesModules();
        $generalInterfaceTables = [];
        $generalInterfaceModulesUpdated = [];
        $component_id_number = 0;
        foreach ($generalInterfaceModules as $moduleIndex => $moduleData) {
            if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP) {
                    $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA;
                } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_PROC) {
                    $moduleData['module_type'] = MODULE_TYPE_GENERIC_PROC;
                } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_INC) {
                    $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA_INC;
                } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_STRING) {
                    // MODULE_TYPE_REMOTE_SNMP_STRING.
                    $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA_STRING;
                }
            }

            // Get current value.
            if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE
                || in_array(
                    $moduleData['module_type'],
                    [
                        MODULE_TYPE_REMOTE_SNMP,
                        MODULE_TYPE_REMOTE_SNMP_INC,
                        MODULE_TYPE_REMOTE_SNMP_STRING,
                        MODULE_TYPE_REMOTE_SNMP_PROC,
                    ]
                ) === true
            ) {
                if (isset($moduleData['value']) === true) {
                    $currentValue = $this->snmpGetValue($moduleData['value']);
                }
            }

            // It unit of measure have data, attach to current value.
            if (empty($moduleData['module_unit']) === false) {
                $currentValue .= ' '.$moduleData['module_unit'];
            }

            // Stablish the data for show.
            $generalInterfaceModulesUpdated[] = [
                'component_id'   => $component_id_number++,
                'execution_type' => $moduleData['execution_type'],
                'name'           => $moduleData['module_name'],
                'type'           => $moduleData['module_type'],
                'description'    => $moduleData['module_info'],
                'min_warning'    => $moduleData['module_thresholds']['min_warning'],
                'max_warning'    => $moduleData['module_thresholds']['max_warning'],
                'inv_warning'    => $moduleData['module_thresholds']['inv_warning'],
                'min_critical'   => $moduleData['module_thresholds']['min_critical'],
                'max_critical'   => $moduleData['module_thresholds']['max_critical'],
                'inv_critical'   => $moduleData['module_thresholds']['inv_critical'],
                'perc_warning'   => $moduleData['module_thresholds']['perc_warning'],
                'perc_critical'  => $moduleData['module_thresholds']['perc_critical'],
                'module_enabled' => $moduleData['default_enabled'],
                'name_oid'       => $moduleData['value'],
                'value'          => $moduleData['value'],
                'id_plugin'      => $moduleData['id_plugin'],
                'macros'         => $moduleData['macros'],
                'id_modulo'      => $moduleData['id_modulo'],
            ];
        }

        $generalInterfaceTables[0]['data'] = $generalInterfaceModulesUpdated;

        // General Default monitoring.
        html_print_div(
            [
                'class'   => 'wizard wizard-result',
                'style'   => 'margin-top: 20px;',
                'content' => $this->toggleTableModules(
                    $generalInterfaceTables,
                    false,
                    true,
                    true
                ),
            ]
        );

        // Interface filter.
        $form = [
            'action' => $this->sectionUrl,
            'id'     => 'form-filter-interfaces',
            'method' => 'POST',
            'class'  => 'modal searchbox',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [
            [
                'direct'        => 1,
                'class'         => 'select-interfaces',
                'block_content' => [
                    [
                        'label'     => __('Select all filtered interfaces'),
                        'arguments' => [
                            'name'    => 'select-all-interfaces',
                            'type'    => 'switch',
                            'class'   => '',
                            'return'  => true,
                            'value'   => 1,
                            'onclick' => 'switchBlockControlInterfaces(this);',
                        ],
                    ],
                ],
            ],
            [
                'label'     => __('Search'),
                'id'        => 'txt-filter-search',
                'class'     => 'textbox',
                'arguments' => [
                    'name'   => 'filter-search',
                    'type'   => 'text',
                    'return' => true,
                ],
            ],
            [
                'label'     => __('OperStatus UP'),
                'arguments' => [
                    'name'     => 'search-oper',
                    'type'     => 'switch',
                    'id'       => 'search-oper',
                    'onchange' => 'filterInterfaces()',
                    'value'    => 0,
                    'return'   => true,
                ],
            ],
            [
                'label'     => __('AdminStatus UP'),
                'arguments' => [
                    'name'     => 'search-admin',
                    'type'     => 'switch',
                    'id'       => 'search-admin',
                    'onchange' => 'filterInterfaces()',
                    'value'    => 0,
                    'return'   => true,
                ],
            ],
        ];

        // Print the filter form.
        $filterForm = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
                true
            ],
            true
        );

        html_print_div(
            [
                'class'   => 'white_box',
                'style'   => 'margin-top: 20px;',
                'content' => $filterForm,
            ]
        );

        $interfaceTables = [];
        // Build the information of the blocks.
        foreach ($this->interfacesFound as $index => $interface) {
            // Add the index position of this interface.
            $interface['index'] = $index;

            if (key_exists($interface['name'], $interfaceTables) === false) {
                $interfaceTables[$interface['name']] = [
                    'name' => $interface['name'],
                    'data' => [],
                ];
            }

            $thisInterfaceModules = $this->getInterfacesModules($interface);

            $interfaceModulesUpdated = [];
            $component_id_number = 0;
            foreach ($thisInterfaceModules as $moduleIndex => $moduleData) {
                if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                    if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP) {
                        $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA;
                    } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_PROC) {
                        $moduleData['module_type'] = MODULE_TYPE_GENERIC_PROC;
                    } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_INC) {
                        $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA_INC;
                    } else if ($moduleData['module_type'] == MODULE_TYPE_REMOTE_SNMP_STRING) {
                        // MODULE_TYPE_REMOTE_SNMP_STRING.
                        $moduleData['module_type'] = MODULE_TYPE_GENERIC_DATA_STRING;
                    }
                }

                // Get current value.
                $currentValue = '';

                if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE
                    || in_array(
                        $moduleData['module_type'],
                        [
                            MODULE_TYPE_REMOTE_SNMP,
                            MODULE_TYPE_REMOTE_SNMP_INC,
                            MODULE_TYPE_REMOTE_SNMP_STRING,
                            MODULE_TYPE_REMOTE_SNMP_PROC,
                        ]
                    ) === true
                ) {
                    if (isset($moduleData['value']) === true) {
                        $currentValue = $this->snmpGetValue($moduleData['value']);
                    }
                }

                // Format current value with thousands and decimals.
                if (is_numeric($currentValue) === true) {
                    $decimals = (is_float($currentValue) === true) ? 2 : 0;
                    $currentValue = number_format(
                        $currentValue,
                        $decimals,
                        $config['decimal_separator'],
                        $config['thousand_separator']
                    );
                }

                // It unit of measure have data, attach to current value.
                if (empty($moduleData['module_unit']) === false) {
                    $currentValue .= ' '.$moduleData['module_unit'];
                }

                // Stablish the data for show.
                $interfaceModulesUpdated[] = [
                    'component_id'   => $component_id_number++,
                    'execution_type' => $moduleData['execution_type'],
                    'name'           => $moduleData['module_name'],
                    'type'           => $moduleData['module_type'],
                    'description'    => $moduleData['module_description'],
                    'min_warning'    => $moduleData['module_thresholds']['min_warning'],
                    'max_warning'    => $moduleData['module_thresholds']['max_warning'],
                    'inv_warning'    => $moduleData['module_thresholds']['inv_warning'],
                    'min_critical'   => $moduleData['module_thresholds']['min_critical'],
                    'max_critical'   => $moduleData['module_thresholds']['max_critical'],
                    'inv_critical'   => $moduleData['module_thresholds']['inv_critical'],
                    'perc_warning'   => $moduleData['module_thresholds']['perc_warning'],
                    'perc_critical'  => $moduleData['module_thresholds']['perc_critical'],
                    'module_enabled' => $moduleData['module_enabled'],
                    'current_value'  => $currentValue,
                    'name_oid'       => $moduleData['value'],
                    'value'          => $moduleData['value'],
                    'id_plugin'      => $moduleData['id_plugin'],
                    'macros'         => $moduleData['macros'],
                    'id_modulo'      => $moduleData['id_modulo'],
                    'unit'           => ($moduleData['unit'] ?? $moduleData['module_unit']),
                ];
            }

            $interfaceTables[$interface['name']]['data'] = $interfaceModulesUpdated;
        }

        html_print_div(
            [
                'class'   => 'wizard wizard-result',
                'style'   => 'margin-top: 20px;',
                'content' => $this->toggleTableModules(
                    $interfaceTables,
                    true,
                    true
                ),
            ]
        );

        // Add Create Modules form.
        $this->createModulesForm();
    }


    /**
     * Perform WMI Module Wizard and show a table with results.
     *
     * @return void
     */
    private function resultsWMIExplorerWizard()
    {
        $moduleBlocks = $this->moduleBlocks;

        $blockTables = [];
        foreach ($moduleBlocks as $k => $module) {
            // Construction of the WMI query.
            $execCommand = $this->wmiQuery($module, 'scan');

            // Execution of the WMI Query.
            $outputCommand = $this->wmiExecution(io_safe_output($execCommand));

            // Unpack the extra fields
            // and include with key field in a field set.
            $macros = json_decode($module['macros'], true);
            $fieldSet = ['0' => $module['query_key_field']];
            foreach ($macros as $fieldKey => $fieldMacro) {
                if (preg_match('/extra_field_/', $fieldKey) !== 0) {
                    $tmpKey = explode('_', $fieldKey);
                    $macros['macros']['_field_wmi_'.$tmpKey[2].'_'] = $fieldMacro;
                    $fieldSet[(string) $tmpKey[2]] = $fieldMacro;
                }
            }

            // Value operation.
            $valueOperation = io_safe_output($macros['value_operation']);
            // Unpack the query filters.
            $queryFilters = json_decode($module['query_filters'], true);
            // Name of query filter field.
            $fieldValueName = (empty($fieldSet[$queryFilters['field']]) === false) ? $fieldSet[$queryFilters['field']] : '1';

            // Evaluate type of scan and execution.
            if ($module['scan_type'] == SCAN_TYPE_FIXED) {
                // Common actions for FIXED scan type.
                $columnsList = [];
                $rowList = [];

                foreach ($outputCommand as $rowLine => $rowContent) {
                    if (($rowLine == 0)
                        && (preg_match(
                            '/CLASS: '.$module['query_class'].'/',
                            $rowContent
                        ) === 0)
                    ) {
                        // Erase this module because give us an error.
                        unset($moduleBlocks[$k]);
                        // Do not continue with this module.
                        continue 2;
                        // The second row has the columns list.
                    } else if ($rowLine == 1) {
                        $columnsList = explode('|', $rowContent);
                        $columnFieldIndex = array_search(
                            $fieldValueName,
                            $columnsList,
                            true
                        );
                        // The rest of the lines have results.
                    } else if ($rowLine == 2) {
                        $rowList = explode('|', $rowContent);
                    }
                }

                // If name of the module have a macro.
                $moduleBlocks[$k]['name'] = $this->macroFilter(
                    io_safe_output($module['name']),
                    $columnsList,
                    $rowList
                );
                // Description can have macros too.
                $moduleBlocks[$k]['description'] = $this->macroFilter(
                    io_safe_output($module['description']),
                    $columnsList,
                    $rowList
                );
                // Query filters can have macros too.
                $moduleBlocks[$k]['query_filters'] = $this->macroFilter(
                    $module['query_filters'],
                    $columnsList,
                    $rowList
                );

                foreach ($columnsList as $columnKey => $columnValue) {
                    $macros['macros']['_'.trim($columnValue).'_'] = $rowList[trim($columnKey)];
                }

                $moduleBlocks[$k]['macros'] = json_encode($macros);

                if ($module['execution_type'] == EXECUTION_TYPE_NETWORK) {
                    // Construction of the WMI query.
                    // $execCommand = $this->wmiQuery($module, 'execution');
                    // Execution of the WMI Query.
                    // $outputCommand = $this->wmiExecution($execCommand);
                    // Setting of value of this module (field query filter).
                    if ($queryFilters['field'] != '') {
                        if (empty($queryFilters['key_string']) === false) {
                            // Evaluate if the value is equal than key string.
                            $moduleBlocks[$k]['current_value'] = (io_safe_output($queryFilters['key_string']) == io_safe_output($rowList[$columnFieldIndex])) ? 1 : 0;
                        } else {
                            // Set the value getted.
                            $moduleBlocks[$k]['current_value'] = $rowList[$columnFieldIndex];
                        }

                        $moduleBlocks[$k]['current_value'] = $this->replacementUnit(
                            $moduleBlocks[$k]['current_value'],
                            $module['unit'],
                            $module['type']
                        );
                    } else {
                        $moduleBlocks[$k]['current_value'] = 0;
                    }
                } else if ($module['execution_type'] == EXECUTION_TYPE_PLUGIN) {
                    // Combine both data list.
                    $dataCombined = array_combine($columnsList, $rowList);
                    // Change the macros for values.
                    foreach ($dataCombined as $macroKey => $macroValue) {
                        $macroKey = trim($macroKey);
                        if (preg_match('/_'.$macroKey.'_/', $valueOperation) !== 0) {
                            $valueOperation = preg_replace(
                                '/_'.$macroKey.'_/',
                                $macroValue,
                                $valueOperation
                            );
                        }
                    }

                    // Evaluate the operation and set the current value.
                    $moduleBlocks[$k]['current_value'] = $this->evalOperation(
                        $valueOperation,
                        $module['unit'],
                        $module['type']
                    );
                }
            } else if ($module['scan_type'] == SCAN_TYPE_DYNAMIC) {
                $columnsList = [];
                $columnFieldIndex = '0';

                foreach ($outputCommand as $rowLine => $rowContent) {
                    // The first result must be the class name.
                    if (($rowLine == 0) && (preg_match(
                        '/CLASS: '.$module['query_class'].'/',
                        $rowContent
                    ) === 0)
                    ) {
                        // Erase this module because give us an error.
                        unset($moduleBlocks[$k]);
                        // Do not continue with this module.
                        continue 2;
                        // The second row has the columns list.
                    } else if ($rowLine == 1) {
                        $columnsList = explode('|', $rowContent);
                        $columnFieldIndex = array_search(
                            $fieldValueName,
                            $columnsList,
                            true
                        );
                        // The rest of the lines have results.
                    } else if ($rowLine > 1) {
                        $newModule = $module;
                        $rowList = explode('|', $rowContent);
                        // If name of the module have a macro.
                        $newModule['name'] = $this->macroFilter(
                            io_safe_output($module['name']),
                            $columnsList,
                            $rowList
                        );
                        // Description can have macros too.
                        $newModule['description'] = $this->macroFilter(
                            io_safe_output($module['description']),
                            $columnsList,
                            $rowList
                        );

                        $newModule['query_filters'] = $this->macroFilter(
                            io_safe_output($module['query_filters']),
                            $columnsList,
                            $rowList
                        );

                        $keyString = $this->macroFilter(
                            io_safe_output($queryFilters['key_string']),
                            $columnsList,
                            $rowList
                        );

                        foreach ($columnsList as $columnKey => $columnValue) {
                            $macros['macros']['_'.$columnValue.'_'] = $rowList[$columnKey];
                        }

                        $newModule['macros'] = json_encode($macros);

                        // Setting of value of this module (field query filter).
                        if ($module['execution_type'] == EXECUTION_TYPE_NETWORK) {
                            if ($queryFilters['field'] != '') {
                                // If key string filter filled.
                                if (empty($keyString) === false) {
                                    // Evaluate if the value
                                    // is equal than key string.
                                    $newModule['current_value'] = ($keyString == io_safe_output($rowList[$columnFieldIndex])) ? 1 : 0;
                                } else {
                                    // Set the value getted.
                                    $newModule['current_value'] = $rowList[$columnFieldIndex];
                                }

                                $newModule['current_value'] = $this->replacementUnit(
                                    $newModule['current_value'],
                                    $module['unit'],
                                    $module['type']
                                );
                            } else {
                                $newModule['current_value'] = 0;
                            }
                        } else if ($module['execution_type'] == EXECUTION_TYPE_PLUGIN) {
                            // Combine both data list.
                            $dataCombined = array_combine(
                                $columnsList,
                                $rowList
                            );
                            // Change the macros for values.
                            foreach ($dataCombined as $macroKey => $macroValue) {
                                $macroKey = trim($macroKey);
                                if (preg_match('/_'.$macroKey.'_/', $valueOperation) !== 0) {
                                    $valueOperation = preg_replace(
                                        '/_'.$macroKey.'_/',
                                        $macroValue,
                                        $valueOperation
                                    );
                                }
                            }

                            // Evaluate the operation and set the result.
                            $newModule['current_value'] = $this->evalOperation(
                                $valueOperation,
                                $module['unit'],
                                $module['type']
                            );
                        }

                        // Adding new module to the block.
                        $moduleBlocks[] = $newModule;
                    }
                }

                // Clear the original module.
                unset($moduleBlocks[$k]);
            }
        }

        // If we not retrieve information (P.E. connection refused).
        if (empty($moduleBlocks) === true) {
            $this->message['type'][]    = 'warning';
            $this->message['message'][] = __(
                'No information could be retrieved.'
            );
            $this->showMessage();
        } else {
            // Create the final table with all of data received.
            foreach ($moduleBlocks as $module) {
                // Prepare the blocks. If its new, create a new index.
                if (key_exists($module['group'], $blockTables) === false) {
                    $blockTables[$module['group']] = [
                        'name' => $module['group_name'],
                        'data' => [],
                    ];
                }

                // Add the module info in the block.
                $blockTables[$module['group']]['data'][] = $module;
                if (isset($blockTables[$module['group']]['activeModules']) === false
                    && (int) $module['module_enabled'] === 1
                ) {
                    $blockTables[$module['group']]['activeModules'] = 2;
                } else if (isset($blockTables[$module['group']]['activeModules']) === true
                    && (int) $module['module_enabled'] === 0
                ) {
                    $blockTables[$module['group']]['activeModules'] = 1;
                }
            }

            // General Default monitoring.
            html_print_div(
                [
                    'class'   => 'wizard wizard-result',
                    'style'   => 'margin-top: 20px;',
                    'content' => $this->toggleTableModules($blockTables),
                ]
            );
            // Add Create Modules form.
            $this->createModulesForm();
        }
    }


    /**
     * Perform SNMP Module Wizard and show a table with results.
     *
     * @return void
     */
    private function resultsSNMPExplorerWizard()
    {
        $moduleBlocks = $this->moduleBlocks;

        $blockTables = [];
        // Lets work with the modules.
        foreach ($moduleBlocks as $k => $module) {
            if ($this->serverType === SERVER_TYPE_ENTERPRISE_SATELLITE) {
                if ($module['type'] == MODULE_TYPE_REMOTE_SNMP) {
                    $module['type'] = MODULE_TYPE_GENERIC_DATA;
                    $moduleBlocks[$k]['type'] = $module['type'];
                } else if ($module['type'] == MODULE_TYPE_REMOTE_SNMP_PROC) {
                    $module['type'] = MODULE_TYPE_GENERIC_PROC;
                    $moduleBlocks[$k]['type'] = $module['type'];
                } else if ($module['type'] == MODULE_TYPE_REMOTE_SNMP_INC) {
                    $module['type'] = MODULE_TYPE_GENERIC_DATA_INC;
                    $moduleBlocks[$k]['type'] = $module['type'];
                } else if ($module['type'] == MODULE_TYPE_REMOTE_SNMP_STRING) {
                    // MODULE_TYPE_REMOTE_SNMP_STRING.
                    $module['type'] = MODULE_TYPE_GENERIC_DATA_STRING;
                    $moduleBlocks[$k]['type'] = $module['type'];
                }
            }

            if ($module['scan_type'] == SCAN_TYPE_FIXED) {
                // Common for FIXED Scan types.
                // If _nameOID_ macro exists, stablish the name getted.
                if (empty($module['name_oid']) === false) {
                    $nameValue = $this->snmpGetValue($module['name_oid']);
                    $moduleBlocks[$k]['name'] = str_replace(
                        '_nameOID_',
                        $nameValue,
                        $module['name']
                    );
                }

                if ($module['execution_type'] == EXECUTION_TYPE_NETWORK) {
                    // Set the current value to this module.
                    if (empty($module['value']) === true) {
                        $module['value'] = 0;
                    }

                    $value = $this->snmpGetValue($module['value']);
                    // If the value is missing, we must not show this module.
                    if (empty($value) === true) {
                        unset($moduleBlocks[$k]);
                    } else {
                        $moduleBlocks[$k]['current_value'] = $this->replacementUnit(
                            $value,
                            $module['unit'],
                            $module['type']
                        );
                    }

                    $moduleBlocks[$k]['macros'] = '';
                } else {
                    // Three steps for FIXED PLUGIN wizard modules.
                    // Break up macros.
                    $macros = json_decode($module['macros'], true);
                    $operation = io_safe_output($macros['value_operation']);
                    // Loop through the macros for get the
                    // OIDs and get his values.
                    foreach ($macros as $key => $oid) {
                        if (preg_match('/extra_field_/', $key) !== 0) {
                            $value = (float) $this->snmpGetValue($oid);

                            // If the value not exists,
                            // we must not create a module.
                            if (empty($value) === true) {
                                unset($moduleBlocks[$k]);
                                continue 2;
                            } else {
                                $tmp = explode('_', $key);
                                $newKey = str_replace(
                                    $key,
                                    '_oid_'.$tmp[2].'_',
                                    $key
                                );
                                $macros['macros']['_oid_'.$tmp[2].'_'] = $oid;
                                $operation = preg_replace(
                                    '/'.$newKey.'/',
                                    $value,
                                    $operation
                                );
                            }
                        }
                    }

                    $moduleBlocks[$k]['macros'] = json_encode($macros);

                    // Get the result of the operation and set it.
                    $moduleBlocks[$k]['current_value'] = $this->evalOperation(
                        $operation,
                        $module['unit'],
                        $module['type']
                    );
                }
            } else {
                if ($module['execution_type'] == EXECUTION_TYPE_NETWORK) {
                    // Get the values of snmpwalk.
                    $snmpwalkNames = $this->snmpWalkValues($module['name_oid']);
                    $snmpWalkValues = $this->snmpWalkValues($module['value']);

                    $snmpwalkCombined = [];
                    foreach ($snmpwalkNames as $index => $name) {
                        if (isset($name) !== true
                            || isset($snmpWalkValues[$index]) !== true
                        ) {
                            continue;
                        }

                        $snmpwalkCombined[$index] = [
                            'name'  => $name,
                            'value' => $snmpWalkValues[$index],
                        ];
                    }

                    foreach ($snmpwalkCombined as $index => $r) {
                        $name = $r['name'];
                        $value = $r['value'];

                        $newModule = $module;
                        // Setting the new values.
                        $newModule['name'] = str_replace(
                            '_nameOID_',
                            io_safe_input($name),
                            $module['name']
                        );

                        // Save complete OID reference + index.
                        $newModule['value'] = $module['value'].$index;
                        $newModule['current_value'] = $this->replacementUnit(
                            $value,
                            $module['unit'],
                            $module['type']
                        );
                        $newModule['macros'] = '';

                        // Add this new module to the module list.
                        $moduleBlocks[] = $newModule;
                    }

                    // Erase the main module.
                    unset($moduleBlocks[$k]);
                } else {
                    // Break up macros.
                    $macros = (array) json_decode($module['macros']);
                    $operation = io_safe_output($macros['value_operation']);
                    $oids = [];
                    foreach ($macros as $key => $oid) {
                        if (preg_match('/extra_field_/', $key) !== 0) {
                            $tmp = explode('_', $key);
                            $newKey = str_replace(
                                $key,
                                '_oid_'.$tmp[2].'_',
                                $key
                            );
                            $oids[$newKey] = $oid;
                        }
                    }

                    $snmpwalkNamesTmp = [];
                    // Is needed the index and the values of snmpwalk.
                    $snmpwalkNamesTmp = $this->snmpWalkValues(
                        $module['name_oid'],
                        true
                    );

                    $snmpwalkNames = [];
                    foreach ($snmpwalkNamesTmp as $value) {
                        // Generate a new module based
                        // in the first for every name found.
                        $newModule = $module;
                        // Split the values got to obtain the name.
                        $tmpFirst = explode('.', $value);
                        $tmpSecond = explode(' ', $tmpFirst[(count($tmpFirst) - 1)]);
                        // Position 0 is the index, Position 3 is the MIB name.
                        $snmpwalkNames[$tmpSecond[0]] = $tmpSecond[3];
                        // Perform the operations for get the values.
                        $thisOperation = $operation;
                        foreach ($oids as $oidName => $oid) {
                            $currentOid = $oid.'.'.$tmpSecond[0];
                            $macros['macros'][$oidName] = $currentOid;
                            $currentOidValue = $this->snmpGetValue($currentOid);
                            // If for any reason the value comes empty, add 1.
                            if ($currentOidValue == '') {
                                $currentOidValue = 1;
                            }

                            $thisOperation = preg_replace(
                                '/'.$oidName.'/',
                                $currentOidValue,
                                $thisOperation
                            );
                        }

                        $newModule['macros'] = json_encode($macros);

                        // Get the result of the operation and set it.
                        $newModule['current_value'] = $this->evalOperation(
                            $thisOperation,
                            $module['unit'],
                            $module['type']
                        );

                        // Add the name to this module.
                        $newModule['name'] = str_replace(
                            '_nameOID_',
                            io_safe_input($tmpSecond[3]),
                            $module['name']
                        );

                        $newModule['name_oid'] = str_replace('"', '', $tmpSecond[3]);

                        // Add this new module to the module list.
                        $moduleBlocks[] = $newModule;
                    }

                    // Erase the main module.
                    unset($moduleBlocks[$k]);
                }
            }
        }

        // Create the final table with all of data received.
        foreach ($moduleBlocks as $module) {
            if (is_array($module) === true
                && count($module) <= 1
                && empty($module['macros']) === true
            ) {
                // Invalid module.
                continue;
            }

            // Prepare the blocks. If its new, create a new index.
            if (key_exists($module['group'], $blockTables) === false) {
                $blockTables[$module['group']] = [
                    'name' => $module['group_name'],
                    'data' => [],
                ];
            }

            // Add the module info in the block.
            $blockTables[$module['group']]['data'][] = $module;
            if (isset($blockTables[$module['group']]['activeModules']) === false
                && (int) $module['module_enabled'] === 1
            ) {
                $blockTables[$module['group']]['activeModules'] = 2;
            } else if (isset($blockTables[$module['group']]['activeModules']) === true
                && (int) $module['module_enabled'] === 0
            ) {
                $blockTables[$module['group']]['activeModules'] = 1;
            }
        }

        if (empty($blockTables) === true) {
            $this->message['type'][]    = 'warning';
            $this->message['message'][] = __(
                'No information could be retrieved.'
            );
        } else {
            // General Default monitoring.
            html_print_div(
                [
                    'class'   => 'wizard wizard-result',
                    'style'   => 'margin-top: 20px;',
                    'content' => $this->toggleTableModules($blockTables),
                ]
            );

            // Add Create Modules form.
            $this->createModulesForm();
        }
    }


    /**
     * Get the data from the module blocks
     *
     * @return array Return an array with the module blocks needed.
     */
    private function getModuleBlocks()
    {
        // Definition of filters.
        $whereString = sprintf(
            'nc.id_modulo = %d AND nc.protocol = "%s"',
            MODULE_WIZARD,
            $this->protocol
        );
        // Special considerations for both protocols.
        if ($this->protocol === 'snmp') {
            if (empty($this->penName) === true) {
                return false;
            }

            $whereString .= sprintf(
                ' AND (
                    nc.manufacturer_id = "all" OR nc.manufacturer_id = "%s"
                )',
                $this->penName
            );
            $fields = 'nc.name_oid';
        } else if ($this->protocol === 'wmi') {
            $fields = 'nc.query_class, nc.query_key_field,';
            $fields .= 'nc.scan_filters, nc.query_filters';
        } else {
            $fields = '';
        }

        $sql = sprintf(
            'SELECT nc.id_nc AS component_id,
            nc.name,
            nc.type,
            nc.description,
            nc.id_group AS `group`,
            ncg.name AS `group_name`,
            nc.min_warning,
            nc.max_warning,
            nc.warning_inverse AS `inv_warning`,
            nc.min_critical,
            nc.max_critical,
            nc.critical_inverse AS `inv_critical`,
            nc.percentage_warning AS `perc_warning`,
            nc.percentage_critical AS `perc_critical`,
            nc.module_enabled,
            %s,
            nc.scan_type,
            nc.execution_type,
            nc.value,
            nc.macros,
            nc.unit
            FROM tnetwork_component AS nc
            LEFT JOIN tnetwork_component_group AS ncg
                ON nc.id_group = ncg.id_sg
            WHERE %s AND nc.enabled=1',
            $fields,
            $whereString
        );

        return db_get_all_rows_sql($sql);
    }


    /**
     * Returns associated PEN code of this device.
     *
     * @return integer|null PEN oid or null if not found.
     */
    private function getPEN()
    {
        $oid = '.1.3.6.1.2.1.1.2.0';
        $output = $this->snmpWalkValues($oid, false, true, true);

        static $pen;

        if (isset($pen) === true) {
            return $pen;
        }

        if (is_array($output) === true
            && isset($output[$oid]) === true
        ) {
            // Output should be an array with only 1 element.
            $pen = (int) explode('.', $output[$oid])[7];
        }

        if ($pen === 0) {
            return null;
        }

        return $pen;
    }


    /**
     * Returns the index oid matching selected expected value.
     *
     * @param string $oidTree       Tree to search in.
     * @param string $expectedValue Expected value.
     *
     * @return string|false Index where expected value is stored or false if not
     *                      found.
     */
    private function snmpGetValueInverse($oidTree, $expectedValue)
    {
        $oidTree = $this->snmpWalkValues($oidTree);

        if (is_array($oidTree) === false) {
            return false;
        }

        return array_search($expectedValue, $oidTree);
    }


    /**
     * Perform a snmpget for get a value from provided oid.
     *
     * @param string  $oid         Oid for get the value.
     * @param boolean $full_output Array with full output..
     *
     * @return mixed String when response, null if error.
     */
    private function snmpGetValue(string $oid, ?bool $full_output=false)
    {
        if ($oid[0] !== '.') {
            $oid = '.'.$oid;
        }

        $output = $this->snmpWalkValues($oid, false, true, true);

        if (is_array($output) === true) {
            foreach ($output as $k => $v) {
                if ($k[0] !== '.') {
                    $k = '.'.$k;
                }

                if ($k == $oid) {
                    if ($full_output === true) {
                        return $k.' = '.$v;
                    }

                    $value = explode(': ', $v, 2);
                    return $value[1];
                }
            }
        }

        return false;
    }


    /**
     * Perform a snmpwalk for get the values from the provided oid.
     *
     * @param string  $oid         Oid for get the values.
     * @param boolean $full_output Array with full output.
     * @param boolean $pure        Return results as received by get_snmwalk.
     * @param boolean $get         If get operation, adjust key.
     *
     * @return array
     */
    private function snmpWalkValues(
        string $oid,
        bool $full_output=false,
        bool $pure=false,
        bool $get=false
    ) {
        static $__cached_walks;

        if ($__cached_walks === null) {
            $__cached_walks = [];
        }

        if ($oid[0] !== '.') {
            $oid = '.'.$oid;
        }

        if ($get === true) {
            // Request from snmpget. Cache is in tree.
            $tree_oid = strrev($oid);
            $tree_oid = strrev(
                substr(
                    $tree_oid,
                    (strpos($tree_oid, '.') + 1),
                    strlen($tree_oid)
                )
            );

            $key = $tree_oid.'-'.((int) $full_output).'-'.((int) $pure);
            // Request entire sub-tree.
            $oid = $tree_oid;
        } else {
            $key = $oid.'-'.((int) $full_output).'-'.((int) $pure);
        }

        if (isset($__cached_walks[$key]) === true) {
            return $__cached_walks[$key];
        }

        $output = [];
        $temporal = get_snmpwalk(
            $this->targetIp,
            $this->version,
            $this->community,
            $this->authUserV3,
            $this->securityLevelV3,
            $this->authMethodV3,
            $this->authPassV3,
            $this->privacyMethodV3,
            $this->privacyPassV3,
            0,
            $oid,
            $this->targetPort,
            $this->server,
            $this->extraArguments,
            (($full_output === false) ? '-On' : '-Oa'),
            ''
        );

        if ($pure === true) {
            $__cached_walks[$key] = $temporal;
            return $temporal;
        }

        if (empty($temporal) === false) {
            foreach ($temporal as $key => $oid_unit) {
                if ($full_output === true) {
                    $output[] = $key.' = '.$oid_unit;
                } else {
                    $index = [];
                    $index[] = preg_replace('/^'.$oid.'/', '', $key);
                    if (empty($index) === true) {
                        preg_match('/\.\d+$/', $key, $index);
                    }

                    $tmp = explode(': ', $oid_unit);
                    $output[$index[0]] = str_replace('"', '', ($tmp[1] ?? ''));
                }
            }
        }

        $__cached_walks[$key] = $output;
        return $output;
    }


    /**
     * Add a button for Create the modules selected.
     *
     * @return void
     */
    private function createModulesForm()
    {
        // Create modules form.
        $form = [
            'action' => $this->sectionUrl,
            'id'     => 'form-create-modules',
            'method' => 'POST',
            'class'  => 'modal',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [];
        // Submit button.
        $inputs[] = [
            'arguments' => [
                'label'      => __('Create modules'),
                'name'       => 'create-modules-action',
                'type'       => 'button',
                'attributes' => [ 'icon' => 'next' ],
                'script'     => 'processListModules();',
                'return'     => true,
            ],
        ];

        $inputs = array_merge($inputs, $this->getCommonDataInputs());

        // Print the the submit button for create modules.
        $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
                true
            ]
        );
    }


    /**
     * Inputs.
     *
     * @return array Inputs for common data.
     */
    private function getCommonDataInputs(): array
    {
        $inputs[] = [
            'id'        => 'create-modules-action',
            'arguments' => [
                'name'   => 'create-modules-action',
                'type'   => 'hidden',
                'value'  => 1,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'protocol',
            'arguments' => [
                'name'   => 'protocol',
                'id'     => 'protocol_data',
                'type'   => 'hidden',
                'value'  => $this->protocol,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'targetIp',
            'arguments' => [
                'name'   => 'targetIp',
                'type'   => 'hidden',
                'value'  => $this->targetIp,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'targetPort',
            'arguments' => [
                'name'   => 'targetPort',
                'type'   => 'hidden',
                'value'  => $this->targetPort,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'community',
            'arguments' => [
                'name'   => 'community',
                'type'   => 'hidden',
                'value'  => $this->community,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'version',
            'arguments' => [
                'name'   => 'version',
                'type'   => 'hidden',
                'value'  => $this->version,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'server',
            'arguments' => [
                'name'   => 'server',
                'type'   => 'hidden',
                'value'  => $this->server,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'authUserV3',
            'arguments' => [
                'name'   => 'authUserV3',
                'type'   => 'hidden',
                'value'  => $this->authUserV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'authPassV3',
            'arguments' => [
                'name'   => 'authPassV3',
                'type'   => 'hidden',
                'value'  => $this->authPassV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'authMethodV3',
            'arguments' => [
                'name'   => 'authMethodV3',
                'type'   => 'hidden',
                'value'  => $this->authMethodV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'securityLevelV3',
            'arguments' => [
                'name'   => 'securityLevelV3',
                'type'   => 'hidden',
                'value'  => $this->securityLevelV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'privacyMethodV3',
            'arguments' => [
                'name'   => 'privacyMethodV3',
                'type'   => 'hidden',
                'value'  => $this->privacyMethodV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'privacyPassV3',
            'arguments' => [
                'name'   => 'privacyPassV3',
                'type'   => 'hidden',
                'value'  => $this->privacyPassV3,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'namespaceWMI',
            'arguments' => [
                'name'   => 'namespaceWMI',
                'type'   => 'hidden',
                'value'  => $this->namespaceWMI,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'usernameWMI',
            'arguments' => [
                'name'   => 'usernameWMI',
                'type'   => 'hidden',
                'value'  => $this->usernameWMI,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'passwordWMI',
            'arguments' => [
                'name'   => 'passwordWMI',
                'type'   => 'hidden',
                'value'  => $this->passwordWMI,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'idAgent',
            'arguments' => [
                'name'   => 'idAgent',
                'type'   => 'hidden',
                'value'  => $this->idAgent,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'idPolicy',
            'arguments' => [
                'name'   => 'id',
                'type'   => 'hidden',
                'value'  => $this->idPolicy,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'wizardSection',
            'arguments' => [
                'name'   => 'wizard_section',
                'type'   => 'hidden',
                'value'  => $this->wizardSection,
                'return' => true,
            ],
        ];

        return $inputs;
    }


    /**
     * Retrieve operstatus for given interface.
     *
     * @param string $interface_name Interface name.
     *
     * @return integer OperStatus.
     */
    private function getOperStatus(string $interface_name)
    {
        if (is_array($this->interfacesData[$interface_name]) === true) {
            return (int) $this->interfacesData[$interface_name]['operstatus'];
        }

        return 0;
    }


    /**
     * Retrieve adminstatus for given interface.
     *
     * @param string $interface_name Interface name.
     *
     * @return integer AdminStatus.
     */
    private function getAdminStatus(string $interface_name)
    {
        if (is_array($this->interfacesData[$interface_name]) === true) {
            return (int) $this->interfacesData[$interface_name]['adminstatus'];
        }

        return 0;
    }


    /**
     * Create the tables with toggle interface for show the modules availables.
     *
     * @param mixed   $blocks           Info getted.
     * @param boolean $showCurrentValue If true, show the
     * column of current values.
     * @param boolean $isInterface      If true, the form is
     * displayed for interface purposes.
     * @param boolean $isPrincipal      If true, the form is
     * displayed for first interface module list.
     *
     * @return mixed String with the tables formed.
     */
    private function toggleTableModules(
        $blocks,
        bool $showCurrentValue=true,
        bool $isInterface=false,
        bool $isPrincipal=false
    ) {
        $output = '';
        foreach ($blocks as $idBlock => $block) {
            $md5IdBlock = hash('md5', $idBlock);

            // Data with all components.
            $blockData = $block['data'];

            // Active modules.
            $activeModules = 0;
            if (isset($block['activeModules']) === true) {
                $activeModules = $block['activeModules'];
            }

            // Creation of list of all components.
            $blockComponentList = '';
            foreach ($blockData as $component) {
                $blockComponentList .= $component['component_id'].',';
            }

            $is_up = false;
            if ($this->getOperStatus($idBlock) === 1
                && $this->getAdminSTatus($idBlock) === 1
            ) {
                $is_up = true;
            }

            $blockComponentList = chop($blockComponentList, ',');
            // Title of Block.
            if ($isInterface === true) {
                if ($isPrincipal === true) {
                    $blockTitle = '<b>';
                    $blockTitle .= __(
                        'Add general monitoring for all selected interfaces'
                    );
                    $blockTitle .= '</b>';
                } else {
                    $blockTitle = html_print_input(
                        [
                            'type'       => 'switch',
                            'name'       => 'interfaz_select_'.$idBlock,
                            'value'      => $is_up,
                            'disabled'   => false,
                            'attributes' => 'form="form-create-modules" class="interfaz_select" ',
                            'return'     => true,
                            'id'         => $md5IdBlock,
                        ]
                    );
                    $blockTitle .= '<b>'.$block['name'];
                    $blockTitle .= '&nbsp;&nbsp;';
                    $blockTitle .= html_print_image(
                        'images/info@svg.svg',
                        true,
                        [
                            'title' => __('Modules selected'),
                            'alt'   => __('Modules selected'),
                            'id'    => 'image-info-modules-'.$idBlock,
                            'class' => 'hidden main_menu_icon',
                        ]
                    );
                    $blockTitle .= '</b>';
                }
            } else {
                $blockTitle = '<b>'.$block['name'];
                $classImg = 'main_menu_icon ';
                if ($activeModules === 0) {
                    $classImg .= 'hidden';
                }

                $blockTitle .= '&nbsp;&nbsp;';
                $blockTitle .= html_print_image(
                    'images/info@svg.svg',
                    true,
                    [
                        'title' => __('Modules selected'),
                        'alt'   => __('Modules selected'),
                        'id'    => 'image-info-modules-'.$idBlock,
                        'class' => $classImg,
                    ]
                );
                $blockTitle .= '</b>';
            }

            $table = new StdClass();
            $table->styleTable = 'margin: 2em auto 0;border: 1px solid #ddd;';
            $table->rowid = [];
            $table->data = [];

            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->width = '100%';
            $table->class = 'info_table';
            // Subheaders for Warning and Critical columns.
            $subheaders = '<span class=\'wizard-colum-levels font_w300 margin-left-1\'>Min.</span>';
            $subheaders .= '<span class=\'wizard-colum-levels font_w300 margin-left-1\'>Max.</span>';
            $subheaders .= '<span class=\'wizard-colum-levels font_w300 margin-left-1\'>Inv.</span>';
            $subheaders .= '<span class=\'wizard-colum-levels font_w300 margin-left-05\'>%.</span>';

            // Warning header.
            $warning_header = html_print_div(
                [
                    'style'   => 'font-weight:700;text-align:center;',
                    'content' => html_print_div(
                        [
                            'style'   => 'width: 100%; text-align:center;',
                            'content' => __('Warning'),
                        ],
                        true
                    ),
                ],
                true
            );
            // Critical header.
            $critical_header = html_print_div(
                [
                    'style'   => 'font-weight:700;text-align:center;',
                    'content' => html_print_div(
                        [
                            'style'   => 'width: 100%; text-align:center;',
                            'content' => __('Critical'),
                        ],
                        true
                    ),
                ],
                true
            );
            // Header section.
            $table->head = [];
            $table->head[0] = html_print_div(
                [
                    'style'   => 'font-weight:700;',
                    'content' => __('Module Name'),
                ],
                true
            );
            $table->head[1] = html_print_div(
                [
                    'style'   => 'font-weight:700;',
                    'content' => __('Type'),
                ],
                true
            );
            if ($isPrincipal === true) {
                $headerInfo = __('Module info');
            } else {
                $headerInfo = __('Description');
            }

            $table->head[2] = html_print_div(
                [
                    'style'   => 'font-weight:700;',
                    'content' => $headerInfo,
                ],
                true
            );
            $table->head[3] = $warning_header.$subheaders;
            $table->head[4] = $critical_header.$subheaders;

            // Size.
            $table->size = [];
            $table->size[0] = '15%';
            $table->size[1] = '3%';
            $table->size[3] = '140px';
            $table->size[4] = '140px';
            $table->size[5] = '3%';

            $table->align = [];
            $table->align[1] = 'center';

            // If is needed show current value, we must correct the table.
            if ($showCurrentValue === true) {
                // Correct headers.
                $table->head[5] = html_print_div(
                    [
                        'style'   => 'font-weight:700;text-align:center;',
                        'content' => __('Current value'),
                    ],
                    true
                );

                $class = '';
                if ($activeModules === 1) {
                    $class = 'alpha50 pdd_0px';
                }

                $table->head[6] = html_print_checkbox_switch_extended(
                    'sel_block_'.$idBlock,
                    1,
                    $activeModules,
                    false,
                    'switchBlockControl(event)',
                    '',
                    true,
                    '',
                    $class
                );

                // Correct size.
                $table->size[5] = '5%';
                $table->size[6] = '3%';
            } else {
                // Correct size.
                $table->size[5] = '1%';
                $table->size[6] = '3%';
                $table->head[5] = '';
                $table->head[6] = html_print_checkbox_switch_extended(
                    'sel_block_'.$idBlock,
                    1,
                    true,
                    false,
                    'switchBlockControl(event)',
                    '',
                    true,
                    '',
                    'alpha50 pdd_0px'
                );
            }

            $table->data = [];

            foreach ($blockData as $kId => $module) {
                $uniqueId = $idBlock.'_'.$module['component_id'].'-'.$kId;

                // Module Name column.
                if ($isPrincipal === true) {
                    $data[0] = $module['name'];
                } else {
                    $data[0] = html_print_input_text(
                        'module-name-set-'.$uniqueId,
                        $module['name'],
                        '',
                        25,
                        255,
                        true,
                        false,
                        false,
                        '',
                        $md5IdBlock.' w100p',
                        '',
                        '',
                        false,
                        '',
                        'form-create-modules'
                    );
                }

                // Module Type column.
                $data[1] = ui_print_moduletype_icon($module['type'], true);
                // Module info column.
                if ($isPrincipal === true) {
                    $data[2] = io_safe_output($module['description']);
                } else {
                    $data[2] = html_print_textarea(
                        'module-description-set-'.$uniqueId,
                        1,
                        20,
                        $module['description'],
                        'form=\'form-create-modules\'',
                        true,
                        'w100p'
                    );
                }

                // Warning column.
                $data_warning = '';
                $data_warning = html_print_div(
                    [
                        'class'   => 'wizard-column-levels',
                        'content' => html_print_input_text(
                            'module-warning-min-'.$uniqueId,
                            $module['min_warning'],
                            '',
                            3,
                            4,
                            true,
                            false,
                            false,
                            '',
                            $md5IdBlock,
                            '',
                            '',
                            false,
                            '',
                            'form-create-modules'
                        ).' ',
                    ],
                    true
                );
                $data_warning .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels',
                        'content' => html_print_input_text(
                            'module-warning-max-'.$uniqueId,
                            $module['max_warning'],
                            '',
                            3,
                            4,
                            true,
                            false,
                            false,
                            '',
                            $md5IdBlock,
                            '',
                            '',
                            false,
                            '',
                            'form-create-modules'
                        ),
                    ],
                    true
                );
                $data_warning .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels-check mrgn_top_10px',
                        'content' => html_print_checkbox(
                            'module-warning-inv-'.$uniqueId,
                            $module['inv_warning'],
                            $module['inv_warning'],
                            true,
                            false,
                            'change_control(this, \''.$uniqueId.'\')',
                            false,
                            'form="form-create-modules"'
                        ),
                    ],
                    true
                );
                $data_warning .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels-check mrgn_top_10px',
                        'content' => html_print_checkbox(
                            'module-warning-perc-'.$uniqueId,
                            $module['perc_warning'],
                            $module['perc_warning'],
                            true,
                            false,
                            'change_control(this, \''.$uniqueId.'\')',
                            false,
                            'form="form-create-modules"'
                        ),
                    ],
                    true
                );
                $data[3] = $data_warning;
                // Critical column.
                $data[4] = '';
                $data[4] .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels',
                        'content' => html_print_input_text(
                            'module-critical-min-'.$uniqueId,
                            $module['min_critical'],
                            '',
                            3,
                            4,
                            true,
                            false,
                            false,
                            '',
                            $md5IdBlock,
                            '',
                            '',
                            false,
                            '',
                            'form-create-modules'
                        ).' ',
                    ],
                    true
                );
                $data[4] .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels',
                        'content' => html_print_input_text(
                            'module-critical-max-'.$uniqueId,
                            $module['max_critical'],
                            '',
                            3,
                            4,
                            true,
                            false,
                            false,
                            '',
                            $md5IdBlock,
                            '',
                            '',
                            false,
                            '',
                            'form-create-modules'
                        ),
                    ],
                    true
                );

                $data[4] .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels-check mrgn_top_10px',
                        'content' => html_print_checkbox(
                            'module-critical-inv-'.$uniqueId,
                            $module['inv_critical'],
                            $module['inv_critical'],
                            true,
                            false,
                            'change_control(this, \''.$uniqueId.'\')',
                            false,
                            'form="form-create-modules"'
                        ),
                    ],
                    true
                );

                $data[4] .= html_print_div(
                    [
                        'class'   => 'wizard-column-levels-check mrgn_top_10px',
                        'content' => html_print_checkbox(
                            'module-critical-perc-'.$uniqueId,
                            $module['perc_critical'],
                            $module['perc_critical'],
                            true,
                            false,
                            'change_control(this,\''.$uniqueId.'\')',
                            false,
                            'form="form-create-modules"'
                        ),
                    ],
                    true
                );

                if (is_string($module['module_enabled']) === true) {
                    if ($module['module_enabled'] === false || $module['module_enabled'] === '0') {
                        $module['module_enabled'] = false;
                    } else {
                        $module['module_enabled'] = true;
                    }
                }

                if ($isPrincipal === true) {
                    // Activation column.
                    $data[5] = '';
                    $data[6] = html_print_checkbox_switch_extended(
                        'sel_module_'.$uniqueId,
                        $module['module_enabled'],
                        $module['module_enabled'],
                        false,
                        'switchBlockControl(event)',
                        '',
                        true,
                        '',
                        'pdd_0px mrgn_top-8px'
                    );
                } else {
                    // WIP. Current value of this module.
                    if (isset($module['current_value']) === false) {
                        $module['current_value'] = 'NO DATA';
                    }

                    $data[5] = ui_print_truncate_text(
                        io_safe_output($module['current_value']),
                        20,
                        false,
                        true,
                        true,
                        '&hellip;',
                        false
                    );

                    // Activation column.
                    $data[6] = html_print_checkbox_switch_extended(
                        'sel_module_'.$uniqueId,
                        $module['module_enabled'],
                        $module['module_enabled'],
                        false,
                        'switchBlockControl(event)',
                        'form="form-create-modules"',
                        true,
                        '',
                        'pdd_0px'
                    );
                }

                // Input info for activate (active: 1 true 0 false).
                $data[6] .= html_print_input_hidden(
                    'module-active-'.$uniqueId,
                    $module['module_enabled'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // Type module.
                $data[6] .= html_print_input_hidden(
                    'module-type-'.$uniqueId,
                    $module['type'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // Unit module.
                $data[6] .= html_print_input_hidden(
                    'module-unit-'.$uniqueId,
                    ($module['unit'] ?? $module['module_unit']),
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // Value module OID.
                $data[6] .= html_print_input_hidden(
                    'module-value-'.$uniqueId,
                    $module['value'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                if (empty($module['macros']) === false) {
                    // Macro module.
                    $data[6] .= html_print_input_hidden(
                        'module-macros-'.$uniqueId,
                        base64_encode($module['macros']),
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );
                }

                if (empty($module['execution_type']) === false) {
                    // Id plugin.
                    $data[6] .= html_print_input_hidden(
                        'module-execution_type-'.$uniqueId,
                        $module['execution_type'],
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );
                }

                if (empty($module['id_modulo']) === false) {
                    // Id module.
                    $data[6] .= html_print_input_hidden(
                        'module-id_modulo-'.$uniqueId,
                        $module['id_modulo'],
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );
                }

                if (empty($module['id_plugin']) === false) {
                    // Id plugin.
                    $data[6] .= html_print_input_hidden(
                        'module-id_plugin-'.$uniqueId,
                        $module['id_plugin'],
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );
                }

                // Macro module.
                $data[6] .= html_print_input_hidden(
                    'module-name-oid-'.$uniqueId,
                    $module['name_oid'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // Scan type module.
                $data[6] .= html_print_input_hidden(
                    'module-scan_type-'.$uniqueId,
                    $module['scan_type'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // WMI Query class.
                $data[6] .= html_print_input_hidden(
                    'module-query_class-'.$uniqueId,
                    $module['query_class'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // WMI Query key.
                $data[6] .= html_print_input_hidden(
                    'module-query_key_field-'.$uniqueId,
                    $module['query_key_field'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // WMI scan filters.
                $data[6] .= html_print_input_hidden(
                    'module-scan_filters-'.$uniqueId,
                    $module['scan_filters'],
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                // WMI query filters.
                $data[6] .= html_print_input_hidden(
                    'module-query_filters-'.$uniqueId,
                    base64_encode($module['query_filters']),
                    true,
                    $md5IdBlock,
                    'form="form-create-modules"'
                );

                if ($isInterface === true) {
                    // Is neccesary for default
                    // module name and description uin general monitoring.
                    $data[6] .= html_print_input_hidden(
                        'module-default_name-'.$uniqueId,
                        $module['name'],
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );

                    $data[6] .= html_print_input_hidden(
                        'module-default_description-'.$uniqueId,
                        $module['description'],
                        true,
                        $md5IdBlock,
                        'form="form-create-modules"'
                    );
                }

                array_push($table->data, $data);
            }

            $content = html_print_table($table, true);

            $open = true;
            $buttonSwitch = false;
            $attr = 'operstatus="'.$this->getOperStatus($idBlock).'" ';
            $attr .= 'adminstatus="';
            $attr .= $this->getAdminStatus($idBlock).'" ';

            $class = 'box-shadow white_table_graph interfaces_search';
            $reverseImg = true;
            if ($isPrincipal === true) {
                $open = false;
                $buttonSwitch = true;
                $class = 'box-shadow white_table_graph';
                $reverseImg = false;
            }

            $output .= ui_print_toggle(
                [
                    'content'           => $content,
                    'name'              => $blockTitle,
                    'title'             => '',
                    'id'                => $idBlock,
                    'hidden_default'    => $open,
                    'return'            => true,
                    'toggle_class'      => '',
                    'container_class'   => 'white-box-content',
                    'main_class'        => $class,
                    'clean'             => false,
                    'reverseImg'        => $reverseImg,
                    'switch'            => $buttonSwitch,
                    'attributes_switch' => 'form="form-create-modules"',
                    'toggl_attr'        => $attr,
                ]
            );
        }

        return $output;
    }


    /**
     * This function return the definition of modules for SNMP Interfaces
     *
     * @param array $data Data.
     *
     * @return array Return modules for defect.
     */
    private function getInterfacesModules(array $data=[])
    {
        $moduleDescription  = '';
        $name               = '';
        $value              = '_generic_';
        // Unpack the array with data.
        if (empty($data) === false) {
            if (empty($data['mac']) === false) {
                $moduleDescription .= 'MAC: '.$data['mac'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['ip']) === false) {
                $moduleDescription .= 'IP: '.$data['ip'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['alias']) === false) {
                $moduleDescription .= 'Alias: '.$data['alias'].' - ';
            } else {
                $moduleDescription .= '';
            }

            $name   = $data['name'].'_';
            $value  = $data['index'];
        }

        // Definition object.
        $definition = [];

        // Fulfill extra info.
        $this->interfacesData[$data['name']] = [];

        // IfOperStatus.
        $adminStatusValue = 1;
        $speed = 0;
        if (empty($data) === false) {
            $adminStatusValue = $this->snmpGetValue(
                '1.3.6.1.2.1.2.2.1.7.'.$value
            );

            $speed = $this->snmpGetValue(
                '.1.3.6.1.2.1.2.2.1.5.'.$value
            );

            preg_match('/\((\d+?)\)/', $adminStatusValue, $match);
            $adminStatusValue = (int) $match[1];
        }

        // IfOperStatus.
        $operStatusValue = 1;
        if (empty($data) === false) {
            $operStatusValue = $this->snmpGetValue(
                '1.3.6.1.2.1.2.2.1.8.'.$value
            );

            preg_match('/\((\d+?)\)/', $operStatusValue, $match);
            $operStatusValue = (int) $match[1];
        }

        // Store aux data.
        $this->interfacesData[$data['name']]['adminstatus'] = $adminStatusValue;
        $this->interfacesData[$data['name']]['operstatus'] = $operStatusValue;

        if ($adminStatusValue === 3) {
            $min_warning = 3;
            $max_warning = 4;
            $min_critical = 2;
            $max_critical = 3;
            $inv_warning = true;
            $inv_critical = false;
        } else if ($adminStatusValue === 2) {
            $min_warning = 3;
            $max_warning = 0;
            $min_critical = 1;
            $max_critical = 2;
            $inv_warning = false;
            $inv_critical = false;
        } else {
            $min_warning = 3;
            $max_warning = 0;
            $min_critical = 2;
            $max_critical = 3;
            $inv_warning = false;
            $inv_critical = false;
        }

        $moduleName = $name.'ifOperStatus';
        $definition['ifOperStatus'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The current operational state of the interface: up(1), down(2), testing(3), unknown(4), dormant(5), notPresent(6), lowerLayerDown(7)',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.8.'.$value,
            'module_unit'        => '',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOperStatus'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => $min_warning,
                'max_warning'   => $max_warning,
                'inv_warning'   => $inv_warning,
                'min_critical'  => $min_critical,
                'max_critical'  => $max_critical,
                'inv_critical'  => $inv_critical,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        // Get x86 or x64 modules.
        if ($this->interfacesx64 === true) {
            $definition_temp = $this->getInterfacesModulesx64($data);
        } else {
            $definition_temp = $this->getInterfacesModulesx86($data);
        }

        // General monitoring names.
        $general_module_names = [
            'ifInOctets / ifHCInOctets',
            'ifOutOctets / ifHCOutOctets',
            'ifInUcastPkts / ifHCInUcastPkts',
            'ifOutUcastPkts / ifHCOutUcastPkts',
            'ifInNUcastPkts / ifHCInNUcastPkts',
            'ifOutNUcastPkts / ifHCOutNUcastPkts',
        ];

        if ($name === '') {
            foreach ($definition_temp as $module => $module_def) {
                $definition_temp[$module]['module_name'] = array_shift(
                    $general_module_names
                );
            }
        }

        if (empty($definition_temp) === false) {
            $definition = array_merge($definition, $definition_temp);
        }

        // LocIfInCRC.
        $moduleName = $name.'locIfInCRC';
        $definition['locIfInCRC'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'Number of input packets which had cyclic redundancy checksum errors.',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.4.1.9.2.2.1.1.12.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['locIfInCRC'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        // Manufacturer specific modules.
        $pen = $this->getPEN();
        switch ($pen) {
            case 9:
                // CISCO.
                $valueTranslated = $this->snmpGetValueInverse(
                    '.1.3.6.1.4.1.9.5.1.4.1.1.11.1',
                    $value
                );
                if ($valueTranslated === false && $value !== '_generic_') {
                    $duplexMismatchOID = null;
                } else {
                    $duplexMismatchOID = '.1.3.6.1.4.1.9.5.1.4.1.1.10.1';
                    $duplexMismatchOID .= $valueTranslated;
                    $minc = 2.5;
                    $maxc = 3.5;
                }
            break;

            // TODO: Add here extra manufacturers.
            default:
                // Unknown.
                $duplexMismatchOID = null;
            break;
        }

        if (isset($duplexMismatchOID) === true) {
            // Duplex mismatch.
            $moduleName = $name.'DuplexMismatch';
            $definition['DuplexMismatch'] = [
                'module_name'        => $moduleName,
                'module_type'        => MODULE_TYPE_REMOTE_SNMP,
                'module_description' => sprintf(
                    '(%s%s)',
                    $moduleDescription,
                    $moduleName
                ),
                'module_info'        => 'Indicates whether the port is operating in half-duplex, full-duplex, disagree or auto negotiation mode. If the port could not agree with the far end on port duplex, the port will be in disagree(3) mode.',
                'execution_type'     => 'network',
                'value'              => $duplexMismatchOID,
                'default_enabled'    => (bool) $this->defaultSNMPValues['DuplexMismatch'],
                'module_enabled'     => false,
                'module_thresholds'  => [
                    'min_warning'   => '0',
                    'max_warning'   => '0',
                    'inv_warning'   => false,
                    'min_critical'  => $minc,
                    'max_critical'  => $maxc,
                    'inv_critical'  => false,
                    'perc_warning'  => false,
                    'perc_critical' => false,
                ],
            ];
        }

        // Bandwidth plugin.
        static $plugin;
        if ($plugin === null) {
            $plugin = \db_get_row_filter(
                'tplugin',
                [ 'name' => 'Network&#x20;bandwidth&#x20;SNMP' ]
            );
        }

        if ($plugin !== false) {
            // Network Bandwidth is installed.
            $plugin_id = $plugin['id'];
            $macros = json_decode($plugin['macros'], 1);
            if (json_last_error() === JSON_ERROR_NONE) {
                // SNMP Version.
                $macros[1]['value'] = $this->version;

                // Community.
                $macros[2]['value'] = $this->community;

                // Host.
                $macros[3]['value'] = $this->targetIp;

                // Port.
                $macros[4]['value'] = $this->targetPort;

                // Interface index filter.
                $macros[5]['value'] = $value;

                // SecurityName.
                $macros[6]['value'] = $this->authUserV3;

                // SecurityContext. Empty by default.
                $macros[7]['value'] = '';

                // SecurityLevel.
                $macros[8]['value'] = $this->securityLevelV3;

                // AuthProtocol.
                $macros[9]['value'] = $this->authMethodV3;

                // AuthKey.
                $macros[10]['value'] = $this->authPassV3;

                // PrivProtocol.
                $macros[11]['value'] = $this->privacyMethodV3;

                // PrivKey.
                $macros[12]['value'] = $this->privacyPassV3;

                // Hash identifier.
                $macros[13]['value'] = uniqid();

                // Get input usage.
                $macros[14]['value'] = 0;

                // Get output usage.
                $macros[15]['value'] = 0;

                $moduleName = $name.'Bandwidth';
                $definition['Bandwidth'] = [
                    'module_name'        => $moduleName,
                    'module_type'        => MODULE_TYPE_NUMERIC,
                    'module_description' => sprintf(
                        '(%s%s - Speed:%d)',
                        $moduleDescription,
                        $moduleName,
                        $speed
                    ),
                    'module_info'        => 'Amount of digital information sent and received from this interface over a particular time (see interval).',
                    'execution_type'     => EXECUTION_TYPE_PLUGIN,
                    'id_plugin'          => $plugin_id,
                    'id_modulo'          => MODULE_PLUGIN,
                    'macros'             => json_encode($macros),
                    'default_enabled'    => (bool) $this->defaultSNMPValues['Bandwidth'],
                    'module_enabled'     => false,
                    'module_unit'        => '%',
                    'module_thresholds'  => [
                        'min_warning'   => '0',
                        'max_warning'   => '0',
                        'inv_warning'   => false,
                        'min_critical'  => '85',
                        'max_critical'  => '0',
                        'inv_critical'  => false,
                        'perc_warning'  => false,
                        'perc_critical' => false,
                    ],
                ];

                // Hash identifier.
                $macros[13]['value'] = uniqid();

                // Get input usage.
                $macros[14]['value'] = 1;

                // Get output usage.
                $macros[15]['value'] = 0;

                $moduleName = $name.'inUsage';
                $definition['inUsage'] = [
                    'module_name'        => $moduleName,
                    'module_type'        => MODULE_TYPE_NUMERIC,
                    'module_description' => sprintf(
                        '(%s%s - Speed:%d)',
                        $moduleDescription,
                        $moduleName,
                        $speed
                    ),
                    'module_info'        => 'Bandwidth usage received into this interface over a particular time (see interval).',
                    'execution_type'     => EXECUTION_TYPE_PLUGIN,
                    'id_plugin'          => $plugin_id,
                    'id_modulo'          => MODULE_PLUGIN,
                    'macros'             => json_encode($macros),
                    'default_enabled'    => (bool) $this->defaultSNMPValues['inUsage'],
                    'module_enabled'     => false,
                    'module_unit'        => '%',
                    'module_thresholds'  => [
                        'min_warning'   => '0',
                        'max_warning'   => '0',
                        'inv_warning'   => false,
                        'min_critical'  => '0',
                        'max_critical'  => '0',
                        'inv_critical'  => false,
                        'perc_warning'  => false,
                        'perc_critical' => false,
                    ],
                ];

                // Hash identifier.
                $macros[13]['value'] = uniqid();

                // Get input usage.
                $macros[14]['value'] = 0;

                // Get output usage.
                $macros[15]['value'] = 1;

                $moduleName = $name.'outUsage';
                $definition['outUsage'] = [
                    'module_name'        => $moduleName,
                    'module_type'        => MODULE_TYPE_NUMERIC,
                    'module_description' => sprintf(
                        '(%s%s - Speed:%d)',
                        $moduleDescription,
                        $moduleName,
                        $speed
                    ),
                    'module_info'        => 'Bandwidth usage sent from this interface over a particular time (see interval).',
                    'execution_type'     => EXECUTION_TYPE_PLUGIN,
                    'id_plugin'          => $plugin_id,
                    'id_modulo'          => MODULE_PLUGIN,
                    'macros'             => json_encode($macros),
                    'default_enabled'    => (bool) $this->defaultSNMPValues['outUsage'],
                    'module_enabled'     => false,
                    'module_unit'        => '%',
                    'module_thresholds'  => [
                        'min_warning'   => '0',
                        'max_warning'   => '0',
                        'inv_warning'   => false,
                        'min_critical'  => '0',
                        'max_critical'  => '0',
                        'inv_critical'  => false,
                        'perc_warning'  => false,
                        'perc_critical' => false,
                    ],
                ];
            }
        }

        // Continue with common x86 and x84 modules.
        // IfAdminStatus.
        $moduleName = $name.'ifAdminStatus';
        $definition['ifAdminStatus'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The desired state of the interface: up(1), down(2), testing(3)',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.7.'.$value,
            'module_unit'        => '',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifAdminStatus'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfInDiscards.
        $moduleName = $name.'ifInDiscards';
        $definition['ifInDiscards'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of inbound packets which were chosen to be discarded even though no errors had been detected to prevent their being deliverable to a higher-layer protocol',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.13.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifInDiscards'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfOutDiscards.
        $moduleName = $name.'ifOutDiscards';
        $definition['ifOutDiscards'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of outbound packets which were chosen to be discarded even though no errors had been detected to prevent their being transmitted',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.19.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOutDiscards'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfInErrors.
        $moduleName = $name.'ifInErrors';
        $definition['ifInErrors'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'For packet-oriented interfaces, the number of inbound packets that contained errors preventing them from being deliverable to a higher-layer protocol. For character- oriented or fixed-length interfaces, the number of inbound transmission units that contained errors preventing them from being deliverable to a higher-layer protocol',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.14.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifInErrors'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfOutErrors.
        $moduleName = $name.'ifOutErrors';
        $definition['ifOutErrors'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'For packet-oriented interfaces, the number of outbound packets that could not be transmitted because of errors. For character-oriented or fixed-length interfaces, the number of outbound transmission units that could not be transmitted because of errors',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.20.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOutErrors'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        return $definition;
    }


    /**
     * This function return the definition of modules for x86 SNMP Interfaces
     *
     * @param array $data Data.
     *
     * @return array Return modules for defect.
     */
    private function getInterfacesModulesx86(array $data=[])
    {
        $moduleDescription  = '';
        $name               = '';
        $value              = '1';
        // Unpack the array with data.
        if (empty($data) === false) {
            if (empty($data['mac']) === false) {
                $moduleDescription .= 'MAC: '.$data['mac'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['ip']) === false) {
                $moduleDescription .= 'IP: '.$data['ip'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['alias']) === false) {
                $moduleDescription .= 'Alias: '.$data['alias'].' - ';
            } else {
                $moduleDescription .= '';
            }

            $name   = $data['name'].'_';
            $value  = $data['index'];
        }

        // Definition object.
        $definition = [];
        // IfInOctets.
        $moduleName = $name.'ifInOctets';
        $definition['ifInOctets'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of octets received on the interface, including framing characters',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.10.'.$value,
            'module_unit'        => 'bytes/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifInOctets'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],

        ];
        // IfOutOctets.
        $moduleName = $name.'ifOutOctets';
        $definition['ifOutOctets'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of octets transmitted out of the interface, including framing characters',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.16.'.$value,
            'module_unit'        => 'bytes/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOutOctets'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        // IfInUcastPkts.
        $moduleName = $name.'ifInUcastPkts';
        $definition['ifInUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of packets, delivered by this sub-layer to a higher (sub-)layer, which were not addressed to a multicast or broadcast address at this sub-layer',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.11.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifInUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfOutUcastPkts.
        $moduleName = $name.'ifOutUcastPkts';
        $definition['ifOutUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of packets that higher-level protocols requested be transmitted, and which were not addressed to a multicast or broadcast address at this sub-layer, including those that were discarded or not sent',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.17.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOutUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfInNUcastPkts.
        $moduleName = $name.'ifInNUcastPkts';
        $definition['ifInNUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of packets, delivered by this sub-layer to a higher (sub-)layer, which were addressed to a multicast or broadcast address at this sub-layer',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.12.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifInNUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfOutNUcastPkts.
        $moduleName = $name.'ifOutNUcastPkts';
        $definition['ifOutNUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of packets that higher-level protocols requested be transmitted, and which were addressed to a multicast or broadcast address at this sub-layer, including those that were discarded or not sent',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.2.2.1.18.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifOutNUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        return $definition;
    }


    /**
     * This function return the definition of modules for x64 SNMP Interfaces
     *
     * @param array $data Data.
     *
     * @return array Return modules for defect.
     */
    private function getInterfacesModulesx64(array $data=[])
    {
        $equivalencies_x86 = [
            'ifHCInOctets'      => 'ifInOctets',
            'ifHCOutOctets'     => 'ifOutOctets',
            'ifHCInUcastPkts'   => 'ifInUcastPkts',
            'ifHCOutUcastPkts'  => 'ifOutUcastPkts',
            'ifHCInNUcastPkts'  => 'ifInNUcastPkts',
            'ifHCOutNUcastPkts' => 'ifOutNUcastPkts',
        ];

        foreach ($equivalencies_x86 as $key => $equivalencie) {
            $this->defaultSNMPValues[$key] = $this->defaultSNMPValues[$equivalencie];
        }

        $moduleDescription  = '';
        $name               = '';
        $value              = '1';
        // Unpack the array with data.
        if (empty($data) === false) {
            if (empty($data['mac']) === false) {
                $moduleDescription .= 'MAC: '.$data['mac'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['ip']) === false) {
                $moduleDescription .= 'IP: '.$data['ip'].' - ';
            } else {
                $moduleDescription .= '';
            }

            if (empty($data['alias']) === false) {
                $moduleDescription .= 'Alias: '.$data['alias'].' - ';
            } else {
                $moduleDescription .= '';
            }

            $name   = $data['name'].'_';
            $value  = $data['index'];
        }

        // Definition object.
        $definition = [];
        // IfHCInOctets.
        $moduleName = $name.'ifHCInOctets';
        $definition['ifHCInOctets'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of octets received on the interface, including framing characters',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.6.'.$value,
            'module_unit'        => 'bytes/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCInOctets'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],

        ];
        // IfHCOutOctets.
        $moduleName = $name.'ifHCOutOctets';
        $definition['ifHCOutOctets'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of octets transmitted out of the interface, including framing characters',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.10.'.$value,
            'module_unit'        => 'bytes/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCOutOctets'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        // IfHCInUcastPkts.
        $moduleName = $name.'ifHCInUcastPkts';
        $definition['ifHCInUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of packets, delivered by this sub-layer to a higher (sub-)layer, which were not addressed to a multicast or broadcast address at this sub-layer',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.7.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCInUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        // IfHCOutUcastPkts.
        $moduleName = $name.'ifHCOutUcastPkts';
        $definition['ifHCOutUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of packets that higher-level protocols requested be transmitted, and which were not addressed to a multicast or broadcast address at this sub-layer, including those that were discarded or not sent',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.11.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCOutUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfHCInNUcastPkts.
        $moduleName = $name.'ifHCInNUcastPkts';
        $definition['ifHCInNUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The number of packets, delivered by this sub-layer to a higher (sub-)layer, which were addressed to a multicast or broadcast address at this sub-layer',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.7.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCInNUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];
        // IfOutNUcastPkts.
        $moduleName = $name.'ifHCOutNUcastPkts';
        $definition['ifHCOutNUcastPkts'] = [
            'module_name'        => $moduleName,
            'module_type'        => MODULE_TYPE_REMOTE_SNMP_INC,
            'module_description' => sprintf(
                '(%s%s)',
                $moduleDescription,
                $moduleName
            ),
            'module_info'        => 'The total number of packets that higher-level protocols requested be transmitted, and which were addressed to a multicast or broadcast address at this sub-layer, including those that were discarded or not sent',
            'execution_type'     => 'network',
            'value'              => '1.3.6.1.2.1.31.1.1.1.11.'.$value,
            'module_unit'        => 'packets/s',
            'default_enabled'    => (bool) $this->defaultSNMPValues['ifHCOutNUcastPkts'],
            'module_enabled'     => false,
            'module_thresholds'  => [
                'min_warning'   => '0',
                'max_warning'   => '0',
                'inv_warning'   => false,
                'min_critical'  => '0',
                'max_critical'  => '0',
                'inv_critical'  => false,
                'perc_warning'  => false,
                'perc_critical' => false,
            ],
        ];

        return $definition;
    }


    /**
     * Watch if is a arithmetic operation and perform it.
     *
     * @param string       $operation Operation for perform.
     * @param string       $unit      If filled,
     * add unit of measure to the output.
     * @param integer|null $type      Module type.
     *
     * @return string
     * @throws Exception Handle of unwanted operations.
     */
    private function evalOperation(
        string $operation,
        string $unit='',
        ?int $type=0
    ) {
        $output = '';
        try {
            // Avoid non-numeric or arithmetic chars for security reasons.
            if (preg_match('/(([^0-9\s\+\-\*\/\(\).,])+)/', $operation) === 1) {
                throw new Exception(sprintf(__("The operation '%s' is not permitted. Review for remote components."), $operation));
            } else {
                // Get the result of the operation and set it.
                eval('$output = '.$operation.';');
                // If this module has unit, attach to current value.
                $output = $this->replacementUnit(
                    round($output, 2),
                    $unit,
                    $type
                );
            }
        } catch (Exception $e) {
            $this->message['type'][]    = 'error';
            $this->message['message'][] = $e->getMessage();
            $this->showMessage();
        }

        return $output;
    }


    /**
     * Filters macros in attributes
     *
     * @param string $attribute   String for manage.
     * @param array  $columnsList List of the columns.
     * @param array  $rowList     List of the values of current row.
     *
     * @return string Returns the value filtered.
     */
    private function macroFilter(
        string $attribute,
        array $columnsList,
        array $rowList
    ) {
        // By default, the output is the raw input of attribute.
        $output = $attribute;
        // If the attribute has a macro, here is filled with the info.
        if (preg_match('/_(.*?)_/', $attribute, $macro) !== 0) {
            $indexColumn = array_search($macro[1], $columnsList, true);
            if ($indexColumn !== false) {
                $output = str_replace(
                    $macro[0],
                    $rowList[$indexColumn],
                    $attribute
                );
            }
        }

        return $output;
    }


    /**
     * WMI query execution.
     *
     * @param string $execution Entire string with the execution command.
     *
     * @return mixed Result of the operation.
     */
    private function wmiExecution(string $execution)
    {
        $output = [];
        try {
            exec($execution, $output);
        } catch (Exception $ex) {
            $output = ['0' => 'ERROR: Failed execution: '.(string) $ex];
        }

        return $output;
    }


    /**
     * WMI query constructor.
     *
     * @param array   $moduleAttr Array with attributes of modules.
     * @param string  $filterType If filled, what query filter to use.
     * @param boolean $onlyQuery  Return only query, no command.
     *
     * @return string A string with the complete query to perform
     */
    private function wmiQuery(
        array $moduleAttr,
        string $filterType='',
        ?bool $onlyQuery=false
    ) {
        // Definition of vars.
        $queryClass = $moduleAttr['query_class'];
        $queryFilters = json_decode(
            $moduleAttr['query_filters'],
            true
        );
        $macros = json_decode($moduleAttr['macros'], true);

        $queryFields = [];

        // If query key field is filled, add to the query fields.
        if (empty($moduleAttr['query_key_field']) === false) {
            $queryFields[] = $moduleAttr['query_key_field'];
        }

        // Unpack the macros.
        foreach ($macros as $key => $macro) {
            // Only attach extra field macros and with data inside.
            if (preg_match('/extra_field_/', $key) !== 0) {
                if (empty($macro) === false) {
                    $queryFields[] = $macro;
                }
            }
        }

        // Generate the string with fields to perform the query.
        $queryFieldsStr = implode(',', $queryFields);

        // Where statement.
        if (($filterType === 'scan' || $filterType === 'execution')
            && empty($queryFilters[$filterType]) === false
        ) {
            $queryWhere = ' WHERE ';
            $queryWhere .= $queryFilters[$filterType];
        } else {
            $queryWhere = ' ';
        }

        if ($onlyQuery === true) {
            // Set up the execute command.
            $executeCommand = sprintf(
                'SELECT %s FROM %s%s',
                $queryFieldsStr,
                $queryClass,
                $queryWhere
            );
        } else {
            // Set up the execute command.
            $executeCommand = sprintf(
                '%s \'SELECT %s FROM %s%s\'',
                $this->wmiCommand,
                $queryFieldsStr,
                $queryClass,
                $queryWhere
            );
        }

        return $executeCommand;
    }


    /**
     * Generate the JS needed for use inside
     *
     * @return mixed
     */
    private function loadJS()
    {
        $str = '';

        ob_start();
        ?>
        <script type="text/javascript">

            function filterInterfaces() {
                var string = $('#text-filter-search').val().trim();
                var filter_online = document.getElementById('search-admin').checked;
                var filter_up = document.getElementById('search-oper').checked;

                var regex = new RegExp(string, 'i');
                var interfaces = $('.interfaces_search');

                interfaces.each(function() {
                    if (string == ''
                    && filter_up == false
                    && filter_online == false
                    ) {
                        $(this).removeClass('hidden');
                        return;
                    }

                    if (this.id.match(regex)) {
                        $(this).removeClass('hidden');
                    } else {
                        $(this).addClass('hidden');
                    }

                    if (filter_online == true) {
                        if ($(this).attr('adminstatus') != 1) {
                            $(this).addClass('hidden');
                        }
                    }

                    if (filter_up == true) {
                        if ($(this).attr('operstatus') != 1) {
                            $(this).addClass('hidden');
                        }
                    }
                });
            }


            $(document).ready(function() {
                // Meta.
                var meta = "<?php echo is_metaconsole(); ?>";
                var hack_meta = '';
                if (meta) {
                    hack_meta = '../../';
                }

                // If snmp version selected is 3.
                showV3Form();

                // Filter search interfaces snmp.
                $('#text-filter-search').keyup(function() {
                    filterInterfaces();
                });

                $('#credential').change(function() {
                    if ($('#credential').val() !== '0') {
                        $.ajax({
                            method: "post",
                            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                            data: {
                                page: "<?php echo $this->ajaxController; ?>",
                                method: "getCredentials",
                                identifier: $('#credential').val()
                            },
                            datatype: "json",
                            success: function(data) {
                                data = JSON.parse(data);

                                if ($('#text-namespaceWMI').length > 0) {
                                    // WMI.
                                    $('#text-namespaceWMI').val(data['extra_1']);
                                    $('#text-usernameWMI').val(data['username']);
                                    $('#password-passwordWMI').val(data['password']);
                                } else {
                                    // SNMP.
                                    extra = JSON.parse(data['extra_1']);
                                    $('#version').val(extra['version']);
                                    $('#version').trigger('change');
                                    $('#text-community').val(extra['community']);

                                    if (extra['version'] === '3') {
                                        $('#securityLevelV3').val(extra['securityLevelV3']);
                                        $('#securityLevelV3').trigger('change');
                                        $('#text-authUserV3').val(extra['authUserV3']);

                                        if (extra['securityLevelV3'] === 'authNoPriv' || extra['securityLevelV3'] === 'authPriv') {
                                            $('#authMethodV3').val(extra['authMethodV3']);
                                            $('#authMethodV3').trigger('change');
                                            $('#password-authPassV3').val(extra['authPassV3']);

                                            if (extra['securityLevelV3'] === 'authPriv') {
                                                $('#privacyMethodV3').val(extra['privacyMethodV3']);
                                                $('#privacyMethodV3').trigger('change');
                                                $('#password-privacyPassV3').val(extra['privacyPassV3']);
                                            }
                                        }
                                    }
                                }
                            },
                            error: function(e) {
                                showMsg(e);
                            }
                        });
                    }
                });

                // Loading.
                $('#button-sub-protocol').click(function() {
                    $('.wizard-result').remove();
                    $('#form-create-modules').remove();
                    $('.textodialogo').remove();
                    $('.loading-wizard')
                        .html('<center><span class="font_25px">Loading...</span><img class="w25px height_25px" src="' + hack_meta + 'images/spinner.gif"></center>');
                });

            });

            function showV3Form() {
                var selector = $('#version').val();
                if (selector == '3') {
                    $('#form-snmp-authentication-box').removeClass('invisible');
                    showSecurityLevelForm();
                } else {
                    $('#form-snmp-authentication-box').addClass('invisible');
                }

            }

            function showSecurityLevelForm() {
                var selector = $('#securityLevelV3').val();
                if (selector === 'authNoPriv' || selector === 'authPriv') {
                    $('#txt-authMethodV3').removeClass('invisible');
                    $('#txt-authPassV3').removeClass('invisible');
                    if (selector === 'authPriv') {
                        $('#txt-privacyMethodV3').removeClass('invisible');
                        $('#txt-privacyPassV3').removeClass('invisible');
                    } else {
                        $('#txt-privacyMethodV3').addClass('invisible');
                        $('#txt-privacyPassV3').addClass('invisible');
                    }
                } else {
                    $('#txt-authMethodV3').addClass('invisible');
                    $('#txt-authPassV3').addClass('invisible');
                    $('#txt-privacyMethodV3').addClass('invisible');
                    $('#txt-privacyPassV3').addClass('invisible');
                }
            }


            function showMsg(data) {
                var title = "<?php echo __('Success'); ?>";
                var text = "";
                var failed = 0;
                try {
                    data = JSON.parse(data);
                    text = data["result"];
                } catch (err) {
                    title = "<?php echo __('Failed'); ?>";
                    text = err.message;
                    failed = 1;
                }
                if (!failed && data["error"] != undefined) {
                    title = "<?php echo __('Failed'); ?>";
                    text = data["error"];
                    failed = 1;
                }
                if (data["report"] != undefined) {
                    data["report"].forEach(function(item) {
                        text += "<br>" + item;
                    });
                }

                $("#msg").empty();
                $("#msg").html(text);
                $("#msg").dialog({
                    width: 450,
                    position: {
                        my: "center",
                        at: "center",
                        of: window,
                        collision: "fit"
                    },
                    title: title,
                    buttons: [{
                        class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                        text: "OK",
                        click: function(e) {
                            $("#msg").close();
                        }
                    }]
                });

            }

            /**
             * Controls checkboxes for modules.
             */
            function switchBlockControl(e) {
                var switchId = e.target.id.split("_");
                var switchName = e.target.name.split("_");
                var type = switchId[1];
                var blockNumber = switchId[2];
                var moduleNumber = switchId[3];
                var selectedBlock = $("#checkbox-sel_block_" + blockNumber);
                var imageInfoModules = $("#image-info-modules-" + blockNumber);
                var totalCount = 0;
                var markedCount = 0;
                if (type == 'block') {
                    selectedBlock
                        .parent()
                        .removeClass("alpha50");
                        if (selectedBlock.prop("checked")) {
                            // Set all inputs in block to checked.
                            var blockItems = document.querySelectorAll('[id^="hidden-module-active-'+switchName[2]+'"]');

                            blockItems.forEach(function(item) {
                                item.value = 1;
                            });

                            // Set block selector to checked.
                            $("[id*=checkbox-sel_module_" + blockNumber + "]")
                                .each(function() {
                                    $(this).prop("checked", true);
                                });
                            imageInfoModules.removeClass('hidden');
                        } else {
                            // Set all inputs in block to unchecked.
                            var blockItems = document.querySelectorAll('[id^="hidden-module-active-'+switchName[2]+'"]');

                            blockItems.forEach(function(item) {
                                item.value = 0;
                            });

                            // Set block selector to unchecked.
                            $("[id*=checkbox-sel_module_" + blockNumber + "]")
                                .each(function() {
                                    $(this).prop("checked", false);
                                });
                            imageInfoModules.addClass('hidden');
                    }
                } else if (type == 'module') {
                    // Getting the element.
                    var thisModuleHidden = document.getElementById("hidden-module-active-" + switchName[2] + "_" + moduleNumber);
                    var thisModule = $("#checkbox-sel_module_" + blockNumber + "_" + moduleNumber);
                    // Setting the individual field
                    if (thisModule.prop('checked')) {
                        thisModuleHidden.value = '1';
                    } else {
                        thisModuleHidden.value = '0';
                    }

                    // Get the list of selected modules.
                    $("[id*=checkbox-sel_module_" + blockNumber + "]")
                        .each(function() {
                            if ($(this).prop("checked")) {
                                markedCount++;
                            }
                            totalCount++;
                        });

                    if (totalCount == markedCount) {
                        selectedBlock.prop("checked", true);
                        selectedBlock
                            .parent()
                            .removeClass("alpha50");
                        imageInfoModules.removeClass('hidden');
                    } else if (markedCount == 0) {
                        selectedBlock.prop("checked", false);
                        selectedBlock
                            .parent()
                            .removeClass("alpha50");
                        imageInfoModules.addClass('hidden');
                    } else {
                        selectedBlock.prop("checked", true);
                        selectedBlock
                            .parent()
                            .addClass("alpha50");
                        imageInfoModules.removeClass('hidden');
                    }
                }
            }

            /**
             * Controls checkboxes for modules.
             */
            function switchBlockControlInterfaces(e) {
                // Apply filters if not done yet.
                //filterInterfaces();
                // Select targets.
                var interfaces = document.querySelectorAll(
                    '.interfaces_search:not(.hidden)'
                );

                // Apply selection.
                for (let iface of interfaces) {
                    iface.querySelector('input[type="checkbox"]').checked = e.checked;
                }

            }

            /**
             * Show the modal with modules for create.
             */
            function processListModules() {
                confirmDialog({
                    title: "<?php echo __('Modules about to be created'); ?>",
                    hideOkButton: true,
                    message: function() {
                        var id = "div-" + uniqId();
                        var loading = "<?php echo __('Loading, this operation might take several minutes...'); ?>";
                        var datas = {};

                        let inputs = document.querySelectorAll("input,textarea");

                        for (let input of inputs) {
                            let id = input.className;
                            let chkbox =document.getElementById('interfaz_select_'+id);
                            if (chkbox != undefined
                                && chkbox.checked == false
                            ) {
                                // Skip disabled containers.
                                continue;
                            }

                            if (input.type != "checkbox") {
                                datas[input.name] = input.value;
                            }
                            if (input.type == "checkbox" && input.checked) {
                                datas[input.name] = input.value;
                            }

                        };

                        $.ajax({
                            method: "post",
                            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                            data: {
                                page: "<?php echo $this->ajaxController; ?>",
                                method: "listModulesToCreate",
                                data: JSON.stringify(datas),
                                id_agente: "<?php echo $this->idAgent; ?>",
                                id: "<?php echo $this->idPolicy; ?>"
                            },
                            datatype: "html",
                            success: function(data) {
                                // Show hidden OK button
                                $('.sub.ok.submit-next').removeClass('invisible_important');
                                $('#' + id).empty().append(data);
                            },
                            error: function(e) {
                                showMsg(e);
                            }
                        });

                        return "<div id ='" + id + "'>" + loading + "</div>";
                    },
                    ok: "<?php echo __('OK'); ?>",
                    cancel: "<?php echo __('Cancel'); ?>",
                    onAccept: function() {
                        $('#reviewed-modules').submit();
                    },
                    size: 750,
                    maxHeight: 500
                });

            }

            function change_control(checkbox, uniqueId) {
                var checkbox_name = $(checkbox).attr('name');

                if($(checkbox).prop('checked', true)) {
                   if(checkbox_name.match(/warning-inv/gm) !== null) {
                        $('#checkbox-module-warning-perc-'+uniqueId.replace('/','\\/')).prop('checked', false);
                   }

                   if(checkbox_name.match(/critical-inv/gm) !== null) {
                        $('#checkbox-module-critical-perc-'+uniqueId.replace('/','\\/')).prop('checked', false);
                    }

                    if(checkbox_name.match(/warning-perc/gm) !== null) {
                        $('#checkbox-module-warning-inv-'+uniqueId.replace('/','\\/')).prop('checked', false);
                    }

                    if(checkbox_name.match(/critical-perc/gm) !== null) {
                        $('#checkbox-module-critical-inv-'+uniqueId.replace('/','\\/')).prop('checked', false);
                    }
                }

            }
        </script>
        <?php
        $str = ob_get_clean();
        echo $str;
        return $str;
    }


}
