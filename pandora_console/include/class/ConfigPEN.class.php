<?php
/**
 * PEN Configuration feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Wizard Setup
 * @version    0.0.1
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/include/class/HTML.class.php';
/**
 * Config PEN Class
 */
class ConfigPEN extends HTML
{

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * URL Base
     *
     * @var string
     */
    private $baseUrl;


    /**
     * Contructor.
     *
     * @param string $ajax_page Target ajax page.
     */
    public function __construct($ajax_page)
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access PEN Definition feature'
            );

            include 'general/noaccess.php';
            exit;
        }

        $this->ajaxController = $ajax_page;
        $this->offset = '';
        $this->baseUrl = ui_get_full_url(
            'index.php?sec=configuration_wizard_setup&sec2=godmode/modules/private_enterprise_numbers'
        );

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
        $filter=null,
        $offset=null,
        $limit=null,
        $order=null,
        $sort_field=null
    ) {
        $sql_filters = [];
        $order_by = '';
        $pagination = '';

        $count = false;
        if (!is_array($fields) && $fields == 'count') {
            $fields = ['*'];
            $count = true;
        } else if (!is_array($fields)) {
            error_log('[configPEN.getAll] Fields must be an array or "count".');
            throw new Exception('[configPEN.getAll] Fields must be an array or "count".');
        }

        if (is_array($filter)) {
            if (!empty($filter['free_search'])) {
                $sql_filters[] = vsprintf(
                    ' AND (lower(`manufacturer`) like lower("%%%s%%")
                        OR pen = "%s") ',
                    array_fill(0, 2, $filter['free_search'])
                );
            }

            if (!empty($filter['pen'])) {
                $sql_filters[] = sprintf(
                    ' AND `pen` = %d',
                    $filter['pen']
                );
            }
        }

        if (isset($order)) {
            $dir = 'asc';
            if ($order == 'desc') {
                $dir = 'desc';
            };

            if (in_array(
                $sort_field,
                [
                    'pen',
                    'manufacturer',
                    'description',
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
            FROM `tpen`
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
     * AJAX: Return JSON content for datatable.
     *
     * @return void
     */
    function draw()
    {
        global $config;

        // Datatables offset, limit and order.
        $filter = get_parameter('filter', []);
        $start = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        try {
            ob_start();

            $fields = ['*'];

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

                        $tmp->description = io_safe_output($tmp->description);
                        $tmp->manufacturer = io_safe_output($tmp->manufacturer);

                        $tmp->options = '<div class="table_action_buttons float-right">';

                        $tmp->options .= '<a href="javascript:" onclick="showForm(\'';
                        $tmp->options .= $tmp->pen;
                        $tmp->options .= '\')" >';
                        $tmp->options .= html_print_image(
                            'images/edit.svg',
                            true,
                            [
                                'title' => __('Show'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $tmp->options .= '</a>';
                        $tmp->options .= '<a href="javascript:" onclick="deletePEN(\'';
                        $tmp->options .= $tmp->pen;
                        $tmp->options .= '\')" >';
                        $tmp->options .= html_print_image(
                            'images/delete.svg',
                            true,
                            [
                                'title' => __('Delete'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $tmp->options .= '</a>';

                        $tmp->options .= '</div>';

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
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // Require specific CSS and JS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        ui_require_css_file('pen');

        // Header section.
        // Breadcrums.
        $this->setBreadcrum([]);

        $this->prepareBreadcrum(
            [
                [
                    'link'     => '',
                    'label'    => __('Configuration'),
                    'selected' => false,
                ],
                [
                    'link'     => '',
                    'label'    => __('Templates'),
                    'selected' => false,
                ],
                [
                    'link'     => $this->baseUrl,
                    'label'    => __('Private Enterprise Numbers'),
                    'selected' => true,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Private Enterprise Numbers'),
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

        // Definition for AJAX.
        html_print_input_hidden(
            'ajax_file',
            ui_get_full_url('ajax.php', false, false, false)
        );

        // Ajax page (hidden).
        html_print_input_hidden(
            'ajax_page',
            $this->ajaxController
        );

        // Allow message area.
        html_print_div(['id' => 'message_show_area']);
        // Prints the main table.
        html_print_div(
            [
                'id'      => 'main_table_area',
                'content' => $this->createMainTable(),
            ]
        );
    }


    /**
     * Load modal information for PEN management.
     *
     * Ajax. Direct HTML.
     *
     * @return void
     */
    public function loadModal()
    {
        $values = [];
        $id = (int) get_parameter('pen', 0);
        if ($id > 0) {
            $values = $this->getAll(
                // Fields.
                ['*'],
                // Filter.
                ['pen' => $id]
            );
            if (is_array($values)) {
                $values = $values[0];
            }
        }

        $form = [
            'action'   => '#',
            'id'       => 'modal_form',
            'onsubmit' => 'return false;',
            'class'    => 'filter-list-adv',
        ];

        $inputs = [];

        $arguments = [
            'name'     => 'pen',
            'type'     => 'number',
            'value'    => $values['pen'],
            'required' => true,
            'return'   => true,
            'size'     => 50,
        ];

        if ((bool) $values['pen'] === true) {
            $arguments['disabled'] = true;
        }

        $inputs[] = [
            'label'     => __('PEN'),
            'class'     => 'flex-row',
            'id'        => 'div-pen',
            'arguments' => $arguments,
        ];

        $inputs[] = [
            'label'     => __('Manufacturer'),
            'class'     => 'flex-row',
            'arguments' => [
                'name'     => 'manufacturer',
                'id'       => 'manufacturer',
                'type'     => 'text',
                'required' => true,
                'value'    => io_safe_output($values['manufacturer']),
                'return'   => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Description'),
            'class'     => 'flex-row',
            'arguments' => [
                'name'    => 'description',
                'id'      => 'description',
                'type'    => 'textarea',
                'value'   => io_safe_output($values['description']),
                'return'  => true,
                'rows'    => 50,
                'columns' => 30,
            ],
        ];

        echo '<div id="div-form">';
        echo parent::printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );
        echo '</div>';
    }


    /**
     * Delete a manufacturer register from db.
     *
     * @return void
     */
    public function delete()
    {
        $pen = get_parameter('pen', 0);

        if (empty($pen)) {
            echo json_encode(['error' => __('PEN is required')]);
        } else {
            if (db_process_sql_delete('tpen', ['pen' => $pen]) !== false) {
                echo json_encode(['result' => __('Successfully deleted')]);
            } else {
                global $config;
                echo json_encode(['error' => $config['dbconnection']->error]);
            }
        }

    }


    /**
     * Add or update a manufacturer to private enterprise numbers.
     *
     * @return void
     */
    public function add()
    {
        $pen = get_parameter('pen', 0);
        $manufacturer = io_safe_input(strip_tags(io_safe_output((string) get_parameter('manufacturer'))));
        $description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
        $is_new = (bool) get_parameter('is_new', false);

        if (empty($pen)) {
            $error = __('PEN is required.');
        }

        if (empty($manufacturer)) {
            $error = __('Manufacturer is required');
        }

        if (!empty($error)) {
            echo json_encode(
                ['error' => $error]
            );
        }

        // Add if not exists.
        $current = $this->getAll(['pen'], ['pen' => $pen]);

        if ($current === false) {
            // New.
            if ($is_new === false) {
                echo json_encode(
                    [
                        'error' => __('This PEN definition does not exist'),
                    ]
                );
                exit;
            }

            $rs = db_process_sql_insert(
                'tpen',
                [
                    'pen'          => $pen,
                    'manufacturer' => $manufacturer,
                    'description'  => $description,
                ]
            );
            $str = __('created');
        } else {
            // Update.
            if ($is_new === true) {
                echo json_encode(
                    [
                        'error' => __('This PEN definition already exists'),
                    ]
                );
                exit;
            }

            $rs = db_process_sql_update(
                'tpen',
                [
                    'manufacturer' => $manufacturer,
                    'description'  => $description,
                ],
                ['pen' => $pen]
            );
            $str = __('updated');
        }

        if ($rs === false) {
            global $config;
            echo json_encode(['error' => $config['dbconnection']->error]);
        } else {
            echo json_encode(['result' => __('Succesfully %s', $str)]);
        }
    }


    /**
     * Create the main table with the PENs info
     *
     * @return string Return entire the table
     */
    public function createMainTable()
    {
        global $config;

        $output = '';

        // Datatables list.
        try {
            $columns = [
                'pen',
                'manufacturer',
                'description',
                'options',
            ];

            $column_names = [
                __('PEN'),
                __('Manufacturer'),
                __('Description'),
                [
                    'text'  => __('Options'),
                    'class' => 'table_action_buttons align_right',
                ],
            ];

            $tableId = 'keystore';
            // Load datatables user interface.
            $output .= ui_print_datatable(
                [
                    'id'                  => $tableId,
                    'return'              => true,
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => ['method' => 'draw'],
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'pen',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                    'form'                => [
                        'inputs' => [
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
        $output .= '<div id="modal" class="invisible"></div>';
        $output .= '<div id="msg"   class="invisible"></div>';
        $output .= '<div id="aux"   class="invisible"></div>';

        $output .= html_print_action_buttons(
            parent::printInput(
                [
                    'type'       => 'submit',
                    'name'       => 'create',
                    'label'      => __('Register manufacturer'),
                    'attributes' => ['icon' => 'next'],
                    'return'     => true,
                ]
            ),
            ['type' => 'form_action'],
            true
        );

        ob_start();
        ?>
    <script type="text/javascript">
function cleanupDOM() {
  $("#div-form").empty();
}

function deletePEN(id) {
  confirmDialog({
    title: "<?php echo __('Are you sure?'); ?>",
    message: "<?php echo __('Are you sure you want to delete this PEN?'); ?>",
    ok: "<?php echo __('OK'); ?>",
    cancel: "<?php echo __('Cancel'); ?>",
    onAccept: function() {
      $.ajax({
        method: "post",
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        data: {
          page: "<?php echo $this->ajaxController; ?>",
          method: "delete",
          pen: id
        },
        datatype: "json",
        success: function(data) {
          showMsg(data);
        },
        error: function(e) {
          showMsg(e);
        }
      });
    }
  });
}

function showForm(id) {
  var btn_ok_text = "<?php echo __('OK'); ?>";
  var btn_cancel_text = "<?php echo __('Cancel'); ?>";
  var title = "<?php echo __('Register new manufacturer'); ?>";
  var is_new = 1;
  if (id) {
    btn_ok_text = "<?php echo __('Update'); ?>";
    title = "<?php echo __('Update'); ?> " + id;
    is_new = 0;
  }
  load_modal({
    target: $("#modal"),
    form: "modal_form",
    url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
    ajax_callback: showMsg,
    cleanup: cleanupDOM,
    modal: {
      title: title,
      ok: btn_ok_text,
      cancel: btn_cancel_text
    },
    extradata: [
      {
        name: "pen",
        value: id
      },
      {
        name: 'is_new',
        value: is_new
      }
    ],
    onshow: {
      page: "<?php echo $this->ajaxController; ?>",
      method: "loadModal"
    },
    onsubmit: {
      page: "<?php echo $this->ajaxController; ?>",
      method: "add"
    }
  });
}

/**
 * Process ajax responses and shows a dialog with results.
 */
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
    buttons: [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
        text: "OK",
        click: function(e) {
          if (!failed) {
            $(".ui-dialog-content").dialog("close");
            $(".info").hide();
            cleanupDOM();
            dt_keystore.draw(false);
          } else {
            $(this).dialog("close");
          }
        }
      }
    ]
  });
}

$(document).ready(function() {
  $("#button-create").click(function() {
    showForm();
  });
});

    </script>
        <?php
        $output .= ob_get_clean();

        return $output;
    }


}
