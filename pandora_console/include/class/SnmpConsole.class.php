<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Controller for SNMP console
 *
 * @category   Controller
 * @package    Pandora FMS
 * @subpackage Community
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

use function Composer\Autoload\includeFile;

// Begin.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';


/**
 * Class SatelliteAgent
 */
class SnmpConsole extends HTML
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'draw',
        'loadModal',
        'deleteTrap',
        'deleteTraps',
        'validateTrap',
        'validateTraps',
        'showInfo',
    ];

    /**
     * Ajax page.
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Filter alert.
     *
     * @var integer
     */
    private $filter_alert;

    /**
     * Filter severity.
     *
     * @var integer
     */
    private $filter_severity;

    /**
     * Filter search.
     *
     * @var string
     */
    private $filter_free_search;

    /**
     * Filter status.
     *
     * @var integer
     */
    private $filter_status;

    /**
     * Filter group by.
     *
     * @var integer
     */
    private $filter_group_by;

    /**
     * Filter hours.
     *
     * @var integer
     */
    private $filter_hours_ago;

    /**
     * Filter trap type.
     *
     * @var integer
     */
    private $filter_trap_type;

    /**
     * Refresh.
     *
     * @var integer
     */
    private $refr;


    /**
     * Class constructor
     *
     * @param string $ajaxController Ajax controller.
     */
    public function __construct(
        string $ajaxController,
        int $filter_alert,
        int $filter_severity,
        string $filter_free_search,
        int $filter_status,
        int $filter_group_by,
        int $filter_hours_ago,
        int $filter_trap_type,
        int $refr
    ) {
        global $config;

        check_login();

        $agent_a = check_acl($config['id_user'], 0, 'AR');
        $agent_w = check_acl($config['id_user'], 0, 'AW');
        if ($agent_a === false && $agent_w === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access SNMP Console'
            );
            include 'general/noaccess.php';
            exit;
        }

        // Set the ajax controller.
        $this->ajaxController = $ajaxController;
        $this->filter_alert = $filter_alert;
        $this->filter_severity = $filter_severity;
        $this->filter_free_search = $filter_free_search;
        $this->filter_status = $filter_status;
        $this->filter_group_by = $filter_group_by;
        $this->filter_hours_ago = $filter_hours_ago;
        $this->filter_trap_type = $filter_trap_type;
        $this->refr = $refr;
    }


    /**
     * Run view
     *
     * @return void
     */
    public function run()
    {
        global $config;
        // Javascript.
        ui_require_jquery_file('pandora');
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        $default_refr = 300;

        $statistics['text'] = '<a href="index.php?sec=estado&sec2=operation/snmpconsole/snmp_statistics&pure='.$config['pure'].'">'.html_print_image(
            'images/logs@svg.svg',
            true,
            [
                'title' => __('Statistics'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>';
        $list['text'] = '<a href="index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&pure=0">'.html_print_image(
            'images/SNMP-network-numeric-data@svg.svg',
            true,
            [
                'title' => __('List'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>';
        $list['active'] = true;

        if (isset($screen) === false) {
            $screen = '';
        }

        // Header.
        ui_print_standard_header(
            __('SNMP Console'),
            'images/op_snmp.png',
            false,
            'snmp_console',
            false,
            [
                $screen,
                $list,
                $statistics,
            ],
            [
                [
                    'link'  => '',
                    'label' => __('Monitoring'),
                ],
                [
                    'link'  => '',
                    'label' => __('SNMP'),
                ],
            ]
        );

        // Datatables list.
        try {
            $checkbox_all = html_print_checkbox(
                'all_validate_box',
                1,
                false,
                true
            );

            $columns = [
                'status',
                [
                    'text'  => 'snmp_agent',
                    'class' => 'snmp-td datos_green',
                ],
                [
                    'text'  => 'enterprise_string',
                    'class' => 'snmp-td datos_green',
                ],
                [
                    'text'  => 'count',
                    'class' => 'snmp-td datos_green',
                ],
                [
                    'text'  => 'trap_subtype',
                    'class' => 'snmp-td datos_green',
                ],
                [
                    'text'  => 'user_id',
                    'class' => 'snmp-td datos_green',
                ],
                [
                    'text'  => 'timestamp',
                    'class' => 'snmp-td datos_green',
                ],
                'alert',
                [
                    'text'  => 'action',
                    'class' => 'table_action_buttons w120px',
                ],
                [
                    'text'  => 'm',
                    'class' => 'mw60px pdd_0px',
                ],
            ];

            $column_names = [
                __('Status'),
                __('SNMP Agent'),
                __('Enterprise String'),
                __('Count'),
                __('Trap subtype'),
                __('User ID'),
                __('Timestamp'),
                __('Alert'),
                __('Actions'),
                [
                    'text'  => 'm',
                    'extra' => $checkbox_all,
                    'class' => 'w20px no-text-imp',
                ],
            ];

            $show_alerts = [
                -1 => __('All'),
                0  => __('Not triggered'),
                1  => __('Triggered'),
            ];

            $severities = get_priorities();
            $severities[-1] = __('All');

            $paginations = [
                $config['block_size'] => __('Default'),
                25                    => '25',
                50                    => '50',
                100                   => '100',
                200                   => '200',
                500                   => '500',
            ];

            $status_array = [
                -1 => __('All'),
                0  => __('Not validated'),
                1  => __('Validated'),
            ];

            $trap_types = [
                -1 => __('None'),
                0  => __('Cold start (0)'),
                1  => __('Warm start (1)'),
                2  => __('Link down (2)'),
                3  => __('Link up (3)'),
                4  => __('Authentication failure (4)'),
                5  => __('Other'),
            ];

            $tableId = 'snmp_console';
            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => $tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => ['method' => 'draw'],
                    'ajax_postprocces'    => 'process_datatables_item(item)',
                    'search_button_class' => 'sub filter float-right',
                    'no_sortable_columns' => [
                        0,
                        1,
                        2,
                        3,
                        4,
                        5,
                        6,
                        7,
                        8,
                        9,
                    ],
                    'form'                => [
                        'class'  => 'flex-row',
                        'inputs' => [
                            [
                                'label'       => __('Alert'),
                                'type'        => 'select',
                                'id'          => 'filter_alert',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_alert',
                                'fields'      => $show_alerts,
                                'return'      => true,
                                'selected'    => $this->filter_alert,
                                'style'       => 'widht:100% !important',
                            ],
                            [
                                'label'       => __('Severity'),
                                'type'        => 'select',
                                'id'          => 'filter_severity',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_severity',
                                'fields'      => $severities,
                                'return'      => true,
                                'selected'    => $this->filter_severity,
                                'style'       => 'widht:100%',
                            ],
                            [
                                'label'       => __('Free search'),
                                'type'        => 'text',
                                'id'          => 'filter_free_search',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_free_search',
                                'value'       => $this->filter_free_search,
                            ],
                            [
                                'label'       => __('Status'),
                                'type'        => 'select',
                                'id'          => 'filter_status',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_status',
                                'fields'      => $status_array,
                                'return'      => true,
                                'selected'    => $this->filter_status,
                                'style'       => 'widht:100%',
                            ],
                            [
                                'label'       => __('Group by Enterprise String/IP'),
                                'type'        => 'select',
                                'name'        => 'filter_group_by',
                                'selected'    => $this->filter_group_by,
                                'disabled'    => false,
                                'return'      => true,
                                'id'          => 'filter_group_by',
                                'input_class' => 'filter_input_datatable',
                                'fields'      => [
                                    0 => __('No'),
                                    1 => __('Yes'),
                                ],
                            ],
                            [
                                'label'       => __('Max. hours old'),
                                'type'        => 'text',
                                'id'          => 'filter_hours_ago',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_hours_ago',
                                'value'       => $this->filter_hours_ago,
                            ],
                            [
                                'label'       => __('Trap type'),
                                'type'        => 'select',
                                'id'          => 'filter_trap_type',
                                'input_class' => 'filter_input_datatable',
                                'name'        => 'filter_trap_type',
                                'fields'      => $trap_types,
                                'return'      => true,
                                'selected'    => $this->filter_trap_type,
                            ],
                        ],
                    ],
                    'pagination_options'  => [
                        [
                            $config['block_size'],
                            5,
                            10,
                            25,
                            100,
                            200,
                            500,
                            1000,
                        ],
                        [
                            $config['block_size'],
                            5,
                            10,
                            25,
                            100,
                            200,
                            500,
                            1000,
                        ],
                    ],
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $buttons[] = html_print_submit_button(
            __('Validate'),
            'updatebt',
            false,
            [
                'class' => 'sub ok',
                'icon'  => 'next',
            ],
            true
        );
        $buttons[] = html_print_submit_button(
            __('Delete'),
            'deletebt',
            false,
            [
                'icon'    => 'delete',
                'mode'    => 'secondary',
                'onClick' => "javascript:return confirm('".__('Are you sure?')."')",
            ],
            true
        );

        html_print_action_buttons(
            implode('', $buttons),
            ['type' => 'form_action']
        );

        $legend = '<table id="legend_snmp_browser"class="w100p"><td><div class="snmp_view_div w100p legend_white">';
        $legend .= '<h3 style="position: relative;left: 50%;">'.__('Severity').'</h3>';
        $legend .= '<div class="display-flex"><div class="flex-50">';
        $priorities = get_priorities();
        $half = (count($priorities) / 2);
        $count = 0;
        foreach ($priorities as $num => $name) {
            if ($count == $half) {
                $legend .= '</div><div class="mrgn_lft_5px flex-50">';
            }

            $legend .= '<span class="'.get_priority_class($num).'">'.$name.'</span>';
            $legend .= '<br />';
            $count++;
        }

        $legend .= '</div></div></div></td>';
        $legend .= '<td><div class="snmp_view_div">';
        $legend .= '<h3>'.__('Status').'</h3>';
        $legend .= '<span class="datos_green">'.__('Validated').'</span>';
        $legend .= '<br />';
        $legend .= '<span class="datos_red">'.__('Not validated').'</span>';
        $legend .= '</div></td>';
        $legend .= '<td><div class="snmp_view_div">';
        $legend .= '<h3>'.__('Alert').'</h3>';
        $legend .= '<span class="datos_yellow">'.__('Alert').'</span>';
        $legend .= '<br />';
        $legend .= '<span class="datos_grey">'.__('Not fired').'</span>';
        $legend .= '</div></td>';
        $legend .= '<td><div class="snmp_view_div">';
        $legend .= '<h3>'.__('Action').'</h3>';
        $legend .= '<div style=" display : flex;align-items : center;">';
        $legend .= html_print_image('images/validate.svg', true, ['class' => 'main_menu_icon invert_filter']).' - '.__('Validate');
        $legend .= '</div>';
        $legend .= '<br />';
        $legend .= '<div style=" display : flex;align-items : center;">';
        $legend .= html_print_image('images/delete.svg', true, ['class' => 'main_menu_icon invert_filter']).' - '.__('Delete');
        $legend .= '</div>';
        $legend .= '</div></div></td>';

        echo '<br>';

        ui_toggle($legend, __('Legend'));

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

        // Init data.
        $data = [];
        // Count of total records.
        $count = 0;
        // Catch post parameters.
        $start   = get_parameter('start', 0);
        $length  = get_parameter('length', $config['block_size']);
        // There is a limit of (2^32)^2 (18446744073709551615) rows in a MyISAM table, show for show all use max nrows.
        $length = ($length != '-1') ? $length : '18446744073709551615';
        $order   = get_datatable_order(true);
        $filters = get_parameter('filter', []);

        // Build ranges.
        $now_timestamp = time();
        $interval_seconds = ($filters['filter_hours_ago'] * 3600);
        $ago_timestamp = ($now_timestamp - $interval_seconds);

        // Build ranges.
        $now = new DateTime();
        $ago = new DateTime();
        $interval = new DateInterval(sprintf('PT%dH', $filters['filter_hours_ago']));
        $ago->sub($interval);

        $date_from_trap = $ago->format('Y/m/d');
        $date_to_trap = $now->format('Y/m/d');
        $time_from_trap = $ago->format('H:i:s');
        $time_to_trap = $now->format('H:i:s');

        try {
            ob_start();
            $data = [];

            $user_groups = users_get_groups($config['id_user'], 'AR', false);
            $prea = array_keys($user_groups);
            $ids = join(',', $prea);

            $user_in_group_wo_agents = db_get_value_sql('select count(DISTINCT(id_usuario)) from tusuario_perfil where id_usuario ="'.$config['id_user'].'" and id_perfil = 1 and id_grupo in (select id_grupo from tgrupo where id_grupo in ('.$ids.') and id_grupo not in (select id_grupo from tagente))');
            if ($user_in_group_wo_agents == 0) {
                $rows = db_get_all_rows_filter(
                    'tagente',
                    ['id_grupo' => array_keys($user_groups)],
                    ['id_agente']
                );
                $id_agents = [];
                foreach ($rows as $row) {
                    $id_agents[] = $row['id_agente'];
                }

                if (!empty($id_agents)) {
                    $address_by_user_groups = agents_get_addresses($id_agents);
                    foreach ($address_by_user_groups as $i => $a) {
                        $address_by_user_groups[$i] = '"'.$a.'"';
                    }
                }
            } else {
                $rows = db_get_all_rows_filter(
                    'tagente',
                    [],
                    ['id_agente']
                );
                $id_agents = [];
                foreach ($rows as $row) {
                    $id_agents[] = $row['id_agente'];
                }

                $all_address_agents = agents_get_addresses($id_agents);
                foreach ($all_address_agents as $i => $a) {
                    $all_address_agents[$i] = '"'.$a.'"';
                }
            }

            if (empty($address_by_user_groups)) {
                $address_by_user_groups = [];
                array_unshift($address_by_user_groups, '""');
            }

            if (empty($all_address_agents)) {
                $all_address_agents = [];
                array_unshift($all_address_agents, '""');
            }

            $sql = 'SELECT * FROM ttrap
                WHERE (
                    `source` IN ('.implode(',', $address_by_user_groups).") OR
                    `source`='' OR
                    `source` NOT IN (".implode(',', $all_address_agents).')
                    )
                    %s
                ORDER BY timestamp DESC
                LIMIT %d,%d';

            $whereSubquery = '';
            if ($filters['filter_alert']  != -1) {
                $whereSubquery .= ' AND alerted = '.$filters['filter_alert'];
            }

            if ($filters['filter_severity'] != -1) {
                // There are two special severity values aimed to match two different trap standard severities
                // in database: warning/critical and critical/normal.
                if ($filters['filter_severity'] != EVENT_CRIT_OR_NORMAL
                    && $filters['filter_severity'] != EVENT_CRIT_WARNING_OR_CRITICAL
                ) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                            (alerted = 0 AND severity = '.$filters['filter_severity'].') OR
                            (alerted = 1 AND priority = '.$filters['filter_severity'].'))';
                    } else {
                        $whereSubquery .= ' AND (
                            (alerted = 0 AND 1 = '.$filters['filter_severity'].') OR
                            (alerted = 1 AND priority = '.$filters['filter_severity'].'))';
                    }
                } else if ($filters['filter_severity'] === EVENT_CRIT_WARNING_OR_CRITICAL) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                        (alerted = 0 AND (severity = '.EVENT_CRIT_WARNING.' OR severity = '.EVENT_CRIT_CRITICAL.')) OR
                        (alerted = 1 AND (priority = '.EVENT_CRIT_WARNING.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    } else {
                        $whereSubquery .= ' AND (
                        (alerted = 1 AND (priority = '.EVENT_CRIT_WARNING.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    }
                } else if ($filters['filter_severity'] === EVENT_CRIT_OR_NORMAL) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                        (alerted = 0 AND (severity = '.EVENT_CRIT_NORMAL.' OR severity = '.EVENT_CRIT_CRITICAL.')) OR
                        (alerted = 1 AND (priority = '.EVENT_CRIT_NORMAL.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    } else {
                        $whereSubquery .= ' AND (
                        (alerted = 1 AND (priority = '.EVENT_CRIT_NORMAL.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    }
                }
            }

            if ($filters['filter_free_search'] !== '') {
                $free_search_str = io_safe_output($filters['filter_free_search']);
                $whereSubquery .= '
                    AND (source LIKE "%'.$free_search_str.'%" OR
                    oid LIKE "%'.$free_search_str.'%" OR
                    oid_custom LIKE "%'.$free_search_str.'%" OR
                    type_custom LIKE "%'.$free_search_str.'%" OR
                    value LIKE "%'.$free_search_str.'%" OR
                    value_custom LIKE "%'.$free_search_str.'%" OR
                    id_usuario LIKE "%'.$free_search_str.'%" OR
                    text LIKE "%'.$free_search_str.'%" OR
                    description LIKE "%'.$free_search_str.'%")';
            }

            if ($filters['filter_status'] != -1) {
                $whereSubquery .= ' AND status = '.$filters['filter_status'];
            }

            if ($date_from_trap != '') {
                if ($time_from_trap != '') {
                    $whereSubquery .= '
                        AND (utimestamp > '.$ago_timestamp.')
                    ';
                } else {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) > UNIX_TIMESTAMP("'.$date_from_trap.' 23:59:59"))
                    ';
                }
            }

            if ($date_to_trap != '') {
                if ($time_to_trap) {
                    $whereSubquery .= '
                        AND (utimestamp < '.$now_timestamp.')
                    ';
                } else {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) < UNIX_TIMESTAMP("'.$date_to_trap.' 23:59:59"))
                    ';
                }
            }

            if ($filters['filter_trap_type'] == 5) {
                $whereSubquery .= ' AND type NOT IN (0, 1, 2, 3, 4)';
            } else if ($filters['filter_trap_type'] != -1) {
                $whereSubquery .= ' AND type = '.$filters['filter_trap_type'];
            }

            if ($filters['filter_group_by']) {
                $where_without_group = $whereSubquery;
                $whereSubquery .= ' GROUP BY source,oid';
            }

            $sql = sprintf($sql, $whereSubquery, $start, $length);

            $sql_count = 'SELECT COUNT(id_trap) FROM ttrap
			WHERE (
				source IN ('.implode(',', $address_by_user_groups).") OR
				source='' OR
				source NOT IN (".implode(',', $all_address_agents).')
				)
				%s';

            $sql_count = sprintf($sql_count, $whereSubquery);

            $traps = db_get_all_rows_sql($sql, true);
            $total = (int) db_get_value_sql($sql_count, false, false);

            if (empty($traps) === false) {
                $data = $traps;
                $data = array_reduce(
                    $data,
                    function ($carry, $item) use ($filters, $where_without_group) {
                        global $config;

                        if (empty($carry) === true) {
                            $count = 0;
                        } else {
                            $count = count($carry);
                        }

                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $severity_class = get_priority_class($tmp->severity);

                        $status = $tmp->status;

                        // Status.
                        if ($status == 0) {
                            $tmp->status = html_print_image(
                                'images/pixel_red.png',
                                true,
                                [
                                    'title'  => __('Not validated'),
                                    'width'  => '20',
                                    'height' => '20',
                                ]
                            );
                        } else {
                            $tmp->status = html_print_image(
                                'images/pixel_green.png',
                                true,
                                [
                                    'title'  => __('Validated'),
                                    'width'  => '20',
                                    'height' => '20',
                                ]
                            );
                        }

                        // SNMP Agent.
                        $agent = agents_get_agent_with_ip($tmp->source);
                        if ($agent === false) {
                            $tmp->snmp_agent .= '<a href="index.php?sec=estado&sec2=godmode/agentes/configurar_agente&new_agent=1&direccion='.$tmp->source.'" title="'.__('Create agent').'">'.$tmp->source.'</a>';
                        } else {
                            $tmp->snmp_agent .= '<div class="'.$severity_class.' snmp-div"><a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$agent['id_agente'].'" title="'.__('View agent details').'">';
                            $tmp->snmp_agent .= '<strong>'.$agent['alias'].ui_print_help_tip($tmp->source, true);
                            '</strong></a></div>';
                        }

                        // Enterprise string.
                        if (empty($tmp->text) === false) {
                            $enterprise_string = $tmp->text;
                        } else if (empty($tmp->oid) === false) {
                            $enterprise_string = $tmp->oid;
                        } else {
                            $enterprise_string = __('N/A');
                        }

                        $tmp->enterprise_string = '<div class="'.$severity_class.' snmp-div"><a href="javascript: toggleVisibleExtendedInfo('.$tmp->id_trap.','.$count.');">'.$enterprise_string.'</a></div>';

                        // Count.
                        if ($filters['filter_group_by']) {
                            $sql = 'SELECT count(*) FROM ttrap WHERE 1=1
                                    '.$where_without_group.'
                                    AND oid="'.$tmp->oid.'"
                                    AND source="'.$tmp->source.'"';
                            $group_traps = db_get_value_sql($sql);
                            $tmp->count = '<div class="'.$severity_class.' snmp-div">'.$group_traps.'</div>';
                        }

                        // Trap subtype.
                        $tmp->trap_subtype = '<div class="'.$severity_class.' snmp-div">';
                        if (empty($tmp->value) === true) {
                            $tmp->trap_subtype .= __('N/A');
                        } else {
                            $tmp->trap_subtype .= ui_print_truncate_text($tmp->value, GENERIC_SIZE_TEXT, false);
                        }

                        $tmp->trap_subtype .= '</div>';

                        // User ID.
                        $tmp->user_id = '<div class="'.$severity_class.' snmp-div">';
                        if (empty($status) === false) {
                            $tmp->user_id .= '<a href="index.php?sec=workspace&sec2=operation/users/user_edit&ver='.$tmp->id_usuario.'">'.substr($tmp->id_usuario, 0, 8).'</a>';
                            if (!empty($tmp->id_usuario)) {
                                $tmp->user_id .= ui_print_help_tip(get_user_fullname($tmp->id_usuario), true);
                            }
                        } else {
                            $tmp->user_id .= '--';
                        }

                        $tmp->user_id .= '</div>';

                        // Timestamp.
                        $timestamp = $tmp->timestamp;
                        $tmp->timestamp = '<div class="'.$severity_class.' snmp-div">';
                        $tmp->timestamp .= '<span title="'.$timestamp.'">';
                        $tmp->timestamp .= ui_print_timestamp($timestamp, true);
                        $tmp->timestamp .= '</span></div>';

                        // Use alert severity if fired.
                        if (empty($tmp->alerted) === false) {
                            $tmp->alert = html_print_image('images/pixel_yellow.png', true, ['width' => '20', 'height' => '20', 'border' => '0', 'title' => __('Alert fired')]);
                        } else {
                            $tmp->alert = html_print_image('images/pixel_gray.png', true, ['width' => '20', 'height' => '20', 'border' => '0', 'title' => __('Alert not fired')]);
                        }

                        // Actions.
                        $tmp->action = '';
                        if ($status != 1) {
                            $tmp->action .= '<a href="#">'.html_print_image(
                                'images/validate.svg',
                                true,
                                [
                                    'border'  => '0',
                                    'title'   => __('Validate'),
                                    'onclick' => 'validate_trap(\''.$tmp->id_trap.'\')',
                                    'class'   => 'invert_filter main_menu_icon',
                                ]
                            ).'</a> ';
                        }

                        if ($tmp->source === '') {
                            if (\users_is_admin()) {
                                $tmp->action .= '<a href="#">'.html_print_image(
                                    'images/delete.svg',
                                    true,
                                    [
                                        'border'  => '0',
                                        'title'   => __('Delete'),
                                        'class'   => 'invert_filter main_menu_icon',
                                        'onclick' => 'delete_trap(\''.$tmp->id_trap.'\')',
                                    ]
                                ).'</a> ';
                            }
                        } else {
                            $tmp->action .= '<a href="#">'.html_print_image(
                                'images/delete.svg',
                                true,
                                [
                                    'border'  => '0',
                                    'title'   => __('Delete'),
                                    'class'   => 'invert_filter main_menu_icon',
                                    'onclick' => 'delete_trap(\''.$tmp->id_trap.'\')',
                                ]
                            ).'</a> ';
                        }

                        $tmp->action .= '<a id="eye_'.$tmp->id_trap.'" data-show="show"
                        href="javascript: toggleVisibleExtendedInfo('.$tmp->id_trap.','.$count.');">'.html_print_image(
                            'images/see-details@svg.svg',
                            true,
                            [
                                'id'    => 'img_'.$tmp->id_trap,
                                'alt'   => __('Show more'),
                                'title' => __('Show more'),
                                'class' => 'invert_filter main_menu_icon',
                            ]
                        ).' '.html_print_image(
                            'images/disable.svg',
                            true,
                            [
                                'id'    => 'img_hide_'.$tmp->id_trap,
                                'alt'   => __('Hide details'),
                                'title' => __('Hide details'),
                                'class' => 'invert_filter main_menu_icon',
                                'style' => 'display:none',
                            ]
                        ).'</a>';

                        if ($config['enterprise_installed']) {
                            $tmp->action .= '<a href="index.php?sec=snmpconsole&sec2=enterprise/godmode/snmpconsole/snmp_trap_editor_form&id='.$tmp->id_trap.'&oid='.$tmp->oid.'&custom_oid='.urlencode($tmp->oid_custom).'&severity='.$tmp->severity.'&text='.io_safe_input($tmp->text).'&description='.io_safe_input($tmp->description, ENT_QUOTES).'" title="'.io_safe_input($tmp->description, ENT_QUOTES).'">';
                            $tmp->action .= html_print_image(
                                'images/edit.svg',
                                true,
                                [
                                    'alt'   => __('SNMP trap editor'),
                                    'title' => __('SNMP trap editor'),
                                    'class' => 'main_menu_icon invert_filter',
                                ]
                            );
                            $tmp->action .= '</a>';
                        }

                        $tmp->m = html_print_checkbox_extended('snmptrapid[]', $tmp->id_trap, false, false, '', 'class="chk"', true);

                        $carry[] = $tmp;
                        return $carry;
                    },
                );
            }

            if (empty($data) === true) {
                $total = 0;
                $data = [];
            }

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
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
        if (json_last_error() === JSON_ERROR_NONE) {
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
     * Delete snmp trap.
     *
     * @return void
     */
    public function deleteTrap()
    {
        $id_trap = get_parameter('id', 0);
        $group_by = (bool) get_parameter('group_by', 0);

        if ($id_trap > 0) {
            if ($group_by === true) {
                $sql_ids_traps = 'SELECT id_trap, source FROM ttrap WHERE oid IN (SELECT oid FROM ttrap WHERE id_trap = '.$id_trap.')
			    AND source IN (SELECT source FROM ttrap WHERE id_trap = '.$id_trap.')';
                $ids_traps = db_get_all_rows_sql($sql_ids_traps);

                foreach ($ids_traps as $key => $value) {
                    $result = db_process_sql_delete('ttrap', ['id_trap' => $value['id_trap']]);
                    enterprise_hook('snmp_update_forwarded_modules', [$value]);
                }
            } else {
                $forward_info = db_get_row('ttrap', 'id_trap', $id_trap);
                $result = db_process_sql_delete('ttrap', ['id_trap' => $id_trap]);
                enterprise_hook('snmp_update_forwarded_modules', [$forward_info]);
            }
        }
    }


    /**
     * Delete snmp traps.
     *
     * @return void
     */
    public function deleteTraps()
    {
        $ids = get_parameter('ids', []);
        $group_by = (bool) get_parameter('group_by', false);

        if (empty($ids) === false) {
            $string_ids = implode(',', $ids);
            if ($group_by === true) {
                $sql_ids_traps = 'SELECT id_trap, source FROM ttrap WHERE oid IN (SELECT oid FROM ttrap WHERE id_trap IN ('.$string_ids.'))
			    AND source IN (SELECT source FROM ttrap WHERE id_trap IN ('.$string_ids.'))';
                $ids_traps = db_get_all_rows_sql($sql_ids_traps);

                $array = array_column($ids_traps, 'id_trap');

                $delete = sprintf(
                    'DELETE FROM `ttrap` WHERE id_trap IN (%s)',
                    implode(',', $array),
                );
                db_process_sql($delete);

                foreach ($ids_traps as $key => $value) {
                    enterprise_hook('snmp_update_forwarded_modules', [$value]);
                }
            } else {
                $delete = sprintf(
                    'DELETE FROM `ttrap` WHERE id_trap IN (%s)',
                    $string_ids,
                );
                db_process_sql($delete);
                foreach ($ids as $id_trap) {
                    enterprise_hook('snmp_update_forwarded_modules', [$id_trap]);
                }
            }
        }
    }


    /**
     * Validate snmp trap.
     *
     * @return void
     */
    public function validateTrap()
    {
        global $config;

        $id_trap = get_parameter('id', 0);

        $values = [
            'status'     => 1,
            'id_usuario' => $config['id_user'],
        ];

        $result = db_process_sql_update('ttrap', $values, ['id_trap' => $id_trap]);
        enterprise_hook('snmp_update_forwarded_modules', [$id_trap]);
    }


    /**
     * Validate snmp traps.
     *
     * @return void
     */
    public function validateTraps()
    {
        global $config;

        $ids = get_parameter('ids', []);

        if (empty($ids) === false) {
            $update = sprintf(
                'UPDATE ttrap SET `status` = 1, `id_usuario` = "%s" WHERE id_trap IN (%s)',
                $config['id_user'],
                implode(',', $ids)
            );
            db_process_sql($update);

            foreach ($ids as $id_trap) {
                enterprise_hook('snmp_update_forwarded_modules', [$id_trap]);
            }
        }
    }


    /**
     * VShow info trap.
     *
     * @return void
     */
    public function showInfo()
    {
        global $config;

        $id_trap = get_parameter('id', 0);
        $group_by = get_parameter('group_by', 0);
        $alert = get_parameter('alert', -1);
        $severity = get_parameter('severity', -1);
        $search = get_parameter('search', '');
        $status = get_parameter('status', 0);
        $hours_ago = get_parameter('hours_ago', 8);
        $trap_type = get_parameter('trap_type', -1);

        $trap = db_get_row('ttrap', 'id_trap', $id_trap);

        if ($group_by) {
            $now = new DateTime();
            $ago = new DateTime();
            $interval = new DateInterval(sprintf('PT%dH', $hours_ago));
            $ago->sub($interval);

            $date_from_trap = $ago->format('Y/m/d');
            $date_to_trap = $now->format('Y/m/d');
            $time_from_trap = $ago->format('H:i:s');
            $time_to_trap = $now->format('H:i:s');

            $whereSubquery = '';
            if ($alert  != -1) {
                $whereSubquery .= ' AND alerted = '.$$alert;
            }

            if ($severity != -1) {
                // There are two special severity values aimed to match two different trap standard severities
                // in database: warning/critical and critical/normal.
                if ($severity != EVENT_CRIT_OR_NORMAL
                    && $severity != EVENT_CRIT_WARNING_OR_CRITICAL
                ) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                            (alerted = 0 AND severity = '.$severity.') OR
                            (alerted = 1 AND priority = '.$severity.'))';
                    } else {
                        $whereSubquery .= ' AND (
                            (alerted = 0 AND 1 = '.$severity.') OR
                            (alerted = 1 AND priority = '.$severity.'))';
                    }
                } else if ($severity === EVENT_CRIT_WARNING_OR_CRITICAL) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                        (alerted = 0 AND (severity = '.EVENT_CRIT_WARNING.' OR severity = '.EVENT_CRIT_CRITICAL.')) OR
                        (alerted = 1 AND (priority = '.EVENT_CRIT_WARNING.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    } else {
                        $whereSubquery .= ' AND (
                        (alerted = 1 AND (priority = '.EVENT_CRIT_WARNING.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    }
                } else if ($severity === EVENT_CRIT_OR_NORMAL) {
                    // Test if enterprise is installed to search oid in text or oid field in ttrap.
                    if ($config['enterprise_installed']) {
                        $whereSubquery .= ' AND (
                        (alerted = 0 AND (severity = '.EVENT_CRIT_NORMAL.' OR severity = '.EVENT_CRIT_CRITICAL.')) OR
                        (alerted = 1 AND (priority = '.EVENT_CRIT_NORMAL.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    } else {
                        $whereSubquery .= ' AND (
                        (alerted = 1 AND (priority = '.EVENT_CRIT_NORMAL.' OR priority = '.EVENT_CRIT_CRITICAL.')))';
                    }
                }
            }

            if ($search !== '') {
                $whereSubquery .= '
                    AND (source LIKE "%'.$search.'%" OR
                    oid LIKE "%'.$search.'%" OR
                    oid_custom LIKE "%'.$search.'%" OR
                    type_custom LIKE "%'.$search.'%" OR
                    value LIKE "%'.$search.'%" OR
                    value_custom LIKE "%'.$search.'%" OR
                    id_usuario LIKE "%'.$search.'%" OR
                    text LIKE "%'.$search.'%" OR
                    description LIKE "%'.$search.'%")';
            }

            if ($status != -1) {
                $whereSubquery .= ' AND status = '.$status;
            }

            if ($date_from_trap != '') {
                if ($time_from_trap != '') {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) > UNIX_TIMESTAMP("'.$date_from_trap.' '.$time_from_trap.'"))
                    ';
                } else {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) > UNIX_TIMESTAMP("'.$date_from_trap.' 23:59:59"))
                    ';
                }
            }

            if ($date_to_trap != '') {
                if ($time_to_trap) {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) < UNIX_TIMESTAMP("'.$date_to_trap.' '.$time_to_trap.'"))
                    ';
                } else {
                    $whereSubquery .= '
                        AND (UNIX_TIMESTAMP(timestamp) < UNIX_TIMESTAMP("'.$date_to_trap.' 23:59:59"))
                    ';
                }
            }

            if ($trap_type == 5) {
                $whereSubquery .= ' AND type NOT IN (0, 1, 2, 3, 4)';
            } else if ($trap_type != -1) {
                $whereSubquery .= ' AND type = '.$trap_type;
            }

            $sql = 'SELECT * FROM ttrap WHERE 1=1
                '.$whereSubquery.'
                AND oid="'.$trap['oid'].'"
                AND source="'.$trap['source'].'"';
            $group_traps = db_get_all_rows_sql($sql);
            $count_group_traps = count($group_traps);

            $sql = 'SELECT timestamp FROM ttrap WHERE 1=1
                '.$whereSubquery.'
                AND oid="'.$trap['oid'].'"
                AND source="'.$trap['source'].'"
                ORDER BY `timestamp` DESC';
            $last_trap = db_get_value_sql($sql);

            $sql = 'SELECT timestamp FROM ttrap WHERE 1=1
                '.$whereSubquery.'
                AND oid="'.$trap['oid'].'"
                AND source="'.$trap['source'].'"
                ORDER BY `timestamp` ASC';
            $first_trap = db_get_value_sql($sql);

            $trap['count'] = $count_group_traps;
            $trap['first'] = $first_trap;
            $trap['last'] = $last_trap;
        }

        echo json_encode($trap);
        return;
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
            /**
             *   Delete selected snmp trap
             */
            function delete_trap(id) {
                if (confirm('<?php echo __('Are you sure?'); ?>')) {
                    $.ajax({
                        method: 'post',
                        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                        data: {
                            page: 'operation/snmpconsole/snmp_view',
                            method: 'deleteTrap',
                            id: id,
                            group_by: $('#filter_group_by').val(),
                        },
                        datatype: "json",
                        success: function(data) {
                            var dt_snmp = $("#snmp_console").DataTable();
                            dt_snmp.draw();
                        },
                        error: function(e) {
                            console.error(e);
                        }
                    });
                }
            }


            /**
             *   Validated selected snmp trap
             */
            function validate_trap(id) {
                if (confirm('<?php echo __('Are you sure?'); ?>')) {
                    $.ajax({
                        method: 'post',
                        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                        data: {
                            page: 'operation/snmpconsole/snmp_view',
                            method: 'validateTrap',
                            id: id,
                        },
                        datatype: "json",
                        success: function(data) {
                            var dt_snmp = $("#snmp_console").DataTable();
                            dt_snmp.draw();
                        },
                        error: function(e) {
                            console.error(e);
                        }
                    });
                }
            }


            function fullscreen(pure) {
                let new_url = 'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view&pure='+pure;
                new_url += '&filter_severity='+$('#filter_severity').val();
                new_url += '&filter_status='+$('#filter_status').val();
                new_url += '&filter_alert='+$('#filter_alert').val();
                new_url += '&filter_group_by=0&filter_free_search='+$('#text-filter_free_search').val();
                new_url += '&filter_hours_ago='+$('#text-filter_hours_ago').val();
                new_url += '&filter_trap_type='+$('#filter_trap_type').val();

                window.location.href = new_url;
            }


            /**
             *   Show more information
             */
            function toggleVisibleExtendedInfo(id, position) {
                var status = $('#eye_'+id).attr('data-show');
                if(status == "show"){
                    $.ajax({
                        method: 'get',
                        url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                        data: {
                            page: 'operation/snmpconsole/snmp_view',
                            method: 'showInfo',
                            id: id,
                            group_by : $('#filter_group_by').val(),
                            alert: $('#filter_alert').val(),
                            severity: $('#filter_severity').val(),
                            search: $('#text-filter_free_search').val(),
                            status: $('#filter_status').val(),
                            hours_ago: $('#text-filter_hours_ago').val(),
                            trap_type: $('#filter_trap_type').val()
                        },
                        datatype: "json",
                        success: function(data) {
                            let trap = JSON.parse(data);
                            var tr = $('#snmp_console tr:not([id^="show_"])').eq(position+1);

                            // Count.
                            if ($('#filter_group_by').val() == 1) {
                                let labelCount = '<td align="left" valign="top"><b><?php echo __('Count:'); ?></b></br><b><?php echo __('First trap:'); ?></b></br><b><?php echo __('Last trap:'); ?></td>';
                                let variableCount = `<td align="left" valign="top" style="line-height: 16pt">${trap['count']}</br>${trap['first']}</br>${trap['last']}</td>`;

                                tr.after(`<tr id="show_${id}" role="row">${labelCount}${variableCount}</tr>`);
                            }

                            // Type.
                            desc_trap_type = "<?php echo __('Other'); ?>";
                            switch (trap['type']) {
                                case -1:
                                    desc_trap_type = "<?php echo __('None'); ?>";
                                break;

                                case 0:
                                    desc_trap_type = "<?php echo __('Cold start (0)'); ?>";
                                break;

                                case 1:
                                    desc_trap_type = "<?php echo __('Warm start (1)'); ?>";
                                break;

                                case 2:
                                    desc_trap_type = "<?php echo __('Link down (2)'); ?>";
                                break;

                                case 3:
                                    desc_trap_type = "<?php echo __('Link up (3)'); ?>";
                                break;

                                case 4:
                                    desc_trap_type = "<?php echo __('Authentication failure (4)'); ?>";
                                break;

                                default:
                                    desc_trap_type = "<?php echo __('Other'); ?>";
                                break;
                            }

                            let labelType = '<td align="left" valign="top"><b><?php echo __('Type:'); ?></td>';
                            let variableType = `<td align="left" colspan="8">${desc_trap_type}</td>`;

                            tr.after(`<tr id="show_${id}" role="row">${labelType}${variableType}</tr>`);

                            // Description.
                            if (trap['description']) {
                                let labelDesc = '<td align="left" valign="top"><b><?php echo __('Description:'); ?></td>';
                                let variableDesc = `<td align="left" colspan="8">${trap['description']}</td>`;

                                tr.after(`<tr id="show_${id}" role="row">${labelDesc}${variableDesc}</tr>`);
                            }

                            // Enterprise String.
                            let labelOid = '<td align="left" valign="top"><b><?php echo __('Enterprise String:'); ?></td>';
                            let variableOId = `<td align="left" colspan="8">${trap['oid']}</td>`;

                            tr.after(`<tr id="show_${id}" role="row">${labelOid}${variableOId}</tr>`);

                            // Variable bindings.
                            let labelBindings = '';
                            let variableBindings = '';
                            if ($('#filter_group_by').val() == 1) {
                                labelBindings = '<td align="left" valign="top" ><b><?php echo __('Variable bindings:'); ?></b></td>';

                                let new_url = 'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_view';
                                new_url += '&filter_severity='+$('#filter_severity').val();
                                new_url += '&filter_status='+$('#filter_status').val();
                                new_url += '&filter_alert='+$('#filter_alert').val();
                                new_url += '&filter_group_by=0&filter_free_search='+$('#text-filter_free_search').val();
                                new_url += '&filter_hours_ago='+$('#text-filter_hours_ago').val();
                                new_url += '&filter_trap_type='+$('#filter_trap_type').val();

                                const string = '<a href="'+new_url+'"><?php echo __('See more details'); ?></a>';

                                variableBindings = `<td align="left" colspan="8">${string}</td>`;
                            } else {
                                labelBindings = '<td align="left" valign="top" ><b><?php echo __('Variable bindings:'); ?></b></td>';
                                const binding_vars = trap['oid_custom'].split("\t");
                                let string = '';
                                binding_vars.forEach(function(oid) {
                                    string += oid+'<br/>';
                                });
                                variableBindings = `<td align="left" colspan="8" class="break-word w200px">${string}</td>`;
                            }

                            tr.after(`<tr id="show_${id}" role="row">${labelBindings}${variableBindings}</tr>`);
                        },
                        error: function(e) {
                            console.error(e);
                        }
                    });
                    $('#eye_'+id).attr('data-show', 'hide');
                    $('#img_'+id).hide();
                    $('#img_hide_'+id).show();
                } else{
                    $(`tr#show_${id}`).remove();
                    $('#eye_'+id).attr('data-show', 'show');
                    $('#img_'+id).show();
                    $('#img_hide_'+id).hide();
                }
            }

            $(document).ready(function() {
                var table = $('#snmp_console').DataTable();
                const column = table.column(3);
                column.visible(false);

                $('#form_snmp_console_search_bt').click(function() {
                    if ($('#filter_group_by').val() == 1) {
                        column.visible(true);
                    } else {
                        column.visible(false);
                    }
                });

                $('#button-updatebt').click(function() {
                    let array = [];
                    $('input[name="snmptrapid[]"]:checked').each(function() {
                        array.push(this.value);
                    });

                    if (array.length > 0) {
                        $.ajax({
                            method: 'post',
                            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                            data: {
                                page: 'operation/snmpconsole/snmp_view',
                                method: 'validateTraps',
                                ids: array,
                            },
                            datatype: "json",
                            success: function(data) {
                                var dt_snmp = $("#snmp_console").DataTable();
                                dt_snmp.draw();
                            },
                            error: function(e) {
                                console.error(e);
                            }
                        });
                    }
                });

                $('#button-deletebt').click(function() {
                    let array = [];
                    $('input[name="snmptrapid[]"]:checked').each(function() {
                        array.push(this.value);
                    });

                    if (array.length > 0) {
                        $.ajax({
                            method: 'post',
                            url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                            data: {
                                page: 'operation/snmpconsole/snmp_view',
                                method: 'deleteTraps',
                                ids: array,
                                group_by: $('#filter_group_by').val(),
                            },
                            datatype: "json",
                            success: function(data) {
                                var dt_snmp = $("#snmp_console").DataTable();
                                dt_snmp.draw();
                            },
                            error: function(e) {
                                console.error(e);
                            }
                        });
                    }
                });

                $('#checkbox-all_validate_box').click(function() {
                    const c = this.checked;
                    $(':checkbox').prop('checked', c);
                });

                var controls = document.getElementById('dashboard-controls');
                autoHideElement(controls, 1000);

                var startCountDown = function (duration, cb) {
                    $('div.dashboard-countdown').countdown('destroy');
                    if (!duration) return;
                    var t = new Date();
                    t.setTime(t.getTime() + duration * 1000);
                    $('div.dashboard-countdown').countdown({
                        until: t,
                        format: 'MS',
                        layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>) ',
                        alwaysExpire: true,
                        onExpiry: function () {
                            var dt_snmp = $("#snmp_console").DataTable();
                            dt_snmp.draw();
                            startCountDown(duration);
                            throw "exit";
                        }
                    });
                }

                // Auto refresh select
                $('form#refr-form').submit(function (event) {
                    event.preventDefault();
                });

                var handleRefrChange = function (event) {
                    event.preventDefault();
                    var url = $('form#refr-form').prop('action');
                    var refr = Number.parseInt(event.target.value, 10);

                    startCountDown(refr);
                }

                $('form#refr-form select').change(handleRefrChange).change();

            });
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
