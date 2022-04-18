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
    public $AJAXMethods = [ 'draw' ];

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
                'id_usuario',
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
                        'class' => 'w80px action_buttons show_security_info',
                    ],
                    [
                        'text'  => 'action',
                        'class' => 'w80px action_buttons show_extended_info',
                    ]
                );

                array_push($column_names, __('S.'), __('A.'));
            }

            $this->tableId = 'audit_logs';

            // Header (only in Node).
            if (is_metaconsole() === false) {
                ui_print_standard_header(
                    __('%s audit', get_product_name()).' &raquo; '.__('Review Logs'),
                    'images/gm_log.png',
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
            }

            if (is_metaconsole() === true) {
                // Only in case of Metaconsole, format the frame.
                open_meta_frame();
            }

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
                    'ajax_postprocces'    => 'process_datatables_item(item)',
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'date',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'form'                => [
                        'inputs' => [
                            [
                                'label' => __('Search'),
                                'type'  => 'text',
                                'class' => 'w200px',
                                'id'    => 'filter_text',
                                'name'  => 'filter_text',
                            ],
                            [
                                'label' => __('Max. hours old'),
                                'type'  => 'text',
                                'class' => 'w100px',
                                'id'    => 'filter_period',
                                'name'  => 'filter_period',
                            ],
                            [
                                'label' => __('IP'),
                                'type'  => 'text',
                                'class' => 'w100px',
                                'id'    => 'filter_ip',
                                'name'  => 'filter_ip',
                            ],
                            [
                                'label'         => __('Action'),
                                'type'          => 'select_from_sql',
                                'nothing'       => __('All'),
                                'nothing_value' => '-1',
                                'sql'           => 'SELECT DISTINCT(accion), accion AS text FROM tsesion',
                                'class'         => 'mw250px',
                                'id'            => 'filter_type',
                                'name'          => 'filter_type',
                            ],
                            [
                                'label'         => __('User'),
                                'type'          => 'select_from_sql',
                                'nothing'       => __('All'),
                                'nothing_value' => '-1',
                                'sql'           => 'SELECT id_user, id_user AS text FROM tusuario',
                                'class'         => 'mw250px',
                                'id'            => 'filter_user',
                                'name'          => 'filter_user',
                            ],
                        ],
                    ],
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if (is_metaconsole() === true) {
            // Close the frame.
            close_meta_frame();
        }

        // Load own javascript file.
        echo $this->loadJS();

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
                " AND (accion LIKE '%%%s%%' OR descripcion LIKE '%%%s%%')",
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
                        $tmp->security     = enterprise_hook('cell1EntepriseAudit', [$tmp->id_sesion]);
                        $tmp->action       = enterprise_hook('cell2EntepriseAudit', []);
                        $tmp->extendedInfo = enterprise_hook('rowEnterpriseAudit', [$tmp->id_sesion]);
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
            function format ( d ) {
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
                $('#audit_logs tbody').on('click', 'td.show_extended_info', function () {
                    var tr = $(this).closest('tr');
                    var table = <?php echo 'dt_'.$this->tableId; ?>;
                    var row = table.row( tr );
            
                    if ( row.child.isShown() ) {
                        // This row is already open - close it
                        row.child.hide();
                        tr.removeClass('shown');
                    }
                    else {
                        // Open this row
                        row.child( format(row.data()) ).show();
                        tr.addClass('shown');
                    }
                } );
            } );
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
