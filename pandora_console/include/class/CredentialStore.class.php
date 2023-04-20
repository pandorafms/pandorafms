<?php
/**
 * Credential store
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Credential store
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

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * Provides functionality for credential store.
 */
class CredentialStore extends Wizard
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
    public $AJAXMethods = [
        'draw',
        'loadModal',
        'addKey',
        'updateKey',
        'deleteKey',
    ];


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

        if ((bool) check_acl($config['id_user'], 0, 'PM') === false
            || (bool) check_acl($config['id_user'], 0, 'UM') === false
        ) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access credential store'
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
     * Returns an array with all the credentials matching filter and ACL.
     *
     * @param array   $fields     Fields array or 'count' keyword to retrieve count.
     * @param array   $filter     Filters to be applied.
     * @param integer $offset     Offset (pagination).
     * @param integer $limit      Limit (pagination).
     * @param string  $order      Sort order.
     * @param string  $sort_field Sort field.
     *
     * @return array With all results or false if error.
     * @throws Exception On error.
     */
    public static function getAll(
        $fields,
        $filter,
        $offset=null,
        $limit=null,
        $order=null,
        $sort_field=null
    ) {
        $sql_filters = [];
        $order_by = '';
        $pagination = '';

        global $config;

        if (!is_array($filter)) {
            error_log('[credential_get_all] Filter must be an array.');
            throw new Exception('[credential_get_all] Filter must be an array.');
        }

        $count = false;
        if (!is_array($fields) && $fields == 'count') {
            $fields = ['cs.*'];
            $count = true;
        } else if (!is_array($fields)) {
            error_log('[credential_get_all] Fields must be an array or "count".');
            throw new Exception('[credential_get_all] Fields must be an array or "count".');
        }

        if (isset($filter['product']) && !empty($filter['product'])) {
            $sql_filters[] = sprintf(' AND cs.product = "%s"', $filter['product']);
        }

        if (isset($filter['free_search']) && !empty($filter['free_search'])) {
            $sql_filters[] = vsprintf(
                ' AND (lower(cs.username) like lower("%%%s%%")
                    OR cs.identifier like "%%%s%%"
                    OR lower(cs.product) like lower("%%%s%%"))',
                array_fill(0, 3, $filter['free_search'])
            );
        }

        if (isset($filter['filter_id_group']) && $filter['filter_id_group'] > 0) {
            $propagate = db_get_value(
                'propagate',
                'tgrupo',
                'id_grupo',
                $filter['filter_id_group']
            );

            if (!$propagate) {
                $sql_filters[] = sprintf(
                    ' AND cs.id_group = %d ',
                    $filter['filter_id_group']
                );
            } else {
                $groups = [ $filter['filter_id_group'] ];
                $childrens = groups_get_children(
                    $filter['filter_id_group'],
                    null,
                    true
                );
                if (!empty($childrens)) {
                    foreach ($childrens as $child) {
                        $groups[] = (int) $child['id_grupo'];
                    }
                }

                $filter['filter_id_group'] = $groups;
                $sql_filters[] = sprintf(
                    ' AND cs.id_group IN (%s) ',
                    join(',', $filter['filter_id_group'])
                );
            }
        }

        if (isset($filter['group_list']) && is_array($filter['group_list'])) {
            $sql_filters[] = sprintf(
                ' AND cs.id_group IN (%s) ',
                join(',', $filter['group_list'])
            );
        } else if (users_is_admin() !== true) {
            $user_groups = users_get_groups(
                $config['id_user'],
                'AR'
            );

            // Always add group 'ALL' because 'ALL' group credentials
            // must be available for all users.
            if (is_array($user_groups) === true) {
                $user_groups = ([0] + array_keys($user_groups));
            } else {
                $user_groups = [0];
            }

            $sql_filters[] = sprintf(
                ' AND cs.id_group IN (%s) ',
                join(',', $user_groups)
            );
        }

        if (isset($filter['identifier'])) {
            $sql_filters[] = sprintf(
                ' AND cs.identifier = "%s" ',
                $filter['identifier']
            );
        }

        if (isset($order)) {
            $dir = 'asc';
            if ($order == 'desc') {
                $dir = 'desc';
            };

            if (in_array(
                $sort_field,
                [
                    'group',
                    'identifier',
                    'product',
                    'username',
                    'options',
                ]
            )
            ) {
                $order_by = sprintf(
                    'ORDER BY `%s` %s',
                    $sort_field,
                    $dir
                );
            }
        }

        if (isset($limit) && $limit > 0
            && isset($offset) && $offset >= 0
        ) {
            $pagination = sprintf(
                ' LIMIT %d OFFSET %d ',
                $limit,
                $offset
            );
        }

        $sql = sprintf(
            'SELECT %s
            FROM tcredential_store cs
            LEFT JOIN tgrupo tg
                ON tg.id_grupo = cs.id_group
            WHERE 1=1
            %s
            %s
            %s',
            join(',', $fields),
            join(' ', $sql_filters),
            $order_by,
            $pagination
        );

        if ($count) {
            $sql = sprintf('SELECT count(*) as n FROM ( %s ) tt', $sql);

            return db_get_value_sql($sql);
        }

        $return = db_get_all_rows_sql($sql);

        if ($return === false) {
            $return = [];
        }

        // Filter out those items of group all that cannot be edited by user.
        $return = array_filter(
            $return,
            function ($item) {
                if ($item['id_group'] == 0 && users_can_manage_group_all('AR') === false) {
                    return false;
                } else {
                    return true;
                }
            }
        );

        return $return;
    }


    /**
     * Retrieves target key from keystore or false in case of error.
     *
     * @param string $identifier Key identifier.
     *
     * @return array Key or false if error.
     */
    public static function getKey($identifier)
    {
        global $config;

        if (empty($identifier)) {
            return false;
        }

        $keys = self::getAll(
            [
                'cs.*',
                'tg.nombre as `group`',
            ],
            ['identifier' => $identifier]
        );

        if (is_array($keys) === true) {
            // Only 1 must exist.
            $key = $keys[0];

            // Decrypt content.
            $key['username'] = io_output_password($key['username']);
            $key['password'] = io_output_password($key['password']);
            $key['extra_1'] = io_output_password($key['extra_1']);
            $key['extra_2'] = io_output_password($key['extra_2']);

            return $key;
        }

        return false;
    }


    /**
     * Return all keys avaliable for current user.
     *
     * @param string $product Filter by product.
     *
     * @return array Keys or false if error.
     */
    public static function getKeys($product=false)
    {
        global $config;

        $filter = [];

        if ($product !== false) {
            $filter['product'] = $product;
        }

        $keys = self::getAll(
            [
                'cs.*',
                'tg.nombre as `group`',
            ],
            $filter
        );

        if (is_array($keys) === true) {
            // Improve usage and decode output.
            $return = array_reduce(
                $keys,
                function ($carry, $item) {
                    $item['username'] = io_output_password($item['username']);
                    $item['password'] = io_output_password($item['password']);
                    $item['extra_1'] = io_output_password($item['extra_1']);
                    $item['extra_2'] = io_output_password($item['extra_2']);
                    $carry[$item['identifier']] = $item['identifier'];
                    return $carry;
                },
                []
            );

            return $return;
        }

        return [];
    }


    /**
     * Ajax method invoked by datatables to draw content.
     *
     * @return void
     */
    public function draw()
    {
        global $config;

        // Datatables offset, limit and order.
        $filter = get_parameter('filter', []);
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        if ((bool) users_is_admin() === false) {
            $all = users_can_manage_group_all('UM');

            $filter['group_list'] = array_keys(
                users_get_groups(
                    $config['id_user'],
                    'UM',
                    (bool) $all
                )
            );
        }

        try {
            ob_start();

            $fields = [
                'cs.*',
                'tg.nombre as `group`',
            ];

            // Retrieve data.
            $data = $this->getAll(
                // Fields.
                $fields,
                // Filter.
                $filter,
                // Offset.
                $start,
                // Limit.
                $length,
                // Order.
                $order['direction'],
                // Sort field.
                $order['field']
            );

            // Retrieve counter.
            $count = $this->getAll(
                'count',
                $filter
            );

            if ($data) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;
                        $tmp->username = io_output_password($tmp->username);

                        if (empty($tmp->group)) {
                            $tmp->group = __('All');
                        } else {
                            $tmp->group = io_safe_output($tmp->group);
                        }

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            // Datatables format: RecordsTotal && recordsfiltered.
            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }

        exit;
    }


    /**
     * Prints inputs for modal "Add key".
     *
     * @return void
     */
    public function loadModal()
    {
        $identifier = get_parameter('identifier', null);
        $key = self::getKey($identifier);

        echo $this->printInputs($key);
    }


    /**
     * Prepare variables received using form. AJAX environment only.
     *
     * @return array of values processed or false in case of error.
     */
    private function prepareKeyValues()
    {
        $identifier = get_parameter('identifier', null);
        $id_group = get_parameter('id_group', null);
        $product = get_parameter('product', null);
        $username = get_parameter('username', null);
        $password = get_parameter('password', null);
        $extra_1 = get_parameter('extra_1', null);
        $extra_2 = get_parameter('extra_2', null);

        if ($product === 'GOOGLE') {
            $google_creds = json_decode(io_safe_output($extra_1));

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->ajaxMsg(
                    'error',
                    __('Not a valid JSON: %s', json_last_error_msg())
                );
                exit;
            }

            $username = $google_creds->client_email;
            $password = $google_creds->private_key_id;
        }

        if ($product !== 'SNMP') {
            if (empty($identifier) === true) {
                $error = __('Key identifier is required');
            } else if ($id_group === null) {
                $error = __('You must select a group where store this key!');
            } else if (empty($product) === true) {
                $error = __('You must specify a product type');
            } else if (empty($username) === true || (empty($password) === true)) {
                $error = __('You must specify a username and/or password');
            } else if (evaluate_ascii_valid_string(io_safe_output($identifier)) === false) {
                $error = __('Identifier with forbidden characters. Check the documentation.');
            }

            if (isset($error) === true) {
                $this->ajaxMsg('error', $error);
                exit;
            }

            // Encrypt content (if needed).
            $values = [
                'identifier' => $identifier,
                'id_group'   => $id_group,
                'product'    => $product,
                'username'   => io_input_password(io_safe_output($username)),
                'password'   => io_input_password(io_safe_output($password)),
                'extra_1'    => io_input_password(io_safe_output($extra_1)),
                'extra_2'    => io_input_password(io_safe_output($extra_2)),
            ];
        } else {
            $values = [
                'identifier' => $identifier,
                'id_group'   => $id_group,
                'product'    => $product,
            ];

            $community = (string) get_parameter('community', '');
            $version = (string) get_parameter('version', '1');
            $extra_json = [
                'community' => $community,
                'version'   => $version,
            ];
            if ($version === '3') {
                $securityLevelV3 = (string) get_parameter('securityLevelV3', 'authNoPriv');
                $extra_json['securityLevelV3'] = $securityLevelV3;
                $authUserV3 = (string) get_parameter('authUserV3', '');
                $extra_json['authUserV3'] = $authUserV3;
                if ($securityLevelV3 === 'authNoPriv' || $securityLevelV3 === 'authPriv') {
                    $authUserV3 = (string) get_parameter('authUserV3', '');
                    $extra_json['authUserV3'] = $authUserV3;
                    $authMethodV3 = (string) get_parameter('authMethodV3', 'MD5');
                    $extra_json['authMethodV3'] = $authMethodV3;
                    $authPassV3 = (string) get_parameter('authPassV3', '');
                    $extra_json['authPassV3'] = $authPassV3;

                    if ($securityLevelV3 === 'authPriv') {
                        $privacyMethodV3 = (string) get_parameter('privacyMethodV3', 'AES');
                        $extra_json['privacyMethodV3'] = $privacyMethodV3;
                        $privacyPassV3 = (string) get_parameter('privacyPassV3', '');
                        $extra_json['privacyPassV3'] = $privacyPassV3;
                    }
                }
            }

            $values['extra_1'] = json_encode($extra_json);
        }

        // Spaces  are not allowed.
        $values['identifier'] = \io_safe_input(
            preg_replace(
                '/\s+/',
                '-',
                trim(
                    \io_safe_output($identifier)
                )
            )
        );
        return $values;
    }


    /**
     * Stores a key into credential store.
     *
     * @param array  $values     Key definition.
     * @param string $identifier Update or create.
     *
     * @return boolean True if ok, false if not ok.
     */
    private function storeKey($values, $identifier=false)
    {
        if ($identifier === false) {
            // New.
            return db_process_sql_insert('tcredential_store', $values);
        } else {
            // Update.
            return db_process_sql_update(
                'tcredential_store',
                $values,
                ['identifier' => $identifier]
            );
        }

    }


    /**
     * Add a new key into Credential Store
     *
     * @return void
     */
    public function addKey()
    {
        global $config;

        $values = $this->prepareKeyValues();

        if ($this->storeKey($values) === false) {
            $this->ajaxMsg('error', $config['dbconnection']->error);
        } else {
            $this->ajaxMsg('result', $values['identifier']);
        }

        exit;
    }


    /**
     * Add a new key into Credential Store
     *
     * @return void
     */
    public function updateKey()
    {
        global $config;

        $values = $this->prepareKeyValues();
        $identifier = $values['identifier'];

        if ($this->storeKey($values, $identifier) === false) {
            $this->ajaxMsg('error', $config['dbconnection']->error);
        } else {
            $this->ajaxMsg('result', $identifier);
        }

        exit;
    }


    /**
     * AJAX method. Delete key from keystore.
     *
     * @return void
     */
    public function deleteKey()
    {
        global $config;

        $identifier = get_parameter('identifier', null);

        if (empty($identifier)) {
            $this->ajaxMsg('error', __('identifier cannot be empty'), true);
        }

        if (self::getKey($identifier) === false) {
            // User has no grants to delete target key.
            $this->ajaxMsg('error', __('Not allowed'), true);
        }

        if (db_process_sql_delete(
            'tcredential_store',
            ['identifier' => $identifier]
        ) === false
        ) {
            $this->ajaxMsg('error', $config['dbconnection']->error, true);
        } else {
            $this->ajaxMsg('result', $identifier, true);
        }

    }


    /**
     * Run CredentialStore (main page).
     *
     * @return void
     */
    public function run()
    {
        global $config;

        // Require specific CSS and JS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        ui_require_css_file('credential_store');

        if (!isset($config['encryption_passphrase'])) {
            $url = 'https://pandorafms.com/docs/index.php?title=Pandora:Documentation_en:Password_Encryption';
            if ($config['language'] == 'es') {
                $url = 'https://pandorafms.com/docs/index.php?title=Pandora:Documentation_es:Cifrado_Contrase%C3%B1as';
            }

            ui_print_warning_message(
                __(
                    'Database encryption is not enabled. Credentials will be stored in plaintext. %s',
                    '<a target="_new" href="'.$url.'">'.__('How to configure encryption.').'</a>'
                )
            );
        }

        // Datatables list.
        try {
            $columns = [
                'group',
                'identifier',
                'product',
                'username',
                'options',
            ];

            $column_names = [
                __('Group'),
                __('Identifier'),
                __('Product'),
                __('User'),
                [
                    'text'  => __('Options'),
                    'class' => 'table_action_buttons',
                ],
            ];

            $this->tableId = 'keystore';
            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => $this->tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => ['method' => 'draw'],
                    'ajax_postprocess'    => 'process_datatables_item(item)',
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'identifier',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                    'form'                => [
                        'inputs' => [
                            [
                                'label'     => __('Group'),
                                'type'      => 'select_groups',
                                'id'        => 'filter_id_group',
                                'name'      => 'filter_id_group',
                                'privilege' => 'AR',
                                'type'      => 'select_groups',
                                'nothing'   => false,
                                'selected'  => (defined($id_group_filter) ? $id_group_filter : 0),
                                'return'    => true,
                                'size'      => '80%',
                            ],
                            [
                                'label' => __('Free search'),
                                'type'  => 'text',
                                'class' => 'mw250px',
                                'id'    => 'free_search',
                                'name'  => 'free_search',
                            ],
                        ],
                    ],
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // Auxiliar div.
        $modal = '<div id="modal" class="invisible"></div>';
        $msg = '<div id="msg"     class="invisible"></div>';
        $aux = '<div id="aux"     class="invisible"></div>';

        echo $modal.$msg.$aux;

        // Create button.
        html_print_action_buttons(
            html_print_submit_button(
                __('Add key'),
                'create',
                false,
                ['icon' => 'next'],
                true
            )
        );

        echo $this->loadJS();

    }


    /**
     * Generates inputs for new/update forms.
     *
     * @param array $values Values or null.
     *
     * @return string Inputs.
     */
    public function printInputs($values=null)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $return_all_group = false;

        if (users_can_manage_group_all('AR') === true) {
            $return_all_group = true;
        }

        $form = [
            'action'   => '#',
            'id'       => 'modal_form',
            'onsubmit' => 'return false;',
            'class'    => 'modal',
            'extra'    => 'autocomplete="new-password"',
        ];

        $inputs = [];

        $inputs[] = [
            'label'     => __('Identifier'),
            'id'        => 'div-identifier',
            'arguments' => [
                'name'     => 'identifier',
                'type'     => 'text',
                'value'    => $values['identifier'],
                'disabled' => (bool) $values['identifier'],
                'return'   => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Group'),
            'arguments' => [
                'name'           => 'id_group',
                'id'             => 'id_group',
                'input_class'    => 'flex-row',
                'type'           => 'select_groups',
                'returnAllGroup' => $return_all_group,
                'selected'       => $values['id_group'],
                'return'         => true,
                'class'          => 'w50p',
            ],
        ];

        $inputs[] = [
            'label'     => __('Product'),
            'id'        => 'div-product',
            'arguments' => [
                'name'        => 'product',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'script'      => 'calculate_inputs()',
                'fields'      => [
                    'CUSTOM' => __('Custom'),
                    'AWS'    => __('Aws'),
                    'AZURE'  => __('Azure'),
                    'SAP'    => __('SAP'),
                    'GOOGLE' => __('Google'),
                    'WMI'    => __('WMI'),
                    'SNMP'   => __('SNMP'),
                ],
                'selected'    => (isset($values['product']) ? $values['product'] : 'CUSTOM'),
                'disabled'    => (bool) $values['product'],
                'return'      => true,
            ],
        ];

        $user_label = __('Username');
        $pass_label = __('Password');
        $extra_1_label = __('Extra');
        $extra_2_label = __('Extra (2)');
        $extra1_type = 'text';
        $user = true;
        $pass = true;
        $extra1 = true;
        $extra2 = true;

        // Remember to update credential_store.php also.
        switch ($values['product']) {
            case 'AWS':
                $user_label = __('Access key ID');
                $pass_label = __('Secret access key');
                $extra1 = false;
                $extra2 = false;
            break;

            case 'AZURE':
                $user_label = __('Account ID');
                $pass_label = __('Application secret');
                $extra_1_label = __('Tenant or domain name');
                $extra_2_label = __('Subscription id');
            break;

            case 'GOOGLE':
                $extra_1_label = __('Auth JSON');
                $user = false;
                $pass = false;
                $extra1 = true;
                $extra2 = false;
                $extra1_type = 'textarea';
            break;

            case 'WMI':
                $extra_1_label = __('Namespace');
                $extra1 = true;
                $extra2 = false;
            break;

            case 'SNMP':
                $user = false;
                $pass = false;
                $extra1 = false;
                $extra2 = false;
            break;

            case 'CUSTOM':
            case 'SAP':
                $user_label = __('Account ID');
                $pass_label = __('Password');
                $extra1 = false;
                $extra2 = false;
            default:
                // Use defaults.
            break;
        }

        if ($user) {
            $inputs[] = [
                'label'     => $user_label,
                'id'        => 'div-username',
                'arguments' => [
                    'name'        => 'username',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'value'       => $values['username'],
                    'return'      => true,
                ],
            ];
        }

        if ($pass) {
            $inputs[] = [
                'label'     => $pass_label,
                'id'        => 'div-password',
                'arguments' => [
                    'name'        => 'password',
                    'input_class' => 'flex-row',
                    'type'        => 'password',
                    'value'       => $values['password'],
                    'return'      => true,
                ],
            ];
        }

        if ($extra1) {
            $inputs[] = [
                'label'     => $extra_1_label,
                'id'        => 'div-extra_1',
                'arguments' => [
                    'name'        => 'extra_1',
                    'id'          => 'text-extra_1',
                    'input_class' => 'flex-row',
                    'type'        => $extra1_type,
                    'value'       => $values['extra_1'],
                    'return'      => true,
                ],
            ];
        }

        if ($extra2) {
            $inputs[] = [
                'label'     => $extra_2_label,
                'id'        => 'div-extra_2',
                'arguments' => [
                    'name'        => 'extra_2',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'value'       => $values['extra_2'],
                    'return'      => true,
                    'display'     => $extra2,
                ],

            ];
        }

        if ($values['product'] === 'SNMP') {
            $json_values = json_decode($values['extra_1'], true);
            $inputs[] = [
                'label'     => __('SNMP community'),
                'id'        => 'li_snmp_1',
                'arguments' => [
                    'name'        => 'community',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'value'       => $json_values['community'],
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('SNMP version'),
                'id'        => 'li_snmp_2',
                'arguments' => [
                    'name'        => 'version',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'script'      => 'showVersion()',
                    'fields'      => [
                        '1'  => __('1'),
                        '2'  => __('2'),
                        '2c' => __('2c'),
                        '3'  => __('3'),
                    ],
                    'selected'    => (isset($json_values['version']) ? $json_values['version'] : '1'),
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Security level'),
                'id'        => 'li_snmp_3',
                'style'     => ($json_values['version'] !== '3') ? 'display: none;' : '',
                'arguments' => [
                    'name'        => 'securityLevelV3',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'script'      => 'showSecurity()',
                    'fields'      => [
                        'authNoPriv'   => __('Authenticated and non-private method'),
                        'authPriv'     => __('Authenticated and private method'),
                        'noAuthNoPriv' => __('Non-authenticated and non-private method'),
                    ],
                    'selected'    => (isset($json_values['securityLevelV3']) ? $json_values['securityLevelV3'] : 'authNoPriv'),
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('User authentication'),
                'id'        => 'li_snmp_4',
                'style'     => ($json_values['version'] !== '3') ? 'display: none;' : '',
                'arguments' => [
                    'name'        => 'authUserV3',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'value'       => $json_values['authUserV3'],
                    'return'      => true,
                ],
            ];

            $authNoPrivate = (
                isset($json_values['securityLevelV3']) &&
                ($json_values['securityLevelV3'] === 'authNoPriv' || $json_values['securityLevelV3'] === 'authPriv')
            ) ? '' : 'display: none;';

            $inputs[] = [
                'label'     => __('Authentication method'),
                'id'        => 'li_snmp_5',
                'style'     => $authNoPrivate,
                'arguments' => [
                    'name'        => 'authMethodV3',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'fields'      => [
                        'MD5' => __('MD5'),
                        'SHA' => __('SHA'),
                    ],
                    'selected'    => (isset($json_values['authMethodV3']) ? $json_values['authMethodV3'] : 'MD5'),
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Password authentication'),
                'id'        => 'li_snmp_6',
                'style'     => $authNoPrivate,
                'arguments' => [
                    'name'        => 'authPassV3',
                    'input_class' => 'flex-row',
                    'type'        => 'password',
                    'value'       => $json_values['authPassV3'],
                    'return'      => true,
                ],
            ];

            $authPrivate = (isset($json_values['securityLevelV3']) && $json_values['securityLevelV3'] === 'authPriv')
                ? ''
                : 'display: none;';

            $inputs[] = [
                'label'     => __('Privacy method'),
                'id'        => 'li_snmp_7',
                'style'     => $authPrivate,
                'arguments' => [
                    'name'        => 'privacyMethodV3',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'fields'      => [
                        'AES' => __('AES'),
                        'DES' => __('DES'),
                    ],
                    'selected'    => (isset($json_values['privacyMethodV3']) ? $json_values['privacyMethodV3'] : 'AES'),
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Privacy pass'),
                'id'        => 'li_snmp_8',
                'style'     => $authPrivate,
                'arguments' => [
                    'name'        => 'privacyPassV3',
                    'input_class' => 'flex-row',
                    'type'        => 'password',
                    'value'       => $json_values['privacyPassV3'],
                    'return'      => true,
                ],
            ];
        }

        return $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );
    }


    /**
     * Loads JS content.
     *
     * @return string JS content.
     */
    public function loadJS()
    {
        ob_start();

        // Javascript content.
        ?>
    <script type="text/javascript">
        /**
        * Process datatable item before draw it.
        */
        function process_datatables_item(item) {

            id = item.identifier;

            idrow = '<b><a href="javascript:" onclick="show_form(\'';
            idrow += item.identifier;
            idrow += '\')" >'+item.identifier+'</a></b>';
            item.identifier = idrow;

            item.options = '<div class="table_action_buttons">';
            item.options += '<a href="javascript:" onclick="show_form(\'';
            item.options += id;
            item.options += '\')" ><?php echo html_print_image('images/edit.svg', true, ['title' => __('Edit'), 'class' => 'main_menu_icon invert_filter']); ?></a>';

            item.options += '<a href="javascript:" onclick="delete_key(\'';
            item.options += id;
            item.options += '\')" ><?php echo html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'main_menu_icon invert_filter']); ?></a>';
            item.options += '</div>';
        }

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
         * Handles inputs visibility based on selected product.
         */
        function calculate_inputs() {
            if ($('#product :selected').val() != "GOOGLE") {
                // Restore text-extra_1.
                var val = $('#text-extra_1').val();
                if(typeof val == 'undefined') {
                    val = '';
                }
                $('#text-extra_1').remove();
                $('#div-extra_1').append(
                    $('<input type="text" name="extra_1" id="text-extra_1" size="50" value="'+val+'"></input>')
                );
                hideSNMP();
            }

            if ($('#product :selected').val() == "CUSTOM") {
                $('#div-username label').text('<?php echo __('User'); ?>');
                $('#div-password label').text('<?php echo __('Password'); ?>');
                $('#div-username').show();
                $('#div-password').show();
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
                hideSNMP();
            } else if ($('#product :selected').val() == "AWS") {
                $('#div-username label').text('<?php echo __('Access key ID'); ?>');
                $('#div-password label').text('<?php echo __('Secret access key'); ?>');
                $('#div-username').show();
                $('#div-password').show();
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
                hideSNMP();
            } else if ($('#product :selected').val() == "AZURE") {
                $('#div-username label').text('<?php echo __('Client ID'); ?>');
                $('#div-password label').text('<?php echo __('Application secret'); ?>');
                $('#div-extra_1 label').text('<?php echo __('Tenant or domain name'); ?>');
                $('#div-extra_2 label').text('<?php echo __('Subscription id'); ?>');
                $('#div-username').show();
                $('#div-password').show();
                $('#div-extra_1').show();
                $('#div-extra_2').show();
                hideSNMP();
            } else if ($('#product :selected').val() == "SAP") {
                $('#div-username label').text('<?php echo __('Account ID.'); ?>');
                $('#div-password label').text('<?php echo __('Password'); ?>');
                $('#div-username').show();
                $('#div-password').show();
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
                hideSNMP();
            } else if ($('#product :selected').val() == "GOOGLE") {
                $('#div-username').hide();
                $('#div-password').hide();
                $('#div-extra_2').hide();
                $('#div-extra_1 label').text('<?php echo __('Auth JSON'); ?>');
                var val = $('#text-extra_1').val();
                if(typeof val == 'undefined') {
                    val = '';
                }

                $('#text-extra_1').remove();
                $('#div-extra_1').append(
                    $('<textarea name="extra_1" id="text-extra_1">'+val+'</textarea>')
                );
                $('#div-extra_1').show();
                hideSNMP();
            } else if ($('#product :selected').val() == "WMI") {
                $('#div-username label').text('<?php echo __('Username'); ?>');
                $('#div-password label').text('<?php echo __('Password'); ?>');
                $('#div-extra_1 label').text('<?php echo __('Namespace'); ?>');
                $('#div-username').show();
                $('#div-password').show();
                $('#div-extra_1').show();
                $('#div-extra_2').hide();
                hideSNMP();
            } else if ($('#product :selected').val() == "SNMP") {
                $('#div-username').hide();
                $('#div-password').hide();
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();

                $('#li_snmp_1').show();
                $('#li_snmp_2').show();

                if ($('#li_snmp_1').length > 0) {
                    //$('#version').val('1');
                    $('#version').trigger('change');
                } else {
                    const ul = $('#modal_form').children('ul')[0];

                    // SNMP community.
                    const li_community = document.createElement("li");
                    li_community.id = 'li_snmp_1';
                    const label_community = document.createElement("label");
                    label_community.textContent = '<?php echo __('SNMP community'); ?>';
                    const input_community = document.createElement("input");
                    input_community.type = 'text';
                    input_community.className = 'text_input';
                    input_community.name = 'community';
                    li_community.append(label_community);
                    li_community.append(input_community);
                    ul.append(li_community);

                    // SNMP version.
                    const li_version = document.createElement("li");
                    li_version.id = 'li_snmp_2';
                    const label_version = document.createElement("label");
                    label_version.textContent = '<?php echo __('SNMP version'); ?>';
                    const select_version = document.createElement("select");
                    select_version.name = 'version';
                    select_version.id = 'version';
                    select_version.onchange = function() {
                        showVersion();
                    };
                    let option1 = document.createElement("option");
                    let option2 = document.createElement("option");
                    let option2c = document.createElement("option");
                    let option3 = document.createElement("option");
                    option1.value = '1';
                    option1.text = '1';
                    option2.value = '2';
                    option2.text = '2';
                    option2c.value = '2c';
                    option2c.text = '2c';
                    option3.value = '3';
                    option3.text = '3';
                    select_version.appendChild(option1);
                    select_version.appendChild(option2);
                    select_version.appendChild(option2c);
                    select_version.appendChild(option3);
                    li_version.append(label_version);
                    li_version.append(select_version);
                    ul.append(li_version);
                    $("#version").select2();

                    // Security.
                    const li_security = document.createElement("li");
                    li_security.id = 'li_snmp_3';
                    const label_security = document.createElement("label");
                    label_security.textContent = '<?php echo __('Security level'); ?>';
                    const select_security = document.createElement("select");
                    select_security.name = 'securityLevelV3';
                    select_security.id = 'securityLevelV3';
                    select_security.onchange = function() {
                        showSecurity();
                    }
                    option1 = document.createElement("option");
                    option2 = document.createElement("option");
                    option3 = document.createElement("option");
                    option1.value = 'authNoPriv';
                    option1.text = '<?php echo __('Authenticated and non-private method'); ?>';
                    option2.value = 'authPriv';
                    option2.text = '<?php echo __('Authenticated and private method'); ?>';
                    option3.value = 'noAuthNoPriv';
                    option3.text = '<?php echo __('Non-authenticated and non-private method'); ?>';
                    select_security.appendChild(option1);
                    select_security.appendChild(option2);
                    select_security.appendChild(option3);
                    li_security.append(label_security);
                    li_security.append(select_security);
                    ul.append(li_security);
                    $("#securityLevelV3").select2();

                    // User.
                    const li_user = document.createElement("li");
                    li_user.id = 'li_snmp_4';
                    const label_user = document.createElement("label");
                    label_user.textContent = '<?php echo __('User authentication'); ?>';
                    const input_user = document.createElement("input");
                    input_user.type = 'text';
                    input_user.className = 'text_input';
                    input_user.name = 'authUserV3';
                    li_user.append(label_user);
                    li_user.append(input_user);
                    ul.append(li_user);

                    // Authentication method.
                    const li_method = document.createElement("li");
                    li_method.id = 'li_snmp_5';
                    const label_method = document.createElement("label");
                    label_method.textContent = '<?php echo __('Authentication method'); ?>';
                    const select_method = document.createElement("select");
                    select_method.name = 'authMethodV3';
                    select_method.id = 'method';
                    option1 = document.createElement("option");
                    option2 = document.createElement("option");
                    option1.value = 'MD5';
                    option1.text = '<?php echo __('MD5'); ?>';
                    option2.value = 'SHA';
                    option2.text = '<?php echo __('SHA'); ?>';
                    select_method.appendChild(option1);
                    select_method.appendChild(option2);
                    li_method.append(label_method);
                    li_method.append(select_method);
                    ul.append(li_method);
                    $("#method").select2();

                    // Password.
                    const li_password = document.createElement("li");
                    li_password.id = 'li_snmp_6';
                    const label_password = document.createElement("label");
                    label_password.textContent = '<?php echo __('Password authentication'); ?>';
                    const input_password = document.createElement("input");
                    input_password.type = 'password';
                    input_password.className = 'text_input';
                    input_password.name = 'authPassV3';
                    li_password.append(label_password);
                    li_password.append(input_password);
                    ul.append(li_password);

                    // Privacy method.
                    const li_privacy = document.createElement("li");
                    li_privacy.id = 'li_snmp_7';
                    const label_privacy = document.createElement("label");
                    label_privacy.textContent = '<?php echo __('Privacy method'); ?>';
                    const select_privacy = document.createElement("select");
                    select_privacy.name = 'privacyMethodV3';
                    select_privacy.id = 'privacy';
                    option1 = document.createElement("option");
                    option2 = document.createElement("option");
                    option1.value = 'AES';
                    option1.text = '<?php echo __('AES'); ?>';
                    option2.value = 'DES';
                    option2.text = '<?php echo __('DES'); ?>';
                    select_privacy.appendChild(option1);
                    select_privacy.appendChild(option2);
                    li_privacy.append(label_privacy);
                    li_privacy.append(select_privacy);
                    ul.append(li_privacy);
                    $("#privacy").select2();

                    // Privacy pass.
                    const li_privacyPassV3 = document.createElement("li");
                    li_privacyPassV3.id = 'li_snmp_8';
                    const label_privacyPassV3 = document.createElement("label");
                    label_privacyPassV3.textContent = '<?php echo __('Privacy pass'); ?>';
                    const input_privacyPassV3 = document.createElement("input");
                    input_privacyPassV3.type = 'password';
                    input_privacyPassV3.className = 'text_input';
                    input_privacyPassV3.name = 'privacyPassV3';
                    li_privacyPassV3.append(label_privacyPassV3);
                    li_privacyPassV3.append(input_privacyPassV3);
                    ul.append(li_privacyPassV3);

                    $('#li_snmp_3').hide();
                    $('#li_snmp_4').hide();
                    $('#li_snmp_5').hide();
                    $('#li_snmp_6').hide();
                    $('#li_snmp_7').hide();
                    $('#li_snmp_8').hide();
                }
            }
        }

        function showVersion() {
            if ($('#version').val() === '3') {
                $('#li_snmp_3').show();
                $('#li_snmp_4').show();
                $('#li_snmp_5').show();
                $('#li_snmp_6').show();
            } else {
                $('#li_snmp_3').hide();
                $('#li_snmp_4').hide();
                $('#li_snmp_5').hide();
                $('#li_snmp_6').hide();
                $('#li_snmp_7').hide();
                $('#li_snmp_8').hide();
            }
        }

        function showSecurity() {
            const value = $('#securityLevelV3').val();
            switch (value) {
                case 'noAuthNoPriv':
                    $('#li_snmp_4').show();
                    $('#li_snmp_5').hide();
                    $('#li_snmp_6').hide();
                    $('#li_snmp_7').hide();
                    $('#li_snmp_8').hide();
                break;

                case 'authPriv':
                    $('#li_snmp_4').show();
                    $('#li_snmp_5').show();
                    $('#li_snmp_6').show();
                    $('#li_snmp_7').show();
                    $('#li_snmp_8').show();
                break;

                case 'authNoPriv':
                default:
                    $('#li_snmp_4').show();
                    $('#li_snmp_5').show();
                    $('#li_snmp_6').show();
                    $('#li_snmp_7').hide();
                    $('#li_snmp_8').hide();
                break;
            }
        }

        function hideSNMP() {
            $('#li_snmp_1').hide();
            $('#li_snmp_2').hide();
            $('#li_snmp_3').hide();
            $('#li_snmp_4').hide();
            $('#li_snmp_5').hide();
            $('#li_snmp_6').hide();
            $('#li_snmp_7').hide();
            $('#li_snmp_8').hide();
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

        /**
         * Loads modal from AJAX to add a new key or edit an existing one.
         */
        function show_form(id) {
            var btn_ok_text = '<?php echo __('OK'); ?>';
            var btn_cancel_text = '<?php echo __('Cancel'); ?>';
            var title = '<?php echo __('Register new key into keystore'); ?>';
            var method = 'addKey';
            if(id) {
                btn_ok_text = '<?php echo __('Update'); ?>';
                title = "<?php echo __('Update key'); ?> "+id;
                method = 'updateKey';
            }

            load_modal({
                target: $('#modal'),
                form: 'modal_form',
                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                ajax_callback: showMsg,
                cleanup: cleanupDOM,
                modal: {
                    title: title,
                    ok: btn_ok_text,
                    cancel: btn_cancel_text,
                },
                extradata: [
                    {
                        name: 'identifier',
                        value: id,
                    }
                ],
                onload: function() {
                    calculate_inputs();
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

        /**
         * Delete selected key
         */
        function delete_key(id) {
            $('#aux').empty();
            $('#aux').text('<?php echo __('Are you sure?'); ?>');
            $('#aux').dialog({
                title: '<?php echo __('Delete'); ?> ' + id,
                buttons: [
                    {
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                        text: '<?php echo __('Cancel'); ?>',
                        click: function(e) {
                            $(this).dialog('close');
                            cleanupDOM();

                        }
                    },
                    {
                        text: 'Delete',
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                        click: function(e) {
                            $.ajax({
                                method: 'post',
                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                data: {
                                    page: 'godmode/groups/credential_store',
                                    method: 'deleteKey',
                                    identifier: id
                                },
                                datatype: "json",
                                success: function (data) {
                                    showMsg(data);
                                },
                                error: function(e) {
                                    showMsg(e);
                                }
                            });
                        }
                    }
                ]
            });
        }

        $(document).ready(function(){
            $("#button-create").on('click', function(){
                show_form();
            });
        });


    </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
