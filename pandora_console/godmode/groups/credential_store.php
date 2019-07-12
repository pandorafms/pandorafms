<?php
/**
 * Credentials management view.
 *
 * @category   Credentials management
 * @package    Pandora FMS
 * @subpackage Opensource
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

// Check access.
check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access event viewer'
    );

    if (is_ajax()) {
        return ['error' => 'noaccess'];
    }

    include 'general/noaccess.php';
    return;
}

// Required files.
ui_require_css_file('credential_store');
require_once $config['homedir'].'/include/functions_credential_store.php';
require_once $config['homedir'].'/include/functions_io.php';

if (is_ajax()) {
    $draw = get_parameter('draw', 0);
    $filter = get_parameter('filter', []);
    $get_key = get_parameter('get_key', 0);
    $new_form = get_parameter('new_form', 0);
    $new_key = get_parameter('new_key', 0);
    $update_key = get_parameter('update_key', 0);
    $delete_key = get_parameter('delete_key', 0);

    if ($new_form) {
        echo print_inputs();
        exit;
    }

    if ($delete_key) {
        $identifier = get_parameter('identifier', null);

        if (empty($identifier)) {
            ajax_msg('error', __('identifier cannot be empty'));
        }

        if (db_process_sql_delete(
            'tcredential_store',
            ['identifier' => $identifier]
        ) === false
        ) {
            ajax_msg('error', $config['dbconnection']->error, true);
        } else {
            ajax_msg('result', $identifier, true);
        }
    }

    if ($update_key) {
        $data = get_parameter('values', null);

        if ($data === null || !is_array($data)) {
            echo json_encode(['error' => __('Invalid parameters, please retry')]);
            exit;
        }

        $values = [];
        foreach ($data as $key => $value) {
            if ($key == 'identifier') {
                $identifier = base64_decode($value);
            } else if ($key == 'product') {
                $product = base64_decode($value);
            } else {
                $values[$key] = base64_decode($value);
            }
        }

        if (empty($identifier)) {
            ajax_msg('error', __('identifier cannot be empty'));
        }

        if (empty($product)) {
            ajax_msg('error', __('product cannot be empty'));
        }

        if (db_process_sql_update(
            'tcredential_store',
            $values,
            ['identifier' => $identifier]
        ) === false
        ) {
            ajax_msg('error', $config['dbconnection']->error);
        } else {
            ajax_msg('result', $identifier);
        }

        exit;
    }

    if ($new_key) {
        $data = get_parameter('values', null);

        if ($data === null || !is_array($data)) {
            echo json_encode(['error' => __('Invalid parameters, please retry')]);
            exit;
        }

        $values = [];
        foreach ($data as $key => $value) {
            $values[$key] = base64_decode($value);
            if ($key == 'identifier') {
                $values[$key] = preg_replace('/\s+/', '-', trim($values[$key]));
            }
        }

        $identifier = $values['identifier'];

        if (empty($identifier)) {
            ajax_msg('error', __('identifier cannot be empty'));
        }

        if (empty($values['product'])) {
            ajax_msg('error', __('product cannot be empty'));
        }

        if (db_process_sql_insert('tcredential_store', $values) === false) {
            ajax_msg('error', $config['dbconnection']->error);
        } else {
            ajax_msg('result', $identifier);
        }

        exit;
    }

    if ($get_key) {
        $identifier = get_parameter('identifier', null);

        $key = get_key($identifier);
        echo print_inputs($key);

        exit;
    }

    if ($draw) {
        // Datatables offset, limit and order.
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
            $data = credentials_get_all(
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
            $count = credentials_get_all(
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
                        $tmp->username = io_safe_output($tmp->username);

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
            return json_encode(['error' => $e->getMessage()]);
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

    exit;
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

    $table_id = 'keystore';
    // Load datatables user interface.
    ui_print_datatable(
        [
            'id'                  => $table_id,
            'class'               => 'info_table',
            'style'               => 'width: 100%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'ajax_url'            => 'godmode/groups/credential_store',
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
$new = '<div id="new_key" style="display: none"><form id="form_new">';
$new .= '</form></div>';
$details = '<div id="info_key" style="display: none"><form id="form_update">';
$details .= '</form></div>';
$aux = '<div id="aux" style="display: none"></div>';


echo $new.$details.$aux;

// Create button.
echo '<div class="w100p flex-content-right">';
html_print_submit_button(
    __('Add key'),
    'create',
    false,
    'class="sub next"'
);
echo '</div>';

?>

<script type="text/javascript">
    function process_datatables_item(item) {
        item.options = '<a href="javascript:" onclick="display_key(\'';
        item.options += item.identifier;
        item.options += '\')" ><?php echo html_print_image('images/eye.png', true, ['title' => __('Show')]); ?></a>';

        item.options += '<a href="javascript:" onclick="delete_key(\'';
        item.options += item.identifier;
        item.options += '\')" ><?php echo html_print_image('images/cross.png', true, ['title' => __('Delete')]); ?></a>';
    }

    function handle_response(data) {
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

        $('#aux').empty();
        $('#aux').html(text);
        $('#aux').dialog({
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
                    text: 'OK',
                    click: function(e) {
                        if (!failed) {
                            dt_<?php echo $table_id; ?>.draw(0);
                            $(".ui-dialog-content").dialog("close");
                            cleanupDOM();
                        } else {
                            $(this).dialog('close');
                        }
                    }
                }
            ]
        });
    }
    
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
                                delete_key: 1,
                                identifier: id
                            },
                            datatype: "json",
                            success: function (data) {
                                handle_response(data);
                            },
                            error: function(e) {
                                handle_response(e);
                            }
                        });
                    }
                }
            ]
        });
    }

    function display_key(id) {
        $('#form_update').empty();
        $('#form_update').html('Loading...');
        $.ajax({
            method: 'post',
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            data: {
                page: 'godmode/groups/credential_store',
                get_key: 1,
                identifier: id
            },
            success: function (data) {
                $('#info_key').dialog({
                    width: 580,
                    height: 400,
                    position: {
                        my: 'center',
                        at: 'center',
                        of: window,
                        collision: 'fit'
                    },
                    title: id,
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
                            text: 'Update',
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                            click: function(e) {
                                var values = {};

                                $('#form_update :input').each(function() {
                                    values[this.name] = btoa($(this).val());
                                });

                                $.ajax({
                                    method: 'post',
                                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                    data: {
                                        page: 'godmode/groups/credential_store',
                                        update_key: 1,
                                        values: values
                                    },
                                    datatype: "json",
                                    success: function (data) {
                                        handle_response(data);
                                    },
                                    error: function(e) {
                                        handle_response(e);
                                    }
                                });
                            }
                        }
                    ]
                });
                $('#form_update').html(data);
            }

        })
    }

    function cleanupDOM() {
        $('#div-identifier').empty();
        $('#div-product').empty();
        $('#div-username').empty();
        $('#div-password').empty();
        $('#div-extra_1').empty();
        $('#div-extra_2').empty();
    }

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
        } 
    }

    function add_key() {
        // Clear form.
        $('#form_update').empty();
        $('#form_update').html('Loading...');
        $.ajax({
            method: 'post',
            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
            data: {
                page: 'godmode/groups/credential_store',
                new_form: 1
            },
            success: function(data) {
                $('#form_new').html(data);
                $('#id_group').val(0);
                // By default AWS.
                $('#product').val('AWS');
                calculate_inputs();

                $('#product').on('change', function() {
                    calculate_inputs()
                });

                // Show form.
                $('#new_key').dialog({
                    width: 580,
                    height: 400,
                    position: {
                        my: 'center',
                        at: 'center',
                        of: window,
                        collision: 'fit'
                    },
                    title: "<?php echo __('Register new key into keystore'); ?>",
                    buttons: [
                        {
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                            text: "<?php echo __('Cancel'); ?>",
                            click: function(e) {
                                $(this).dialog('close');
                                cleanupDOM();
                            }
                        },
                        {
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                            text: 'OK',
                            click: function(e) {
                                var values = {};

                                console.log($('#form_new'));

                                $('#form_new :input').each(function() {
                                    values[this.name] = btoa($(this).val());
                                });

                                $.ajax({
                                    method: 'post',
                                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                    data: {
                                        page: 'godmode/groups/credential_store',
                                        new_key: 1,
                                        values: values
                                    },
                                    datatype: "json",
                                    success: function (data) {
                                        handle_response(data);
                                    },
                                    error: function(e) {
                                        handle_response(e);
                                    }
                                });
                            }
                        },
                    ]
                });
            }
        })


    }
    $(document).ready(function(){

        $("#submit-create").on('click', function(){
            add_key();
        });
    });

</script>
