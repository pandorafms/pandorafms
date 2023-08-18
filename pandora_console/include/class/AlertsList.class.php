<?php
/**
 * Pending lerts list class
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Alerts list
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

global $config;

/**
 * Provides functionality for pending alerts list.
 */
class AlertsList
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
        'loadModal',
        'drawTable',
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

        if ((bool) check_acl($config['id_user'], 0, 'LM') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access pending alerts list'
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
     * Prints inputs for modal "Pending alerts list".
     *
     * @return void
     */
    public function loadModal()
    {
        ob_start();
        echo '<div id="pending_alerts_modal">';
        echo $this->getModalContent();
        echo '</div>';
        echo ob_get_clean();
    }


    /**
     * Run.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        ui_require_css_file('tables');

        if ((bool) check_acl($config['id_user'], 0, 'LM') === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access pending alerts list.'
            );
            include 'general/noaccess.php';
            return;
        }

        // Auxiliar div for modal.
        echo '<div id="alerts_list_modal" class="invisible"></div>';

        echo $this->loadJS();
    }


    /**
     * Draw table.
     *
     * @return void
     */
    public function drawTable()
    {
        global $config;

        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);

        try {
            ob_start();

            $order_by_clause = '';

            if (in_array($order['field'], ['agentAlias', 'moduleName', 'alertType']) === false) {
                $order_by_clause = 'ORDER BY id '.$order['direction'];
            }

            if ($length !== '-1') {
                $sql = sprintf(
                    'SELECT *
                    FROM talert_execution_queue %s
                    LIMIT %d, %d',
                    $order_by_clause,
                    $start,
                    $length
                );
            } else {
                $sql = sprintf(
                    'SELECT * FROM talert_execution_queue %s',
                    $order_by_clause
                );
            }

            // Retrieve data and count.
            $data = db_get_all_rows_sql($sql);
            $count = (int) db_get_sql('SELECT COUNT(*) FROM talert_execution_queue');

            if ($data) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        // Check if the item is an array before proceeding.
                        if (is_array($item) === true) {
                            // Transforms array of arrays $data into an array
                            // of objects, making a post-process of certain fields.
                            $tmp = (object) $item;
                            $decoded_data = base64_decode($tmp->data);
                            $decoded_data = json_decode($decoded_data, true);

                            if (is_array($decoded_data) === true) {
                                // Access the second element of $decoded_data (index 1) to get 'alias' and 'type'.
                                $tmp->agentAlias = isset($decoded_data[1]['alias']) ? $decoded_data[1]['alias'] : null;
                                $tmp->alertType = isset($decoded_data[3]['type']) ? $decoded_data[3]['type'] : null;
                                // Access the third element of $decoded_data (index 2) to get 'nombre'.
                                $tmp->moduleName = isset($decoded_data[2]['nombre']) ? $decoded_data[2]['nombre'] : null;

                                $carry[] = $tmp;
                            }
                        }

                        return $carry;
                    }
                );
            }

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
     * Generates content of modal.
     *
     * @return string Modal content.
     */
    public function getModalContent()
    {
        global $config;

        ob_start();

        try {
            $columns = [
                'id',
                'agentAlias',
                'moduleName',
                'alertType',
            ];

            $column_names = [
                __('ID'),
                __('Agent'),
                __('Module'),
                __('Type'),
            ];

            $this->tableId = 'pending_alerts';
            ui_print_datatable(
                [
                    'id'                  => $this->tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'default_pagination'  => 7,
                    'dom_elements'        => 'pfti',
                    'ajax_data'           => ['method' => 'drawTable'],
                    'no_sortable_columns' => [
                        1,
                        2,
                        3,
                    ],
                    'order'               => [
                        'field'     => 'id',
                        'direction' => 'asc',
                    ],
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

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


            /**
             * Loads modal from AJAX.
             */
            function show_agent_install_modal() {
                var btn_close_text = '<?php echo __('Close'); ?>';
                var title = '<?php echo __('Alerts pending to be executed'); ?>';

                load_modal({
                    target: $('#alerts_list_modal'),
                    form: 'modal_form',
                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                    ajax_callback: showMsg,
                    cleanup: cleanupDOM,
                    modal: {
                        title: title,
                        cancel: btn_close_text,
                    },
                    extradata: [
                        {
                            name: 'identifier'
                        }
                    ],
                    onshow: {
                        page: '<?php echo $this->ajaxController; ?>',
                        method: 'loadModal'
                    },
                    onload: function() {
                        $('#pending_alerts_paginate').css('margin-bottom','15px');
                    },
                });
            }

            $(document).ready(function() {
                var page = 0;

                $("#button-modal_pending_alerts").on('click', function() {
                    show_agent_install_modal();
                });

                const alertsListBtn = document.querySelectorAll('.open-alerts-list-modal');

                alertsListBtn.forEach(link => {
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        show_agent_install_modal();
                    });
                });
            });
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
