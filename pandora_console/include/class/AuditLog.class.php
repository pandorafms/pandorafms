<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Controller for Audit Logs
 *
 * @category   Controller
 * @package    Pandora FMS
 * @subpackage Community
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
// Begin.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
enterprise_include_once('godmode/admin_access_logs.php');

/**
 * Class AuditLog
 */
class AuditLog extends HTML
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = ['draw'];

    /**
     * Ajax page.
     *
     * @var string
     */
    private $ajaxController;


    /**
     * Class constructor
     *
     * @param string $ajaxController Ajax controller.
     */
    public function __construct(string $ajaxController)
    {
        global $config;

        check_login();

        if (check_acl($config['id_user'], 0, 'PM') === false
            && is_user_admin($config['id_user']) === true
        ) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Audit Logs'
            );
            include 'general/noaccess.php';
            return;
        }

        // Set the ajax controller.
        $this->ajaxController = $ajaxController;
    }


    /**
     * Run view
     *
     * @return void
     */
    public function run()
    {
        // Javascript.
        ui_require_jquery_file('pandora');
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        // Datatables list.
        try {
            $columns = [
                [
                    'text'  => 'id_usuario',
                    'class' => 'w50px',
                ],
                'accion',
                'fecha',
                'ip_origen',
                'descripcion',
            ];

            $column_names = [
                __('User'),
                __('Action'),
                __('Date'),
                __('Source IP'),
                __('Comments'),
            ];

            if (enterprise_installed() === true) {
                array_push(
                    $columns,
                    [
                        'text'  => 'security',
                        'class' => 'w80px table_action_buttons show_security_info',
                    ],
                    [
                        'text'  => 'action',
                        'class' => 'w80px table_action_buttons show_extended_info',
                    ]
                );

                array_push($column_names, __('S.'), __('A.'));
            }

            $this->tableId = 'audit_logs';

            ui_print_standard_header(
                __('%s audit', get_product_name()).' &raquo; '.__('Review Logs'),
                'images/gm_log@svg.svg',
                false,
                '',
                false,
                [],
                [
                    [
                        'link'  => '',
                        'label' => __('Admin Tools'),
                    ],
                    [
                        'link'  => '',
                        'label' => __('System Audit log'),
                    ],
                ]
            );

            $buttons = [];

            $buttons[] = [
                'id'      => 'load-filter',
                'class'   => 'float-left margin-right-2 margin-left-2 sub config',
                'text'    => __('Load filter'),
                'icon'    => 'load',
                'onclick' => '',
            ];

            $buttons[] = [
                'id'      => 'save-filter',
                'class'   => 'float-left margin-right-2 sub wand',
                'text'    => __('Save filter'),
                'icon'    => 'save',
                'onclick' => '',
            ];

            // Modal for save/load filters.
            echo '<div id="save-modal-filter" style="display:none"></div>';
            echo '<div id="load-modal-filter" style="display:none"></div>';

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
                    'ajax_postprocces'    => 'process_datatables_item(item)',
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'date',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'form'                => [
                        'extra_buttons' => $buttons,
                        'inputs'        => [
                            [
                                'label' => __('Free search').ui_print_help_tip(__('Search filter by User, Action, Date, Source IP or Comments fields content'), true),
                                'type'  => 'text',
                                'class' => 'w100p',
                                'id'    => 'filter_text',
                                'name'  => 'filter_text',
                            ],
                            [
                                'label'          => __('Max. hours old'),
                                'type'           => 'select',
                                'class'          => 'w20px',
                                'select2_enable' => true,
                                'sort'           => false,
                                'selected'       => 168,
                                'fields'         => [
                                    24   => __('1 day'),
                                    168  => __('7 days'),
                                    360  => __('15 days'),
                                    744  => __('1 month'),
                                    2160 => __('3 months'),
                                    4320 => __('6 months'),
                                    8760 => __('1 Year'),
                                ],
                                'id'             => 'filter_period',
                                'name'           => 'filter_period',
                            ],
                            [
                                'label' => __('IP'),
                                'type'  => 'text',
                                'class' => 'w100p',
                                'id'    => 'filter_ip',
                                'name'  => 'filter_ip',
                            ],
                            [
                                'label'         => __('Action'),
                                'type'          => 'select_from_sql',
                                'nothing'       => __('All'),
                                'nothing_value' => '-1',
                                'sql'           => 'SELECT DISTINCT(accion), accion AS text FROM tsesion',
                                'class'         => 'mw200px',
                                'id'            => 'filter_type',
                                'name'          => 'filter_type',
                            ],
                            [
                                'label'         => __('User'),
                                'type'          => 'select_from_sql',
                                'nothing'       => __('All'),
                                'nothing_value' => '-1',
                                'sql'           => 'SELECT id_user, id_user AS text FROM tusuario UNION SELECT "SYSTEM"
                                                    AS id_user, "SYSTEM" AS text UNION SELECT "N/A"
                                                    AS id_user, "N/A" AS text',
                                'class'         => 'mw200px',
                                'id'            => 'filter_user',
                                'name'          => 'filter_user',
                            ],
                        ],
                    ],
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // Load own javascript file.
        echo $this->loadJS();

        html_print_action_buttons([], ['type' => 'form_action']);
    }


    /**
     * Get the data for draw the table.
     *
     * @return void.
     */
    public function draw()
    {
        global $config;
        // Initialice filter.
        $filter = '1=1';
        // Init data.
        $data = [];
        // Count of total records.
        $count = 0;
        // Catch post parameters.
        $start              = get_parameter('start', 0);
        $length             = get_parameter('length', $config['block_size']);
        $order              = get_datatable_order();
        $filters            = get_parameter('filter', []);
        $this->filterType   = $filters['filter_type'];
        $this->filterUser   = $filters['filter_user'];
        $this->filterText   = $filters['filter_text'];
        $this->filterPeriod = (empty($filters['filter_period']) === false) ? $filters['filter_period'] : 24;
        $this->filterIp     = $filters['filter_ip'];

        if (empty($this->filterType) === false && $this->filterType !== '-1') {
            $filter .= sprintf(" AND accion = '%s'", $this->filterType);
        }

        if (empty($this->filterUser) === false && $this->filterUser !== '-1') {
            $filter .= sprintf(" AND id_usuario = '%s'", $this->filterUser);
        }

        if (empty($this->filterText) === false) {
            $filter .= sprintf(
                " AND (accion LIKE '%%%s%%' OR descripcion LIKE '%%%s%%' OR id_usuario LIKE '%%%s%%' OR fecha LIKE '%%%s%%' OR ip_origen LIKE '%%%s%%')",
                $this->filterText,
                $this->filterText,
                $this->filterText,
                $this->filterText,
                $this->filterText
            );
        }

        if (empty($this->filterIp) === false) {
            $filter .= sprintf(" AND ip_origen LIKE '%%%s%%'", $this->filterIp);
        }

        if (empty($this->filterPeriod) === false) {
            $filter .= sprintf(' AND fecha >= DATE_ADD(NOW(), INTERVAL -%d HOUR)', $this->filterPeriod);
        }

        $count = (int) db_get_value_sql(sprintf('SELECT COUNT(*) as "total" FROM tsesion WHERE %s', $filter));

        if ($length !== '-1') {
            $sql = sprintf(
                'SELECT *
                FROM tsesion
                WHERE %s
                ORDER BY %s
                LIMIT %d, %d',
                $filter,
                $order,
                $start,
                $length
            );
        } else {
            $sql = sprintf(
                'SELECT *
                FROM tsesion
                WHERE %s
                ORDER BY %s',
                $filter,
                $order
            );
        }

        $data = db_get_all_rows_sql($sql);

        if (empty($data) === false) {
            $data = array_reduce(
                $data,
                function ($carry, $item) {
                    global $config;
                    // Transforms array of arrays $data into an array
                    // of objects, making a post-process of certain fields.
                    $tmp = (object) $item;

                    $tmp->id_usuario  = io_safe_output($tmp->id_usuario);
                    $tmp->ip_origen   = io_safe_output($tmp->ip_origen);
                    $tmp->descripcion = io_safe_output($tmp->descripcion);
                    $tmp->accion      = ui_print_session_action_icon($tmp->accion, true).$tmp->accion;
                    $tmp->utimestamp  = ui_print_help_tip(
                        date(
                            $config['date_format'],
                            $tmp->utimestamp
                        ),
                        true
                    ).ui_print_timestamp($tmp->utimestamp, true);

                    if (enterprise_installed() === true) {
                        $extendedInfo = enterprise_hook('rowEnterpriseAudit', [$tmp->id_sesion]);
                        if (empty($extendedInfo) === false) {
                            $tmp->security     = enterprise_hook('cell1EntepriseAudit', [$tmp->id_sesion]);
                            $tmp->action       = enterprise_hook('cell2EntepriseAudit', []);
                            $tmp->extendedInfo = $extendedInfo;
                        }
                    }

                    $carry[] = $tmp;
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
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method)
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Load Javascript code.
     *
     * @return string.
     */
    public function loadJS()
    {
        // Nothing for this moment.
        ob_start();

        // Javascript content.
        ?>
        <script type="text/javascript">
            var loading = 0;

            function format(d) {
                var output = '';

                if (d.extendedInfo === '') {
                    output = "<?php echo __('There is no additional information to display'); ?>";
                } else {
                    output = d.extendedInfo;
                }

                return output;
            }

            $(document).ready(function() {
                // Add event listener for opening and closing details
                $('#audit_logs tbody').on('click', 'td.show_extended_info', function() {
                    var tr = $(this).closest('tr');
                    var table = $("#<?php echo $this->tableId; ?>").DataTable();
                    var row = table.row(tr);

                    if (row.child.isShown()) {
                    // This row is already open - close it
                        row.child.hide();
                        tr.removeClass('shown');
                    } else {
                        // Open this row
                        row.child(format(row.data())).show();
                        tr.addClass('shown');
                    }
                    $('#audit_logs').css('table-layout','fixed');
                    $('#audit_logs').css('width','95% !important');
                });

                $('#button-save-filter').click(function() {
                    if ($('#save-filter-select').length) {
                        $('#save-filter-select').dialog({
                            width: "20%",
                            maxWidth: "25%",
                            title: "<?php echo __('Save filter'); ?>"
                        });
                        $('#info_box').html("");
                        $('#text-id_name').val("");
                        $.ajax({
                            method: 'POST',
                            url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                            dataType: 'json',
                            data: {
                                page: 'include/ajax/audit_log',
                                recover_aduit_log_select: 1
                            },
                            success: function(data) {
                                var options = "";
                                $.each(data,function(key,value){
                                    options += "<option value='"+key+"'>"+value+"</option>";
                                });
                                $('#overwrite_filter').html(options);
                                $('#overwrite_filter').select2();
                            }
                        });
                    } else {
                        if (loading == 0) {
                            loading = 1
                            $.ajax({
                                method: 'POST',
                                url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                                data: {
                                    page: 'include/ajax/audit_log',
                                    save_filter_modal: 1,
                                    current_filter: $('#latest_filter_id').val()
                                },
                                success: function(data) {
                                    $('#save-modal-filter')
                                        .empty()
                                        .html(data);
                                    loading = 0;
                                    $('#save-filter-select').dialog({
                                        width: "20%",
                                        maxWidth: "25%",
                                        title: "<?php echo __('Save filter'); ?>"
                                    });
                                }
                            });
                        }
                    }
                });

                $('#save_filter_form-0-1, #radiobtn0002').click(function(){
                    $('#overwrite_filter').select2();
                });

                /* Filter management */
                $('#button-load-filter').click(function (){
                    if($('#load-filter-select').length) {
                        $('#load-filter-select').dialog({width: "20%",
                            maxWidth: "25%",
                            title: "<?php echo __('Load filter'); ?>"
                        });
                        $.ajax({
                            method: 'POST',
                            url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                            dataType: 'json',
                            data: {
                                page: 'include/ajax/audit_log',
                                recover_aduit_log_select: 1
                            },
                            success: function(data) {
                                var options = "";
                                $.each(data,function(key,value){
                                    options += "<option value='"+key+"'>"+value+"</option>";
                                });
                                $('#filter_id').html(options);
                                $('#filter_id').select2();
                            }
                        });
                    } else {
                        if (loading == 0) {
                            loading = 1
                            $.ajax({
                                method: 'POST',
                                url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                                data: {
                                    page: 'include/ajax/audit_log',
                                    load_filter_modal: 1
                                },
                                success: function (data){
                                    $('#load-modal-filter')
                                    .empty()
                                    .html(data);
                                    loading = 0;
                                    $('#load-filter-select').dialog({
                                        width: "20%",
                                        maxWidth: "25%",
                                        title: "<?php echo __('Load filter'); ?>"
                                    });
                                }
                            });
                        }
                    }
                });
            });
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
