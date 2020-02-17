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
        $msg_err = 'Failed while saving: %s';
        $msg_ok = 'Successfully saved into keystore ';

        if ($delete) {
            $msg_err = 'Failed while removing: %s';
            $msg_ok = 'Successfully deleted ';
        }

        if ($type == 'error') {
            echo json_encode(
                [
                    $type => ui_print_error_message(
                        __(
                            $msg_err,
                            $msg
                        ),
                        '',
                        true
                    ),
                ]
            );
        } else {
            echo json_encode(
                [
                    $type => ui_print_success_message(
                        __(
                            $msg_ok,
                            $msg
                        ),
                        '',
                        true
                    ),
                ]
            );
        }

        exit;
    }


    /**
     * Initializes object and validates user access.
     *
     * @param string $ajax_controller Path of ajaxController, is the 'page'
     *                               variable sent in ajax calls.
     *
     * @return Object
     */
    public function __construct($ajax_controller)
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
                $childrens = groups_get_childrens($id_group, null, true);
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

        return db_get_all_rows_sql($sql);
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
                    $carry[$item['identifier']] = $item['identifier'];
                    return $carry;
                }
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
        // Datatables offset, limit and order.
        $filter = get_parameter('filter', []);
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
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

        if (empty($identifier)) {
            $error = __('Key identifier is required');
        } else if ($id_group === null) {
            $error = __('You must select a group where store this key!');
        } else if (empty($product)) {
            $error = __('You must specify a product type');
        } else if (empty($username) && (empty($password))) {
            $error = __('You must specify a username and/or password');
        }

        // Encrypt content (if needed).
        $values = [
            'identifier' => $identifier,
            'id_group'   => $id_group,
            'product'    => $product,
            'username'   => io_input_password($username),
            'password'   => io_input_password($password),
            'extra_1'    => $extra_1,
            'extra_2'    => $extra_2,
        ];

        // Spaces  are not allowed.
        $values['identifier'] = preg_replace('/\s+/', '-', trim($identifier));

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
                    'class' => 'action_buttons',
                ],
            ];

            $this->tableId = 'keystore';
            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => $this->tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
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
                    'form'                => [
                        'inputs' => [
                            [
                                'label'   => __('Group'),
                                'type'    => 'select',
                                'id'      => 'filter_id_group',
                                'name'    => 'filter_id_group',
                                'options' => users_get_groups_for_select(
                                    $config['id_user'],
                                    'AR',
                                    true,
                                    true,
                                    false
                                ),
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
        $modal = '<div id="modal" style="display: none"></div>';
        $msg = '<div id="msg" style="display: none"></div>';
        $aux = '<div id="aux" style="display: none"></div>';

        echo $modal.$msg.$aux;

        // Create button.
        echo '<div class="w100p flex-content-right">';
        html_print_submit_button(
            __('Add key'),
            'create',
            false,
            'class="sub next"'
        );
        echo '</div>';

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
                'name'        => 'id_group',
                'id'          => 'id_group',
                'input_class' => 'flex-row',
                'type'        => 'select_groups',
                'selected'    => $values['id_group'],
                'return'      => true,
                'class'       => 'w50p',
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
                // 'GOOGLE' => __('Google'),
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
                // Need further investigation.
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

        if ($extra1) {
            $inputs[] = [
                'label'     => $extra_1_label,
                'id'        => 'div-extra_1',
                'arguments' => [
                    'name'        => 'extra_1',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
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

            item.options = '<a href="javascript:" onclick="show_form(\'';
            item.options += id;
            item.options += '\')" ><?php echo html_print_image('images/eye.png', true, ['title' => __('Show')]); ?></a>';

            item.options += '<a href="javascript:" onclick="delete_key(\'';
            item.options += id;
            item.options += '\')" ><?php echo html_print_image('images/cross.png', true, ['title' => __('Delete')]); ?></a>';
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
            if ($('#product :selected').val() == "CUSTOM") {
                $('#div-username label').text('<?php echo __('User'); ?>');
                $('#div-password label').text('<?php echo __('Password'); ?>');
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
            } else if ($('#product :selected').val() == "AWS") {
                $('#div-username label').text('<?php echo __('Access key ID'); ?>');
                $('#div-password label').text('<?php echo __('Secret access key'); ?>');
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
            } else if ($('#product :selected').val() == "AZURE") {
                $('#div-username label').text('<?php echo __('Client ID'); ?>');
                $('#div-password label').text('<?php echo __('Application secret'); ?>');
                $('#div-extra_1 label').text('<?php echo __('Tenant or domain name'); ?>');
                $('#div-extra_2 label').text('<?php echo __('Subscription id'); ?>');
                $('#div-extra_1').show();
                $('#div-extra_2').show();
            } else if ($('#product :selected').val() == "SAP") {
                $('#div-username label').text('<?php echo __('Account ID.'); ?>');
                $('#div-password label').text('<?php echo __('Password'); ?>');
                $('#div-extra_1').hide();
                $('#div-extra_2').hide();
            }
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

            $("#submit-create").on('click', function(){
                show_form();
            });
        });


    </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
