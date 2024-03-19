<?php
/**
 * Cloud wizard manager.
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Cloud
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/class/CredentialStore.class.php';

/**
 * Implements Wizard to provide generic Cloud wizard.
 */
class Cloud extends Wizard
{

    /**
     * Sub-wizard to be launch (vmware,oracle...).
     *
     * @var string
     */
    public $mode;

    /**
     * Discovery task data.
     *
     * @var array.
     */
    public $task;

    /**
     * General maxPages.
     *
     * @var integer
     */
    public $maxPages;

    /**
     * Product string.
     *
     * @var string
     */
    protected $product = '';

    /**
     * Credentials store identifier.
     *
     * @var string
     */
    protected $keyIdentifier = null;

    /**
     * Credentials store product identifier.
     *
     * @var string
     */
    protected $keyStoreType = null;


    /**
     * Constructor.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Default message to show to users.
     * @param string  $icon  Target icon to be used.
     * @param string  $label Target label to be displayed.
     *
     * @return mixed
     */
    public function __construct(
        int $page=0,
        string $msg='Default message. Not set.',
        string $icon='images/wizard/cloud.png',
        string $label='Cloud'
    ) {
        $this->setBreadcrum([]);

        $this->access = 'AW';
        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = __($label);
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud'
        );

        return $this;
    }


    /**
     * Run wizard manager.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run()
    {
        // Load styles.
        parent::run();

        // Load current wiz. sub-styles.
        ui_require_css_file(
            'cloud',
            ENTERPRISE_DIR.'/include/styles/wizards/'
        );

        $mode = get_parameter('mode', null);

        $extensions = new ExtensionsDiscovery('cloud', $mode);

        if ($mode !== null) {
            // Load extension if exist.
            $extensions->run();
            return;
        }

        // Load classes and print selector.
        $this->prepareBreadcrum(
            [
                [
                    'link'  => ui_get_full_url(
                        'index.php?sec=gservers&sec2=godmode/servers/discovery'
                    ),
                    'label' => __('Discovery'),
                ],
                [
                    'link'     => $this->url,
                    'label'    => __('Cloud'),
                    'selected' => true,
                ],
            ],
            true
        );

        // Header.
        ui_print_page_header(
            __('Cloud'),
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

        Wizard::printBigButtonsList($extensions->loadExtensions());

        $not_defined_extensions = $extensions->loadExtensions(true);

        $output = html_print_div(
            [
                'class'   => 'agent_details_line',
                'content' => ui_toggle(
                    Wizard::printBigButtonsList($not_defined_extensions, true),
                    '<span class="subsection_header_title">'.__('Not installed').'</span>',
                    'not_defined_apps',
                    'not_defined_apps',
                    false,
                    true,
                    '',
                    '',
                    'box-flat white_table_graph w100p'
                ),
            ],
        );

        echo $output;

        echo '<div class="app_mssg"><i>*'.__('All company names used here are for identification purposes only. Use of these names, logos, and brands does not imply endorsement.').'</i></div>';
    }


    /**
     * Run credentials wizard.
     *
     * @return boolean True if credentials wizard is displayed and false if not.
     */
    public function runCredentials()
    {
        global $config;

        if ($this->status === false) {
            $empty_account = true;
        }

        // Checks credentials. If check not passed. Show the form to fill it.
        if ($this->checkCredentials()) {
            return true;
        }

        // Add breadcrum and print header.
        $this->prepareBreadcrum(
            [
                [
                    'link'     => $this->url.'&credentials=1',
                    'label'    => __('%s credentials', $this->product),
                    'selected' => true,
                ],
            ],
            true
        );
        // Header.
        ui_print_page_header(
            __('%s credentials', $this->product),
            '',
            false,
            $this->product.'_credentials_tab',
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

        if ($this->product === 'Aws') {
            ui_print_warning_message(
                __(
                    'If a task with the selected credentials is already running, it will be edited. To create a new one, another account from the credential store must be selected.'
                )
            );
        }

        if ($this->status === true) {
            ui_print_success_message($this->msg);
        } else if ($this->status === false) {
            ui_print_error_message($this->msg);
        }

        if (isset($empty_account) === true) {
            ui_print_error_message($this->msg);
        }

        $link_to_cs = '';
        if (check_acl($config['id_user'], 0, 'UM')) {
            $link_to_cs = '<a class="ext_link" href="'.ui_get_full_url(
                'index.php?sec=gmodules&sec2=godmode/groups/group_list&tab=credbox'
            ).'" >';
            $link_to_cs .= __('Manage accounts').'</a>';
        }

        $this->getCredentials();
        $this->printFormAsList(
            [
                'form'   => [
                    'action' => $this->url,
                    'method' => 'POST',
                    'id'     => 'form-credentials',
                ],
                'inputs' => [
                    [
                        'label'     => __('Cloud tool full path'),
                        'arguments' => [
                            'name'  => 'cloud_util_path',
                            'value' => isset($config['cloud_util_path']) ? io_safe_output($config['cloud_util_path']) : '/usr/bin/pandora-cm-api',
                            'type'  => 'text',
                        ],
                    ],
                    [
                        'label'     => __('Account'),
                        'extra'     => $link_to_cs,
                        'arguments' => [
                            'name'     => 'account_identifier',
                            'type'     => 'select',
                            'fields'   => CredentialStore::getKeys($this->keyStoreType),
                            'selected' => (isset($this->keyIdentifier) === true) ? $this->keyIdentifier : '',
                            'return'   => true,
                        ],
                    ],
                    [
                        'arguments' => [
                            'name'   => 'parse_credentials',
                            'value'  => 1,
                            'type'   => 'hidden',
                            'return' => true,
                        ],
                    ],
                ],
            ]
        );

        $buttons_form = $this->printInput(
            [
                'name'       => 'submit',
                'label'      => __('Validate'),
                'type'       => 'submit',
                'attributes' => [
                    'icon' => 'wand',
                    'form' => 'form-credentials',
                ],
                'return'     => true,
                'width'      => 'initial',
            ]
        );

        $buttons_form .= $this->printGoBackButton(
            ui_get_full_url(
                'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud'
            ),
            true
        );

        html_print_action_buttons($buttons_form);
        return false;
    }


    /**
     * Check credentials.
     *
     * @return boolean True if credentials are OK.
     */
    public function checkCredentials()
    {
        global $config;

        $pandora = io_safe_output(($config['cloud_util_path'] ?? ''));

        if (isset($pandora) === false) {
            config_update_value('cloud_util_path', '/usr/bin/pandora-cm-api');
        }

        if ((bool) get_parameter('disconnect_account', false) === true) {
            $this->status = null;
            return false;
        }

        if (isset($this->keyIdentifier) === false) {
            // Ask user for available credentials.
            $this->msg = __('Select a set of credentials from the list');
            $this->status = null;
            return false;
        }

        $credentials = $this->getCredentials($this->keyIdentifier);

        if (empty($credentials['username']) === true
            || empty($credentials['password']) === true
            || isset($pandora) === false
            || is_executable($pandora) === false
        ) {
            if (is_executable($pandora) === false) {
                $this->msg = (__('Path %s is not executable.', $pandora));
                $this->status = false;
            } else {
                $this->msg = __('Invalid username or password');
                $this->status = false;
            }

            return false;
        }

        try {
            $value = $this->executeCMCommand('--get availability');
        } catch (Exception $e) {
            $this->msg = $e->getMessage();
            $this->status = false;
            return false;
        }

        if ($value == '1') {
            return true;
        }

        $this->status = false;

        // Error message directly from pandora-cm-api.
        $this->msg = str_replace('"', '', $value);

        return false;
    }


    /**
     * Handle the click on disconnect account link.
     *
     * @return void But it prints some info to user.
     */
    protected function parseDisconnectAccount()
    {
        // Check if disconection account link is pressed.
        if ((bool) get_parameter('disconnect_account') === false) {
            return;
        }

        $ret = $this->setCredentials(null);
        if ($ret) {
            $this->msg = __('Account disconnected');
        } else {
            $this->msg = __('Failed disconnecting account');
        }

        $this->status = $ret;
        $this->page = 0;
    }


    /**
     * Build an array with Product credentials.
     *
     * @return array with credentials (pass and id).
     */
    public function getCredentials()
    {
        return CredentialStore::getKey((isset($this->keyIdentifier) === true) ? $this->keyIdentifier : '');
    }


    /**
     * Set Product credentials.
     *
     * @param string|null $identifier Credential store identifier.
     *
     * @return boolean True if success.
     */
    public function setCredentials($identifier)
    {
        if ($identifier === null) {
            unset($this->keyIdentifier);
            return true;
        }

        if (isset($identifier) === false) {
            return false;
        }

        $all = CredentialStore::getKeys($this->type);

        if (in_array($identifier, $all) === true) {
            $this->keyIdentifier = $identifier;
            return true;
        }

        return false;
    }


    /**
     * Parse credentials form.
     *
     * @return void But it prints a message.
     */
    protected function parseCredentials()
    {
        global $config;

        if (!$this->keyIdentifier) {
            $this->setCredentials(get_parameter('ki', null));
        }

        // Check if credentials form is submitted.
        if ((bool) get_parameter('parse_credentials') === false) {
            return;
        }

        $this->page = 0;
        $ret = $this->setCredentials(
            get_parameter('account_identifier')
        );

        $path = get_parameter('cloud_util_path');
        $ret_path = config_update_value('cloud_util_path', $path);
        if ($ret_path) {
            $config['cloud_util_path'] = $path;
        }

        if ($ret && $ret_path) {
            $this->msg = __('Credentials successfully updated');
        } else {
            $this->msg = __('Failed updating credentials process');
        }

        $this->status = ($ret && $ret_path);
    }


    /**
     * This method must be implemented.
     *
     * Execute a pandora-cm-api request.
     *
     * @param string $command Command to execute.
     *
     * @return void But must return string STDOUT of executed command.
     * @throws Exception If not implemented.
     */
    protected function executeCMCommand($command)
    {
        throw new Exception('executeCMCommand must be implemented.');
    }


    /**
     * Get a recon token value
     *
     * @param string $token The recon key to retrieve.
     *
     * @return string String with the value.
     */
    protected function getConfigReconElement($token)
    {
        if ($this->reconConfig === false
            || isset($this->reconConfig[0][$token]) === false
        ) {
            if (is_array($this->task) === true
                && isset($this->task[$token]) === true
            ) {
                return $this->task[$token];
            } else {
                return '';
            }
        } else {
            return $this->reconConfig[0][$token];
        }
    }


    /**
     * Print global inputs
     *
     * @param boolean $last True if is last element.
     *
     * @return array Array with all global inputs.
     */
    protected function getGlobalInputs(bool $last=false)
    {
        $task_id = $this->task['id_rt'];
        if (!$task_id) {
            $task_id = $this->getConfigReconElement('id_rt');
        }

        return [
            [
                'arguments' => [
                    'name'   => 'page',
                    'value'  => ($this->page + 1),
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ],
            [
                'arguments' => [
                    'name'       => 'submit',
                    'label'      => ($last) ? __('Finish') : __('Next'),
                    'type'       => 'submit',
                    'attributes' => 'class="sub '.(($last) ? 'wand' : 'next').'"',
                    'return'     => true,
                ],
            ],
            [
                'arguments' => [
                    'name'   => 'task',
                    'value'  => $task_id,
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ],
            [
                'arguments' => [
                    'name'   => 'parse_form',
                    'value'  => 1,
                    'type'   => 'hidden',
                    'return' => true,
                ],
            ],
        ];
    }


    /**
     * Print required css in some points.
     *
     * @return string With js code.
     */
    protected function cloudJS()
    {
        return '
            function toggleCloudSubmenu(curr_elem, id_csm){
                if (document.getElementsByName(curr_elem)[0].checked){
                    $("#li-"+id_csm).show();
                } else {
                    $("#li-"+id_csm).hide();
                }
            };
        ';
    }


    /**
     * Check if section have extensions.
     *
     * @return boolean Return true if section is empty.
     */
    public function isEmpty()
    {
        $extensions = new ExtensionsDiscovery('cloud');
        $listExtensions = $extensions->getExtensionsApps();
        if ($listExtensions > 0 || enterprise_installed() === true) {
            return false;
        } else {
            return true;
        }
    }


}
