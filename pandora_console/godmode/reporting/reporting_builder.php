<script type="text/javascript">

function dialog_message(message_id) {
  $(message_id)
    .css("display", "inline")
    .dialog({
      modal: true,
      show: "blind",
      hide: "blind",
      width: "400px",
      buttons: {
        Close: function() {
          $(this).dialog("close");
        }
      }
    });
}


    function check_all_checkboxes() {
        if ($("input[name=all_delete]").prop("checked")) {
            $(".check_delete").prop("checked", true);
            $('.check_delete').each(function(){
            $('.massive_report_form_elements').prop("disabled", false);
            });
        }
        else {
            $(".check_delete").prop("checked", false);
            $('.check_delete').each(function(){
            $('.massive_report_form_elements').prop("disabled", true);
            });
        }
    }

    $( document ).ready(function() {
        $('.check_delete').click(function(){
            $('.check_delete').each(function(){
                if($(this).prop( "checked" )){
                    $('#hidden-id_report_'+$(this).val())
                    .prop("disabled", false);
                }
                else{
                    $('#hidden-id_report_'+$(this).val())
                    .prop("disabled", true);
                }
            });
        });

        $('[id^=checkbox-all_delete]').change(function(){
            if ($("#checkbox-all_delete").prop("checked")) {
                $(".check_delete").prop("checked", true);
                $('.check_delete').each(function(){
                    $('#hidden-id_report_'+$(this).val()).prop("disabled", false);
                });
            }
            else{
                $(".check_delete").prop("checked", false);
            }
        });
    });
</script>

<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Reporting
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

/*
    IMPORTANT NOTE: All reporting pages are used also
    for metaconsole reporting functionality.
    So, it's very important to specify full url and paths to resources
    because metaconsole has a different.
    entry point: enterprise/meta/index.php than normal console !!!.
*/

// Login check.
check_login();

$report_r = check_acl($config['id_user'], 0, 'RR');
$report_w = check_acl($config['id_user'], 0, 'RW');
$report_m = check_acl($config['id_user'], 0, 'RM');
$access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));
if (!$report_r && !$report_w && !$report_m) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access report builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_reports.php';

// Load enterprise extensions.
enterprise_include('operation/reporting/custom_reporting.php');
enterprise_include_once('include/functions_metaconsole.php');



$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
    $enterpriseEnable = true;
}

// Constant with fonts directory.
define('_MPDF_TTFONTPATH', $config['homedir'].'/include/fonts/');

$activeTab = get_parameter('tab', 'main');
$action = get_parameter('action', 'list');
$idReport = get_parameter('id_report', 0);
$offset = (int) get_parameter('offset', 0);
$idItem = get_parameter('id_item', 0);
$pure = get_parameter('pure', 0);
$schedule_report = get_parameter('schbutton', '');
$pagination = (int) get_parameter('pagination', $config['block_size']);

if ($action === 'edit' && $idReport > 0) {
    $report_group = db_get_value(
        'id_group',
        'treport',
        'id_report',
        $idReport
    );

    if (! check_acl_restricted_all($config['id_user'], $report_group, 'RW')
        && ! check_acl_restricted_all($config['id_user'], $report_group, 'RM')
    ) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access report builder'
        );
        include 'general/noaccess.php';
        exit;
    }
}

if ($schedule_report != '') {
    $id_user_task = 1;
    $scheduled = 'no';
    $date = date(DATE_FORMAT);
    $time = date(TIME_FORMAT);
    $parameters[0] = get_parameter('id_schedule_report');
    $parameters[1] = get_parameter('schedule_email_address');
    $parameters[2] = get_parameter('schedule_subject', '');
    $parameters[3] = get_parameter('schedule_email', '');
    $parameters[4] = get_parameter('report_type', '');
    $parameters['first_execution'] = strtotime($date.' '.$time);


    $values = [
        'id_usuario'   => $config['id_user'],
        'id_user_task' => $id_user_task,
        'args'         => serialize($parameters),
        'scheduled'    => $scheduled,
        'flag_delete'  => 1,
    ];

    $result = db_process_sql_insert('tuser_task_scheduled', $values);

    $report_type = $parameters[4];

    ui_print_result_message(
        $result,
        __('Your report has been planned, and the system will email you a '.$report_type.' file with the report as soon as its finished'),
        __('An error has ocurred')
    );
    echo '<br>';
}

// Other Checks for the edit the reports.
if ($idReport != 0) {
    $report = db_get_row_filter('treport', ['id_report' => $idReport]);
    $type_access_selected = reports_get_type_access($report);
    $edit = false;
    switch ($type_access_selected) {
        case 'group_view':
            $edit = check_acl(
                $config['id_user'],
                $report['id_group'],
                'RW'
            );
        break;

        case 'group_edit':
            $edit = check_acl(
                $config['id_user'],
                $report['id_group_edit'],
                'RW'
            );
        break;

        case 'user_edit':
            if ($config['id_user'] == $report['id_user']
                || is_user_admin($config['id_user'])
            ) {
                $edit = true;
            }
        break;

        default:
            // Default.
        break;
    }

    if (! $edit) {
        // The user that created the report should can delete it.
        // Despite its permissions.
        $delete_report_bypass = false;

        if ($action === 'delete_report') {
            if ($config['id_user'] == $report['id_user']
                || is_user_admin($config['id_user'])
            ) {
                $delete_report_bypass = true;
            }
        }

        if (!$delete_report_bypass) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access report builder'
            );
            include 'general/noaccess.php';
            exit;
        }
    }
}

$helpers = ($helpers ?? '');

switch ($action) {
    case 'sort_items':
        switch ($activeTab) {
            case 'list_items':
                $resultOperationDB = null;
                $position_to_sort = (int) get_parameter('position_to_sort', 1);
                $ids_serialize = (string) get_parameter(
                    'ids_items_to_sort',
                    ''
                );
                $move_to = (string) get_parameter('move_to', 'after');

                $countItems = db_get_sql(
                    '
					SELECT COUNT(id_rc)
					FROM treport_content
					WHERE id_report = '.$idReport
                );

                if (($countItems < $position_to_sort)
                    || ($position_to_sort < 1)
                ) {
                    $resultOperationDB = false;
                } else if (!empty($ids_serialize)) {
                    $ids = explode('|', $ids_serialize);
                    $items = db_get_all_rows_sql(
                        '
                        SELECT id_rc, `order`
                        FROM treport_content
                        WHERE id_report = '.$idReport.'
                        ORDER BY `order`'
                    );

                    if ($items === false) {
                        $items = [];
                    }

                    // Clean the repeated order values.
                    $order_temp = 1;
                    foreach ($items as $item) {
                        switch ($config['dbtype']) {
                            case 'mysql':
                                db_process_sql_update(
                                    'treport_content',
                                    ['`order`' => $order_temp],
                                    ['id_rc' => $item['id_rc']]
                                );
                            break;

                            case 'postgresql':
                            case 'oracle':
                                db_process_sql_update(
                                    'treport_content',
                                    ['"order"' => $order_temp],
                                    ['id_rc' => $item['id_rc']]
                                );
                            break;

                            default:
                                // Default.
                            break;
                        }

                        $order_temp++;
                    }


                    switch ($config['dbtype']) {
                        case 'mysql':
                            $items = db_get_all_rows_sql(
                                '
								SELECT id_rc, `order`
								FROM treport_content
								WHERE id_report = '.$idReport.'
								ORDER BY `order`'
                            );
                        break;

                        case 'oracle':
                        case 'postgresql':
                            $items = db_get_all_rows_sql(
                                '
								SELECT id_rc, "order"
								FROM treport_content
								WHERE id_report = '.$idReport.'
								ORDER BY "order"'
                            );
                        break;

                        default:
                            // Default.
                        break;
                    }

                    if ($items === false) {
                        $items = [];
                    }

                    $temp = [];
                    foreach ($items as $item) {
                        // Remove the contents from the block to sort.
                        if (array_search($item['id_rc'], $ids) === false) {
                            $temp[$item['order']] = $item['id_rc'];
                        }
                    }

                    $items = $temp;

                    $sorted_items = [];
                    foreach ($items as $pos => $id_unsort) {
                        if ($pos == $position_to_sort) {
                            if ($move_to == 'after') {
                                $sorted_items[] = $id_unsort;
                            }

                            foreach ($ids as $id) {
                                $sorted_items[] = $id;
                            }

                            if ($move_to != 'after') {
                                $sorted_items[] = $id_unsort;
                            }
                        } else {
                            $sorted_items[] = $id_unsort;
                        }
                    }

                    $items = $sorted_items;

                    foreach ($items as $order => $id) {
                        switch ($config['dbtype']) {
                            case 'mysql':
                                db_process_sql_update(
                                    'treport_content',
                                    ['`order`' => ($order + 1)],
                                    ['id_rc' => $id]
                                );
                            break;

                            case 'postgresql':
                            case 'oracle':
                                db_process_sql_update(
                                    'treport_content',
                                    ['"order"' => ($order + 1)],
                                    ['id_rc' => $id]
                                );
                            break;

                            default:
                                // Default.
                            break;
                        }
                    }

                    $resultOperationDB = true;
                } else {
                    $resultOperationDB = false;
                }
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'delete_items':
        $resultOperationDB = null;
        $ids_serialize = (string) get_parameter('ids_items_to_delete', '');

        if (!empty($ids_serialize)) {
            $sql = 'DELETE FROM treport_content
                    WHERE id_rc IN ('.$ids_serialize.')';
            $resultOperationDB = db_process_sql($sql);
        } else {
            $resultOperationDB = false;
        }

        header(
            sprintf(
                'Location: %sindex.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report=%d',
                $config['homeurl'],
                $idReport
            )
        );
    break;

    case 'delete_items_pos':
        $resultOperationDB = null;
        $position_to_delete = (int) get_parameter('position_to_delete', 1);
        $pos_delete = (string) get_parameter('delete_m', 'below');

        $countItems = db_get_sql(
            'SELECT COUNT(id_rc)
			FROM treport_content WHERE id_report = '.$idReport
        );

        if (($countItems < $position_to_delete) || ($position_to_delete < 1)) {
            $resultOperationDB = false;
        } else {
            $sql = 'SELECT id_rc
                    FROM treport_content
                    WHERE id_report='.$idReport." ORDER BY '`order`'";
            $items = db_get_all_rows_sql($sql);
            switch ($pos_delete) {
                case 'above':
                    if ($position_to_delete == 1) {
                        $resultOperationDB = false;
                    } else {
                        $i = 1;
                        foreach ($items as $key => $item) {
                            if ($i < $position_to_delete) {
                                $resultOperationDB = db_process_sql_delete(
                                    'treport_content',
                                    ['id_rc' => $item['id_rc']]
                                );
                            }

                            $i++;
                        }
                    }
                break;

                case 'below':
                    if ($position_to_delete == $countItems) {
                        $resultOperationDB = false;
                    } else {
                        $i = 1;
                        foreach ($items as $key => $item) {
                            if ($i > $position_to_delete) {
                                $resultOperationDB = db_process_sql_delete(
                                    'treport_content',
                                    ['id_rc' => $item['id_rc']]
                                );
                            }

                            $i++;
                        }
                    }
                break;

                default:
                    // Default.
                break;
            }
        }
    break;

    case 'copy_report':
    case 'delete_report':
    case 'list':
        $buttons = [
            'list_reports' => [
                'active' => false,
                'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image(
                    'images/logs@svg.svg',
                    true,
                    [
                        'title' => __('Reports list'),
                        'class' => 'main_menu_icon invert_filter',
                    ]
                ).'</a>',
            ],
        ];

        if ($enterpriseEnable) {
            $buttons = reporting_enterprise_add_main_Tabs($buttons);
        }

        $subsection = '';
        $helpers = '';
        switch ($activeTab) {
            case 'main':
                $buttons['list_reports']['active'] = true;
                $subsection = __('Custom reporting');
            break;

            default:
                $data_tab = reporting_enterprise_add_subsection_main(
                    $activeTab,
                    $buttons
                );

                $subsection = $data_tab['subsection'];
                $buttons = $data_tab['buttons'];
                $helpers = $data_tab['helper'];
            break;
        }

        // Header.
        ui_print_standard_header(
            __('List of reports'),
            'images/op_reporting.png',
            false,
            '',
            false,
            $buttons,
            [
                [
                    'link'  => '',
                    'label' => __('Reporting'),
                ],
                [
                    'link'  => '',
                    'label' => __('Custom reports'),
                ],
            ]
        );

        if ($action == 'delete_report') {
            $delete = false;
            switch ($type_access_selected) {
                case 'group_view':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $delete = true;
                        // Owner can delete.
                    } else {
                        $delete = check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RM'
                        );
                    }
                break;

                case 'group_edit':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $delete = true;
                        // Owner can delete.
                    } else {
                        $delete = (bool) check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RM'
                        );
                    }
                break;

                case 'user_edit':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $delete = true;
                    }
                break;

                default:
                    // Default.
                break;
            }

            if ($delete === false && empty($type_access_selected) === false) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access report builder deletion'
                );
                include 'general/noaccess.php';
                exit;
            }

            $result = reports_delete_report($idReport);
            $auditMessage = ($result !== false) ? 'Delete report' : 'Fail try to delete report';

            db_pandora_audit(
                AUDIT_LOG_REPORT_MANAGEMENT,
                sprintf('%s #%s', $auditMessage, $idReport)
            );

            ui_print_result_message(
                $result,
                __('Successfully deleted'),
                __('Could not be deleted')
            );
        }

        if ($action === 'copy_report') {
            $copy = false;
            switch ($type_access_selected) {
                case 'group_view':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $copy = true;
                        // Owner can delete.
                    } else {
                        $copy = check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RM'
                        );
                    }
                break;

                case 'group_edit':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $copy = true;
                        // Owner can delete.
                    } else {
                        $copy = check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RM'
                        );
                    }
                break;

                case 'user_edit':
                    if ($config['id_user'] == $report['id_user']
                        || is_user_admin($config['id_user'])
                    ) {
                        $copy = true;
                    }
                break;

                default:
                    // Default.
                break;
            }

            if (! $copy && empty($type_access_selected) === false) {
                db_pandora_audit(
                    AUDIT_LOG_ACL_VIOLATION,
                    'Trying to access report builder copy'
                );
                include 'general/noaccess.php';
                exit;
            }

            $result = reports_copy_report($idReport);
            $auditMessage = ((bool) $result === true) ? 'Copy report' : 'Fail try to copy report';
            db_pandora_audit(
                AUDIT_LOG_REPORT_MANAGEMENT,
                sprintf('%s #%s', $auditMessage, $idReport)
            );

            ui_print_result_message(
                $result,
                __('Successfully copied'),
                __('Could not be copied')
            );
        }

        $id_group = (int) get_parameter('id_group', 0);
        $search = trim(get_parameter('search', ''));

        $search_sql = '';

        if ($search !== '') {
            $search_name = "(name LIKE '%".$search."%' OR description LIKE '%".$search."%')";
        }

        $table_aux = new stdClass();
        $table_aux->width = '100%';
        $table_aux->class = 'filter-table-adv';
        $table_aux->size[0] = '30%';
        $table_aux->size[1] = '30%';
        $table_aux->size[2] = '30%';

        $table_aux->data[0][0] = html_print_label_input_block(
            __('Group'),
            html_print_select_groups(
                false,
                $access,
                true,
                'id_group',
                $id_group,
                '',
                '',
                '',
                true,
                false,
                true,
                '',
                false,
                '',
                false,
                false,
                'id_grupo'
            )
        );

        $table_aux->data[0][1] = html_print_label_input_block(
            __('Free text for search: ').ui_print_help_tip(
                __('Search by report name or description, list matches.'),
                true
            ),
            html_print_input_text(
                __('search'),
                $search,
                '',
                30,
                '',
                true
            )
        );

        $url_rb = 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder';
        $searchForm = '<form action="'.$url_rb.'&id_group='.$id_group.'&pure='.$pure.'" method="post">';
        $searchForm .= html_print_table($table_aux, true);
        $searchForm .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Filter'),
                    'search_submit',
                    false,
                    [
                        'mode' => 'mini',
                        'icon' => 'search',
                    ],
                    true
                ),
            ],
            true
        );
        $searchForm .= '</form>';

        ui_toggle(
            $searchForm,
            '<span class="subsection_header_title">'.__('Filters').'</span>',
            'filter_form',
            '',
            false,
            false,
            '',
            'white-box-content',
            'box-flat white_table_graph fixed_filter_bar'
        );

        ui_require_jquery_file('pandora.controls');
        ui_require_jquery_file('ajaxqueue');
        ui_require_jquery_file('bgiframe');
        ui_require_jquery_file('autocomplete');

        // Show only selected groups.
        if ($id_group > 0) {
            $group = [$id_group => $id_group];
        } else {
            $group = false;
        }

        $own_info = get_user_info($config['id_user']);
        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'RM') || check_acl($config['id_user'], 0, 'RR')) {
            $return_all_group = true;
        } else {
            $return_all_group = false;
        }

        $filter = ['order' => 'name'];

        if ($search !== '') {
            $filter[] = $search_name;
        }

        // Fix : group filter was not working
        // Show only selected groups.
        if ($id_group > 0) {
            $group = [$id_group => $id_group];
            $filter['id_group'] = $id_group;
        } else {
            $group = false;
        }

        // Filter normal and metaconsole reports.
        if ($config['metaconsole'] == 1 && defined('METACONSOLE')) {
            $filter['metaconsole'] = 1;
        } else {
            $filter['metaconsole'] = 0;
        }

        $reports = reports_get_reports(
            $filter,
            [
                'name',
                'id_report',
                'description',
                'private',
                'id_user',
                'id_group',
                'non_interactive',
            ],
            $return_all_group,
            $access,
            $group
        );

        $total_reports = (int) count(
            reports_get_reports(
                $filter,
                ['name'],
                $return_all_group,
                $access,
                $group
            )
        );

        if (count($reports)) {
            $filters = [
                'search'   => $search,
                'id_group' => $id_group,
            ];
            $filtersStr = http_build_query($filters, '', '&amp;');
            $url = 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder';
            $url .= '&'.$filtersStr;
            // ui_pagination($total_reports, $url, $offset, $pagination);
            $table = new stdClass();
            $table->id = 'report_list';
            $table->styleTable = 'margin: 0 10px;';
            $table->class = 'info_table';
            $table->cellpadding = 0;
            $table->cellspacing = 0;

            $table->head = [];
            $table->align = [];
            $table->headstyle = [];
            $table->style = [];

            $table->align[2] = 'left';
            $table->align[3] = 'left';
            $table->align[4] = 'left';
            $table->data = [];
            $table->head[0] = __('Report name');
            $table->head[1] = __('Description');
            $table->head[2] = __('HTML');
            $table->head[3] = __('XML');
            $table->size[0] = '50%';
            $table->size[1] = '20%';
            $table->size[2] = '2%';
            $table->headstyle[2] = 'min-width: 35px;text-align: left;';
            $table->size[3] = '2%';
            $table->headstyle[3] = 'min-width: 35px;text-align: left;';
            $table->size[4] = '2%';
            $table->headstyle[4] = 'min-width: 35px;text-align: left;';

            $next = 4;
            // Calculate dinamically the number of the column.
            if (enterprise_hook('load_custom_reporting_1', [$table]) !== ENTERPRISE_NOT_HOOK) {
                $next = 7;
            }

            $table->size[$next] = '2%';
            $table->style[$next] = 'text-align: left;';

            $table->headstyle[($next + 2)] = 'min-width: 130px; text-align:right;';
            $table->style[($next + 2)] = 'text-align: right;';


            // Admin options only for RM flag.
            if (check_acl($config['id_user'], 0, 'RM')) {
                $table->head[$next] = __('Private');
                $table->headstyle[$next] = 'min-width: 40px;text-align: left;';
                $table->size[$next] = '2%';
                if (is_metaconsole() === true) {
                    $table->align[$next] = '';
                } else {
                    $table->align[$next] = 'left';
                }

                $next++;
                $table->head[$next] = __('Group');
                $table->headstyle[$next] = 'min-width: 40px;text-align: left;';
                $table->size[$next] = '2%';
                $table->align[$next] = 'left';

                $next++;
                $op_column = false;
                if (is_metaconsole() === false) {
                    $op_column = true;
                    $table->head[$next] = '<span title="Operations">'.__('Op.').'</span>'.html_print_checkbox(
                        'all_delete',
                        0,
                        false,
                        true,
                        false,
                        'check_all_checkboxes();'
                    );
                }

                // $table->size = array ();
                $table->size[$next] = '10%';
                $table->align[$next] = 'right';
            } else {
                $table->size[1] = '40%';
            }

            $columnview = false;

            $reports = array_slice($reports, $offset, $pagination);

            foreach ($reports as $report) {
                if (!is_user_admin($config['id_user'])) {
                    if ($report['private']
                        && $report['id_user'] != $config['id_user']
                    ) {
                        if (!check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RR'
                        )
                            && !check_acl(
                                $config['id_user'],
                                $report['id_group'],
                                'RW'
                            )
                            && !check_acl(
                                $config['id_user'],
                                $report['id_group'],
                                'RM'
                            )
                        ) {
                            continue;
                        }
                    }

                    if (!check_acl(
                        $config['id_user'],
                        $report['id_group'],
                        'RR'
                    )
                        && !check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RW'
                        )
                        && !check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RM'
                        )
                    ) {
                        continue;
                    }
                }

                $data = [];

                if (check_acl_restricted_all($config['id_user'], $report['id_group'], 'RW')
                    || check_acl_restricted_all($config['id_user'], $report['id_group'], 'RM')
                ) {
                    $data[0] = '<a href="'.$config['homeurl'].'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit&id_report='.$report['id_report'].'&pure='.$pure.'">'.ui_print_truncate_text($report['name'], 70).'</a>';
                } else {
                    $data[0] = ui_print_truncate_text($report['name'], 70);
                }


                $data[1] = ui_print_truncate_text($report['description'], 70);

                // Remove html and xml button if items are larger than limit.
                $item_count = db_get_num_rows(
                    'SELECT * FROM treport_content
                    WHERE id_report='.$report['id_report']
                );
                $report['overload'] = $item_count >= $config['report_limit'];
                if ($report['overload']) {
                    $data[2] = html_print_image(
                        'images/application_not_writable.png',
                        true,
                        ['title' => __('This report exceeds the item limit for realtime operations')]
                    );
                    $data[3] = null;
                } else if ((bool) $report['non_interactive'] === false) {
                    $data[2] = html_print_anchor(
                        [
                            'href'    => $config['homeurl'].'index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'&pure='.$pure,
                            'content' => html_print_image(
                                'images/file-html.svg',
                                true,
                                [
                                    'title' => __('HTML view'),
                                    'class' => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );

                    $data[3] = html_print_anchor(
                        [
                            'onClick' => 'blockResubmit($(this))',
                            'href'    => ui_get_full_url(false, false, false, false).'ajax.php?page='.$config['homedir'].'/operation/reporting/reporting_xml&id='.$report['id_report'],
                            'content' => html_print_image(
                                'images/file-xml.svg',
                                true,
                                [
                                    'title' => __('Export to XML'),
                                    'class' => 'invert_filter main_menu_icon',
                                ]
                            ),
                        ],
                        true
                    );
                    // I chose ajax.php because it's supposed
                    // to give XML anyway.
                } else {
                    $data[2] = html_print_image(
                        'images/file-html.svg',
                        true,
                        [
                            'title' => __('HTML view'),
                            'class' => 'invert_filter main_menu_icon alpha50',
                        ]
                    );
                    $data[3] = html_print_image(
                        'images/file-xml.svg',
                        true,
                        [
                            'title' => __('Export to XML'),
                            'class' => 'invert_filter main_menu_icon alpha50',
                        ]
                    );
                }

                // Calculate dinamically the number of the column.
                $next = 4;
                if (enterprise_hook('load_custom_reporting_2') !== ENTERPRISE_NOT_HOOK) {
                    $next = 7;
                }

                // Admin options only for RM flag.
                if (check_acl($config['id_user'], 0, 'RM')) {
                    if ($report['private'] == 1) {
                        $data[$next] = __('Yes');
                    } else {
                        $data[$next] = __('No');
                    }

                    $next++;


                    $data[$next] = ui_print_group_icon(
                        $report['id_group'],
                        true,
                        '',
                        '',
                        true,
                        false,
                        false,
                        'invert_filter'
                    );
                    $next++;
                }

                $type_access_selected = reports_get_type_access($report);
                $edit = false;
                $delete = false;

                switch ($type_access_selected) {
                    case 'group_view':
                        $edit = check_acl_restricted_all(
                            $config['id_user'],
                            $report['id_group'],
                            'RW'
                        );
                        $delete = $edit ||
                            is_user_admin($config['id_user']) ||
                            $config['id_user'] == $report['id_user'];
                    break;

                    case 'group_edit':
                        $edit = check_acl_restricted_all(
                            $config['id_user'],
                            $report['id_group_edit'],
                            'RW'
                        );
                        $delete = $edit ||
                            is_user_admin($config['id_user']) ||
                            $config['id_user'] == $report['id_user'];
                    break;

                    case 'user_edit':
                        if ($config['id_user'] == $report['id_user']
                            || is_user_admin($config['id_user'])
                        ) {
                            $edit = true;
                            $delete = true;
                        }
                    break;

                    default:
                        // Default.
                    break;
                }

                if ($edit || $delete) {
                    $columnview = true;
                    // $table->cellclass[][$next] = 'table_action_buttons';
                    $tableActionButtons = [];
                    if (!isset($table->head[$next])) {
                        $table->head[$next] = '<span title="Operations">'.__('Op.').'</span>'.html_print_checkbox('all_delete', 0, false, true, false);
                        $table->size = [];
                    }

                    if ($edit) {
                        $tableActionButtons[] = '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit&pure='.$pure.'" class="inline_line">';
                        $tableActionButtons[] = html_print_input_image(
                            'edit',
                            'images/edit.svg',
                            1,
                            '',
                            true,
                            [
                                'title' => __('Edit'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $tableActionButtons[] = html_print_input_hidden(
                            'id_report',
                            $report['id_report'],
                            true
                        );
                        $tableActionButtons[] = '</form>';
                    }

                    $tableActionButtons[] = '<form method="post" style="display: inline"; onsubmit="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
                    $tableActionButtons[] = html_print_input_hidden(
                        'id_report',
                        $report['id_report'],
                        true
                    );
                    $tableActionButtons[] = html_print_input_hidden(
                        'action',
                        'copy_report',
                        true
                    );
                    $tableActionButtons[] = html_print_input_image(
                        'dup',
                        'images/copy.svg',
                        1,
                        '',
                        true,
                        [
                            'title' => __('Duplicate'),
                            'class' => 'main_menu_icon invert_filter',
                        ]
                    );
                    $tableActionButtons[] = '</form> ';

                    if ($delete) {
                        $tableActionButtons[] = '<form method="post" class="inline_line" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
                        $tableActionButtons[] = html_print_input_image(
                            'delete',
                            'images/delete.svg',
                            1,
                            'margin-right: 10px;',
                            true,
                            [
                                'title' => __('Delete'),
                                'class' => 'main_menu_icon invert_filter',
                            ]
                        );
                        $tableActionButtons[] = html_print_input_hidden(
                            'id_report',
                            $report['id_report'],
                            true
                        );
                        $tableActionButtons[] = html_print_input_hidden(
                            'action',
                            'delete_report',
                            true
                        );

                        $tableActionButtons[] = html_print_checkbox_extended(
                            'massive_report_check',
                            $report['id_report'],
                            false,
                            false,
                            '',
                            [ 'input_class' => 'check_delete' ],
                            true
                        );

                        $tableActionButtons[] = '</form>';
                    }

                    $data[$next] = html_print_div(
                        [
                            'class'   => 'table_action_buttons',
                            'style'   => 'padding-top: 8px;',
                            'content' => implode('', $tableActionButtons),
                        ],
                        true
                    );
                } else {
                    if ($op_column) {
                        $data[$next] = '';
                    }
                }

                array_push($table->data, $data);
            }

            html_print_table($table);
            $tablePagination = ui_pagination(
                $total_reports,
                $url,
                $offset,
                $pagination,
                true,
                'offset',
                false,
            );
        } else {
            ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __('No data found.'),
                ]
            );
        }

        if (check_acl($config['id_user'], 0, 'RW')
            || check_acl($config['id_user'], 0, 'RM')
        ) {
            $buttonsOutput = [];
            // Create form.
            $buttonsOutput[] = '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=new&pure='.$pure.'">';
            $buttonsOutput[] = html_print_submit_button(
                __('Create report'),
                'create',
                false,
                [ 'icon' => 'next' ],
                true
            );
            $buttonsOutput[] = '</form>';

            // Delete form.
            $buttonsOutput[] = '<form class="inline_line mrgn_right_10px" id="massive_report_form" method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=delete">';

            foreach ($reports as $report) {
                $buttonsOutput[] = '<input class="massive_report_form_elements" id="hidden-id_report_'.$report['id_report'].'" name="id_report[]" type="hidden" disabled value="'.$report['id_report'].'">';
            }

            if (empty($report) === false) {
                $buttonsOutput[] = html_print_input_hidden('action', 'delete_report', true);
                $buttonsOutput[] = html_print_submit_button(
                    __('Delete'),
                    'delete_btn',
                    false,
                    [
                        'icon' => 'delete',
                        'mode' => 'secondary',
                    ],
                    true
                );
            }

            $buttonsOutput[] = '</form>';

            echo html_print_action_buttons(
                implode('', $buttonsOutput),
                [
                    'type'          => 'form_action',
                    'right_content' => $tablePagination,
                ],
                true
            );
        }
    return;

        break;
    case 'new':
        switch ($activeTab) {
            case 'main':
                $reportName = '';
                $idGroupReport = null;
                // All groups.
                $description = '';
                $resultOperationDB = null;
                $report_id_user = 0;
                $type_access_selected = reports_get_type_access(false);
                $id_group_edit = 0;
                $cover_page_render = true;
                $index_render = true;
            break;

            case 'item_editor':
                $resultOperationDB = null;
                $report = db_get_row_filter(
                    'treport',
                    ['id_report' => $idReport]
                );

                $reportName = $report['name'];
                $idGroupReport = $report['id_group'];
                $description = $report['description'];
            break;

            default:
                // Default.
            break;
        }
    break;

    case 'update':
    case 'save':
        switch ($activeTab) {
            case 'main':
                $reportName = get_parameter('name');
                $idGroupReport = get_parameter('id_group');
                $description = get_parameter('description');
                $type_access_selected = get_parameter(
                    'type_access',
                    'group_view'
                );
                $id_group_edit_param = (int) get_parameter('id_group_edit', 0);
                $report_id_user = get_parameter('report_id_user');
                $non_interactive = get_parameter('non_interactive', 0);

                $cover_page_render = get_parameter_switch(
                    'cover_page_render',
                    0
                );
                $index_render = get_parameter_switch('index_render', 0);

                $custom_font = $config['custom_report_front_font'];

                switch ($type_access_selected) {
                    case 'group_view':
                        $id_group_edit = 0;
                        $private = 0;
                    break;

                    case 'group_edit':
                        $id_group_edit = $id_group_edit_param;
                        $private = 0;
                    break;

                    case 'user_edit':
                        $id_group_edit = 0;
                        $private = 1;
                    break;

                    default:
                        // Default.
                    break;
                }

                if ($action == 'update') {
                    if ($reportName != '' && $idGroupReport != '') {
                        $new_values = [
                            'name'              => $reportName,
                            'id_group'          => $idGroupReport,
                            'description'       => $description,
                            'private'           => $private,
                            'id_group_edit'     => $id_group_edit,
                            'non_interactive'   => $non_interactive,
                            'cover_page_render' => $cover_page_render,
                            'index_render'      => $index_render,
                        ];


                        $report = db_get_row_filter(
                            'treport',
                            ['id_report' => $idReport]
                        );
                        $report_id_user = $report['id_user'];
                        if ($report_id_user != $config['id_user']
                            && !is_user_admin($config['id_user'])
                        ) {
                            unset($new_values['private']);
                            unset($new_values['id_group_edit']);
                        }

                        $resultOperationDB = (bool) db_process_sql_update(
                            'treport',
                            $new_values,
                            ['id_report' => $idReport]
                        );

                        ui_update_name_fav_element($idReport, 'Reporting', $new_values['name']);

                        $auditMessage = ($resultOperationDB === true) ? 'Update report' : 'Fail try to update report';
                        db_pandora_audit(
                            AUDIT_LOG_REPORT_MANAGEMENT,
                            sprintf('%s #%s', $auditMessage, $idReport)
                        );
                    } else {
                        $resultOperationDB = false;
                    }

                    $action = 'edit';
                } else if ($action === 'save') {
                    if ($reportName != '' && $idGroupReport != '') {
                        // This flag allow to differentiate
                        // between normal console and metaconsole reports.
                        $metaconsole_report = (int) is_metaconsole();

                        if ($config['custom_report_front']) {
                            $logo = $config['custom_report_front_logo'];
                            $header = $config['custom_report_front_header'];
                            $first_page = $config['custom_report_front_firstpage'];
                            $footer = $config['custom_report_front_footer'];
                        } else {
                            $start_url = ui_get_full_url(
                                false,
                                false,
                                false,
                                false
                            );
                            $first_page = '&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;img&#x20;src=&quot;'.$start_url.'/images/pandora_report_logo.png&quot;&#x20;alt=&quot;&quot;&#x20;width=&quot;800&quot;&#x20;/&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&amp;nbsp;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;xx-large;&quot;&gt;&#40;_REPORT_NAME_&#41;&lt;/span&gt;&lt;/p&gt;&#x0d;&#x0a;&lt;p&#x20;style=&quot;text-align:&#x20;center;&quot;&gt;&lt;span&#x20;style=&quot;font-size:&#x20;large;&quot;&gt;&#40;_DATETIME_&#41;&lt;/span&gt;&lt;/p&gt;';
                            $logo = null;
                            $header = null;
                            $footer = null;
                        }

                        $idOrResult = db_process_sql_insert(
                            'treport',
                            [
                                'name'              => $reportName,
                                'id_group'          => $idGroupReport,
                                'description'       => $description,
                                'first_page'        => $first_page,
                                'private'           => $private,
                                'id_group_edit'     => $id_group_edit,
                                'id_user'           => $config['id_user'],
                                'metaconsole'       => $metaconsole_report,
                                'non_interactive'   => $non_interactive,
                                'custom_font'       => $custom_font,
                                'custom_logo'       => $logo,
                                'header'            => $header,
                                'footer'            => $footer,
                                'cover_page_render' => $cover_page_render,
                                'index_render'      => $index_render,
                            ]
                        );

                        $auditMessage = ((bool) $idOrResult === true) ? sprintf('Create report #%s', $idOrResult) : 'Fail try to create report';
                        db_pandora_audit(
                            AUDIT_LOG_REPORT_MANAGEMENT,
                            $auditMessage
                        );
                    } else {
                        $idOrResult = false;
                    }

                    if ($idOrResult === false) {
                        $resultOperationDB = false;
                    } else {
                        $resultOperationDB = true;
                        $idReport = $idOrResult;
                        $report_id_user = $config['id_user'];
                    }

                    $action = ($resultOperationDB) ? 'edit' : 'new';
                }
            break;

            case 'item_editor':
                $resultOperationDB = null;
                $report = db_get_row_filter(
                    'treport',
                    ['id_report' => $idReport]
                );

                $reportName = $report['name'];
                $idGroupReport = $report['id_group'];
                $description = $report['description'];
                $good_format = false;
                switch ($action) {
                    case 'update':
                        $values = [];
                        $server_id = get_parameter('server_id', 0);
                        if (is_metaconsole() === true
                            && empty($server_id) === false
                        ) {
                            $connection = metaconsole_get_connection_by_id(
                                $server_id
                            );
                            metaconsole_connect($connection);
                            $values['server_name'] = $connection['server_name'];
                        }

                        $values['id_report'] = $idReport;
                        $values['description'] = get_parameter('description');
                        $values['type'] = get_parameter('type', null);
                        $values['recursion'] = get_parameter('recursion', null);
                        $values['show_extended_events'] = get_parameter(
                            'include_extended_events',
                            null
                        );

                        $label = get_parameter('label', '');

                        $id_agent = get_parameter('id_agent');
                        $id_agent_module = get_parameter('id_agent_module');

                        // Add macros name.
                        $name_it = (string) get_parameter('name');

                        $agent_description = agents_get_description($id_agent);
                        $agent_group = agents_get_agent_group($id_agent);
                        $agent_address = agents_get_address($id_agent);
                        $agent_alias = agents_get_alias($id_agent);
                        $module_name = modules_get_agentmodule_name(
                            $id_agent_module
                        );

                        $module_description = modules_get_agentmodule_descripcion(
                            $id_agent_module
                        );

                        $items_label = [
                            'type'               => get_parameter('type'),
                            'id_agent'           => $id_agent,
                            'id_agent_module'    => $id_agent_module,
                            'agent_description'  => $agent_description,
                            'agent_group'        => $agent_group,
                            'agent_address'      => $agent_address,
                            'agent_alias'        => $agent_alias,
                            'module_name'        => $module_name,
                            'module_description' => $module_description,
                        ];

                        $values['name'] = $name_it;

                        $values['landscape'] = get_parameter('landscape');
                        $values['pagebreak'] = get_parameter('pagebreak');

                        /*
                            Added support for projection graphs,
                            prediction date and SLA reports
                            'top_n_value','top_n' and 'text'
                            fields will be reused for these types of report
                        */

                        switch ($values['type']) {
                            case 'projection_graph':
                                $values['period'] = get_parameter('period1');
                                $values['top_n_value'] = get_parameter(
                                    'period2'
                                );
                                $values['text'] = get_parameter('text');
                                $good_format = true;
                            break;

                            case 'event_report_log':
                                $agents_to_report = get_parameter('id_agents3');
                                $source = get_parameter('source', '');
                                $search = get_parameter('search', '');
                                $full_text = (integer) get_parameter('full_text', 0);
                                $log_number = get_parameter('log_number', '');

                                $es['source'] = $source;
                                $es['id_agents'] = $agents_to_report;
                                $es['search'] = $search;
                                $es['full_text'] = $full_text;
                                $es['log_number'] = $log_number;

                                $values['external_source'] = json_encode($es);
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'prediction_date':
                                $values['period'] = get_parameter('period1');
                                $values['top_n'] = get_parameter(
                                    'radiobutton_max_min_avg'
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $interval_max = get_parameter('max_interval');
                                $interval_min = get_parameter('min_interval');
                                // Checks intervals fields.
                                if (preg_match(
                                    '/^(\-)*[0-9]*\.?[0-9]+$/',
                                    $interval_max
                                )
                                    && preg_match(
                                        '/^(\-)*[0-9]*\.?[0-9]+$/',
                                        $interval_min
                                    )
                                ) {
                                    $good_format = true;
                                }

                                $intervals = get_parameter('max_interval').';';
                                $intervals .= get_parameter('min_interval');
                                $values['text'] = $intervals;
                            break;

                            case 'availability_graph':
                                $values['summary'] = get_parameter(
                                    'summary',
                                    0
                                );
                            case 'SLA_monthly':
                            case 'SLA_weekly':
                            case 'SLA_hourly':
                            case 'SLA_services':
                            case 'SLA':
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter(
                                    'combo_sla_sort_options',
                                    0
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $values['text'] = get_parameter('text');
                                $values['show_graph'] = get_parameter(
                                    'combo_graph_options'
                                );
                                $values['failover_mode'] = get_parameter(
                                    'failover_mode',
                                    0
                                );
                                $values['failover_type'] = get_parameter(
                                    'failover_type',
                                    REPORT_FAILOVER_TYPE_NORMAL
                                );

                                $good_format = true;
                            break;

                            case 'agent_module':
                            case 'agent_module_status':
                                $agents_to_report_text = get_parameter('id_agents2-multiple-text', '');
                                $modules_to_report_text = get_parameter('module-multiple-text', '');

                                // Decode json check modules.
                                $agents_to_report = json_decode(
                                    io_safe_output($agents_to_report_text),
                                    true
                                );
                                $modules_to_report = json_decode(
                                    io_safe_output($modules_to_report_text),
                                    true
                                );

                                $es['module'] = get_same_modules_all(
                                    $agents_to_report,
                                    $modules_to_report
                                );

                                // Encode json modules and agents.
                                $es['module'] = base64_encode(json_encode($es['module']));
                                $es['id_agents'] = base64_encode(json_encode($agents_to_report));
                                $es['show_type'] = get_parameter('show_type', 0);

                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'alert_report_actions':
                                $alert_templates_to_report = get_parameter('alert_templates');
                                $alert_actions_to_report = get_parameter('alert_actions');
                                $show_summary = get_parameter('show_summary', 0);
                                $group_by = get_parameter('group_by');
                                $only_data = get_parameter('only_data', 0);
                                $agents_to_report_text = get_parameter('id_agents2-multiple-text');
                                $modules_to_report_text = get_parameter('module-multiple-text', '');

                                // Decode json check modules.
                                $agents_to_report = json_decode(
                                    io_safe_output($agents_to_report_text),
                                    true
                                );
                                $modules_to_report = json_decode(
                                    io_safe_output($modules_to_report_text),
                                    true
                                );

                                $es['module'] = get_same_modules_all(
                                    $agents_to_report,
                                    $modules_to_report
                                );

                                // Encode json modules and agents.
                                $es['module'] = base64_encode(json_encode($es['module']));
                                $es['id_agents'] = base64_encode(json_encode($agents_to_report));

                                $es['templates'] = $alert_templates_to_report;
                                $es['actions'] = $alert_actions_to_report;
                                $es['show_summary'] = $show_summary;
                                $es['group_by'] = $group_by;
                                $es['only_data'] = $only_data;

                                $values['external_source'] = json_encode($es);

                                $values['period'] = get_parameter('period');
                                $values['lapse_calc'] = get_parameter(
                                    'lapse_calc'
                                );
                                $values['lapse'] = get_parameter('lapse');

                                $good_format = true;
                            break;

                            case 'inventory':
                                $values['period'] = 0;
                                $es['date'] = get_parameter('date');
                                $es['id_agents'] = get_parameter('id_agents');
                                $es['inventory_modules'] = get_parameter(
                                    'inventory_modules'
                                );
                                $es['inventory_regular_expression'] = get_parameter('inventory_regular_expression', '');
                                $description = get_parameter('description');
                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'inventory_changes':
                                $values['period'] = get_parameter('period');
                                $es['id_agents'] = get_parameter('id_agents');
                                $es['inventory_modules'] = get_parameter(
                                    'inventory_modules'
                                );
                                $description = get_parameter('description');
                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'netflow_area':
                            case 'netflow_data':
                            case 'netflow_summary':
                            case 'netflow_top_N':
                                $values['text'] = get_parameter(
                                    'netflow_filter'
                                );
                                $values['description'] = get_parameter(
                                    'description'
                                );
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter('resolution');
                                $values['top_n_value'] = get_parameter(
                                    'max_values'
                                );
                                $good_format = true;
                            break;

                            case 'availability':
                                // HACK it is saved in show_graph field.
                                // Show interfaces instead the modules.
                                $values['show_graph'] = get_parameter(
                                    'checkbox_show_address_agent'
                                );
                                $values['period'] = get_parameter(
                                    'period'
                                );
                                $values['total_time'] = get_parameter(
                                    'total_time'
                                );
                                $values['time_failed'] = get_parameter(
                                    'time_failed'
                                );
                                $values['time_in_ok_status'] = get_parameter(
                                    'time_in_ok_status'
                                );
                                $values['time_in_warning_status'] = get_parameter(
                                    'time_in_warning_status'
                                );
                                $values['time_in_unknown_status'] = get_parameter(
                                    'time_in_unknown_status'
                                );
                                $values['time_of_not_initialized_module'] = get_parameter(
                                    'time_of_not_initialized_module'
                                );
                                $values['time_of_downtime'] = get_parameter(
                                    'time_of_downtime'
                                );
                                $values['total_checks'] = get_parameter(
                                    'total_checks'
                                );
                                $values['checks_failed'] = get_parameter(
                                    'checks_failed'
                                );
                                $values['checks_in_ok_status'] = get_parameter(
                                    'checks_in_ok_status'
                                );
                                $values['checks_in_warning_status'] = get_parameter(
                                    'checks_in_warning_status'
                                );
                                $values['unknown_checks'] = get_parameter(
                                    'unknown_checks'
                                );
                                $values['agent_max_value'] = get_parameter(
                                    'agent_max_value'
                                );
                                $values['agent_min_value'] = get_parameter(
                                    'agent_min_value'
                                );
                                $values['failover_mode'] = get_parameter(
                                    'failover_mode',
                                    0
                                );
                                $values['failover_type'] = get_parameter(
                                    'failover_type',
                                    REPORT_FAILOVER_TYPE_NORMAL
                                );
                                $good_format = true;
                            break;

                            case 'simple_graph':
                                $values['graph_render'] = (int) get_parameter(
                                    'graph_render'
                                );
                            case 'simple_baseline_graph':
                                // HACK it is saved in show_graph field.
                                $values['show_graph'] = (int) get_parameter(
                                    'time_compare_overlapped'
                                );
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'network_interfaces_report':
                                $values['graph_render'] = (int) get_parameter(
                                    'graph_render'
                                );
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'custom_render':
                                $macro_custom_name = get_parameter('macro_custom_name', []);
                                $macro_custom_type = get_parameter('macro_custom_type', []);
                                $macro_custom_value = get_parameter('macro_custom_value', []);
                                $macro_custom_key = get_parameter('macro_custom_key', []);
                                $macros_definition = [];

                                foreach ($macro_custom_name as $key_macro => $value_macro) {
                                    $kl = (empty($macro_custom_key[$key_macro]) === true) ? 0 : $macro_custom_key[$key_macro];
                                    $macros_definition[$key_macro]['name'] = $value_macro;
                                    $macros_definition[$key_macro]['type'] = $macro_custom_type[$key_macro];


                                    if (is_array($macro_custom_value[$kl]) === true) {
                                        foreach ($macro_custom_value[$kl] as $k => $v) {
                                            $macros_definition[$key_macro][$k] = $v;
                                        }
                                    } else {
                                        $macros_definition[$key_macro]['value'] = $macro_custom_value[$key_macro];
                                    }
                                }

                                $values['macros_definition'] = json_encode($macros_definition);
                                $values['render_definition'] = get_parameter('render_definition', '');
                                $good_format = true;
                            break;

                            case 'min_value':
                            case 'max_value':
                            case 'avg_value':
                                $values['period'] = get_parameter('period');
                                $values['lapse_calc'] = get_parameter(
                                    'lapse_calc'
                                );
                                $values['lapse'] = get_parameter('lapse');
                                $values['visual_format'] = get_parameter(
                                    'visual_format'
                                );
                                $values['use_prefix_notation'] = get_parameter(
                                    'use_prefix_notation'
                                );
                                $good_format = true;
                            break;

                            case 'IPAM_network':
                                $values['ipam_network_filter'] = get_parameter('network_filter');
                                $values['ipam_alive_ips'] = get_parameter('alive_ip');
                                $values['ipam_ip_not_assigned_to_agent'] = get_parameter('agent_not_assigned_to_ip');
                                $good_format = true;
                            break;

                            default:
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter(
                                    'radiobutton_max_min_avg',
                                    0
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $values['text'] = get_parameter('text');
                                $values['show_graph'] = get_parameter(
                                    'combo_graph_options'
                                );
                                $values['use_prefix_notation'] = get_parameter(
                                    'use_prefix_notation'
                                );
                                $good_format = true;
                            break;
                        }

                        $values['id_agent'] = get_parameter('id_agent');
                        $values['id_gs'] = get_parameter('id_custom_graph');

                        $values['id_agent_module'] = '';
                        if (isset($values['type'])) {
                            if (($values['type'] == 'alert_report_agent')
                                || ($values['type'] == 'event_report_agent')
                                || ($values['type'] == 'agent_configuration')
                                || ($values['type'] == 'group_configuration')
                            ) {
                                $values['id_agent_module'] = '';
                            } else {
                                $values['id_agent_module'] = get_parameter(
                                    'id_agent_module'
                                );
                            }
                        } else {
                            $values['id_agent_module'] = get_parameter(
                                'id_agent_module'
                            );
                        }

                        $values['only_display_wrong'] = (int) get_parameter(
                            'checkbox_only_display_wrong',
                            0
                        );
                        $values['monday'] = get_parameter('monday', 0);
                        $values['tuesday'] = get_parameter('tuesday', 0);
                        $values['wednesday'] = get_parameter('wednesday', 0);
                        $values['thursday'] = get_parameter('thursday', 0);
                        $values['friday'] = get_parameter('friday', 0);
                        $values['saturday'] = get_parameter('saturday', 0);
                        $values['sunday'] = get_parameter('sunday', 0);
                        $values['compare_work_time'] = get_parameter(
                            'compare_work_time',
                            0
                        );
                        $values['total_time'] = get_parameter('total_time', 0);
                        $values['time_failed'] = get_parameter(
                            'time_failed',
                            0
                        );
                        $values['time_in_ok_status'] = get_parameter(
                            'time_in_ok_status',
                            0
                        );
                        $values['time_in_warning_status'] = get_parameter(
                            'time_in_warning_status',
                            0
                        );
                        $values['time_in_unknown_status'] = get_parameter(
                            'time_in_unknown_status',
                            0
                        );
                        $values['time_of_not_initialized_module'] = get_parameter(
                            'time_of_not_initialized_module',
                            0
                        );
                        $values['time_of_downtime'] = get_parameter(
                            'time_of_downtime',
                            0
                        );
                        $values['total_checks'] = get_parameter(
                            'total_checks',
                            0
                        );
                        $values['checks_failed'] = get_parameter(
                            'checks_failed',
                            0
                        );
                        $values['checks_in_ok_status'] = get_parameter(
                            'checks_in_ok_status',
                            0
                        );
                        $values['checks_in_warning_status'] = get_parameter(
                            'checks_in_warning_status',
                            0
                        );
                        $values['unknown_checks'] = get_parameter(
                            'unknown_checks',
                            0
                        );
                        $values['agent_max_value'] = get_parameter(
                            'agent_max_value',
                            0
                        );
                        $values['agent_min_value'] = get_parameter(
                            'agent_min_value',
                            0
                        );

                        $values['time_from'] = get_parameter(
                            'time_from'
                        );
                        $values['time_to'] = get_parameter('time_to');

                        $values['group_by_agent'] = get_parameter(
                            'checkbox_row_group_by_agent'
                        );
                        $values['show_resume'] = get_parameter(
                            'checkbox_show_resume'
                        );
                        $values['order_uptodown'] = get_parameter(
                            'radiobutton_order_uptodown'
                        );
                        $values['exception_condition'] = (int) get_parameter(
                            'exception_condition',
                            0
                        );
                        $values['exception_condition_value'] = get_parameter(
                            'exception_condition_value'
                        );
                        $values['id_module_group'] = get_parameter(
                            'combo_modulegroup'
                        );
                        $values['id_group'] = get_parameter('combo_group');

                        if ($values['server_name'] == '') {
                            $values['server_name'] = get_parameter(
                                'combo_server'
                            );
                        }

                        if ((($values['type'] == 'custom_graph')
                            || ($values['type'] == 'automatic_custom_graph'))
                            && ($values['id_gs'] == 0 || $values['id_gs'] == '')
                        ) {
                            $resultOperationDB = false;
                            break;
                        }

                        $show_summary_group = get_parameter(
                            'show_summary_group',
                            0
                        );

                        $server_multiple = get_parameter(
                            'server_multiple',
                            0
                        );
                        $filter_event_severity = get_parameter(
                            'filter_event_severity',
                            0
                        );
                        $filter_event_type = get_parameter(
                            'filter_event_type',
                            ''
                        );
                        $filter_event_status = get_parameter(
                            'filter_event_status',
                            0
                        );

                        $event_graph_by_agent = get_parameter(
                            'event_graph_by_agent',
                            0
                        );
                        $event_graph_by_user_validator = get_parameter(
                            'event_graph_by_user_validator',
                            0
                        );
                        $event_graph_by_criticity = get_parameter(
                            'event_graph_by_criticity',
                            0
                        );
                        $event_graph_validated_vs_unvalidated = get_parameter(
                            'event_graph_validated_vs_unvalidated',
                            0
                        );

                        $event_filter_search = get_parameter(
                            'filter_search',
                            ''
                        );

                        $event_filter_exclude = get_parameter(
                            'filter_exclude',
                            ''
                        );

                        // If metaconsole is activated.
                        if (is_metaconsole() === true) {
                            if (($values['type'] == 'custom_graph')
                                || ($values['type'] == 'automatic_custom_graph')
                            ) {
                                $id_gs = substr(
                                    $values['id_gs'],
                                    0,
                                    strpos($values['id_gs'], '|')
                                );
                                if ($id_gs !== false) {
                                    $server_name = strstr(
                                        $values['id_gs'],
                                        '|'
                                    );
                                    $values['id_gs'] = $id_gs;
                                    $values['server_name'] = substr(
                                        $server_name,
                                        1,
                                        strlen($server_name)
                                    );
                                }
                            }

                            // Get agent and server name.
                            $agent_name_server = io_safe_output(
                                get_parameter('agent')
                            );

                            if (isset($agent_name_server)) {
                                $separator_pos = strpos(
                                    $agent_name_server,
                                    '('
                                );

                                if (($separator_pos != false)
                                    || ($separator_pos != 0)
                                ) {
                                    $server_name = substr(
                                        $agent_name_server,
                                        $separator_pos
                                    );
                                    $server_name = str_replace(
                                        '(',
                                        '',
                                        $server_name
                                    );
                                    $server_name = str_replace(
                                        ')',
                                        '',
                                        $server_name
                                    );
                                    // Will update server_name variable.
                                    $values['server_name'] = trim($server_name);
                                    $agent_name = substr(
                                        $agent_name_server,
                                        0,
                                        $separator_pos
                                    );
                                }
                            }
                        }

                        if (($values['type'] == 'sql')
                            || ($values['type'] == 'sql_graph_hbar')
                            || ($values['type'] == 'sql_graph_vbar')
                            || ($values['type'] == 'sql_graph_pie')
                        ) {
                            $values['treport_custom_sql_id'] = get_parameter(
                                'id_custom'
                            );
                            if ($values['treport_custom_sql_id'] == 0) {
                                $sql = get_parameter('sql', '');
                                $values['external_source'] = $sql;
                            }

                            $values['historical_db'] = get_parameter(
                                'historical_db_check'
                            );
                            $values['top_n_value'] = get_parameter('max_items');

                            if ($values['type'] === 'sql_graph_hbar'
                                || ($values['type'] === 'sql_graph_vbar')
                                || ($values['type'] === 'sql_graph_pie')
                            ) {
                                $values['server_name'] = get_parameter('combo_server_sql');
                            } else {
                                $values['server_name'] = get_parameter('combo_server');
                            }

                            if ($sql !== '') {
                                if ($values['server_name'] === 'all') {
                                    $servers_connection = metaconsole_get_connections();
                                    foreach ($servers_connection as $key => $s) {
                                        $good_format = db_validate_sql($sql, $s['server_name']);
                                    }

                                    // Reconnected in nodo if exist.
                                    if ($server_id !== 0) {
                                        $connection = metaconsole_get_connection_by_id(
                                            $server_id
                                        );
                                        metaconsole_connect($connection);
                                    }
                                } else if ($server_id === 0) {
                                    // Connect with node if not exist conexion.
                                    $good_format = db_validate_sql($sql, (is_metaconsole() === true) ? $values['server_name'] : false);
                                } else {
                                    $good_format = db_validate_sql($sql);
                                }
                            }
                        } else if ($values['type'] == 'url') {
                            $values['external_source'] = get_parameter('url');
                        } else if ($values['type'] == 'event_report_group') {
                            $values['id_agent'] = get_parameter('group');
                        }

                        if ($values['type'] == 'sumatory') {
                            $values['uncompressed_module'] = get_parameter('uncompressed_module', 0);
                        }


                        $values['header_definition'] = get_parameter('header');
                        $values['column_separator'] = get_parameter('field');
                        $values['line_separator'] = get_parameter('line');

                        $values['current_month'] = get_parameter('current_month');

                        $style = [];
                        $style['show_in_same_row'] = get_parameter(
                            'show_in_same_row',
                            0
                        );
                        $style['hide_notinit_agents'] = get_parameter(
                            'hide_notinit_agents',
                            0
                        );
                        $style['priority_mode'] = get_parameter(
                            'priority_mode',
                            REPORT_PRIORITY_MODE_OK
                        );
                        $style['dyn_height'] = get_parameter(
                            'dyn_height',
                            230
                        );

                        switch ($values['type']) {
                            case 'event_report_group':
                                $style['server_multiple'] = json_encode(
                                    $server_multiple
                                );
                            case 'event_report_agent':
                            case 'event_report_module':
                                // Added for events items.
                                $style['show_summary_group'] = $show_summary_group;
                                $style['filter_event_severity'] = json_encode(
                                    $filter_event_severity
                                );
                                $style['filter_event_type'] = json_encode(
                                    $filter_event_type
                                );
                                $style['filter_event_status'] = json_encode(
                                    $filter_event_status
                                );

                                $custom_data_events = get_parameter_switch(
                                    'custom_data_events',
                                    0
                                );

                                $style['event_graph_by_agent'] = $event_graph_by_agent;
                                $style['event_graph_by_user_validator'] = $event_graph_by_user_validator;
                                $style['event_graph_by_criticity'] = $event_graph_by_criticity;
                                $style['event_graph_validated_vs_unvalidated'] = $event_graph_validated_vs_unvalidated;
                                $style['event_filter_search'] = $event_filter_search;
                                $style['event_filter_exclude'] = $event_filter_exclude;
                                $style['custom_data_events'] = $custom_data_events;


                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'simple_graph':
                                // Warning. We are using this column to hold
                                // this value to avoid the modification of the
                                // database for compatibility reasons.
                                $style['percentil'] = (int) get_parameter(
                                    'percentil'
                                );
                                $style['fullscale'] = (int) get_parameter(
                                    'fullscale'
                                );
                                $style['image_threshold'] = (int) get_parameter(
                                    'image_threshold'
                                );
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'network_interfaces_report':
                                $style['fullscale'] = (int) get_parameter(
                                    'fullscale'
                                );
                            break;

                            case 'module_histogram_graph':
                            case 'agent_configuration':
                            case 'alert_report_agent':
                            case 'alert_report_module':
                            case 'historical_data':
                            case 'sumatory':
                            case 'database_serialized':
                            case 'monitor_report':
                            case 'min_value':
                            case 'max_value':
                            case 'avg_value':
                            case 'projection_graph':
                            case 'prediction_date':
                            case 'simple_baseline_graph':
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'permissions_report':
                                $es['id_users'] = get_parameter('selected-select-id_users', 0);
                                $es['users_groups'] = get_parameter('users_groups', 0);
                                $es['select_by_group'] = get_parameter('select_by_group', 0);
                                $description = get_parameter('description');
                                $values['external_source'] = json_encode($es);
                            break;

                            case 'agents_inventory':
                                $es['agent_server_filter'] = get_parameter('agent_server_filter');
                                $es['agents_inventory_display_options'] = get_parameter('agents_inventory_display_options');
                                $es['agent_custom_field_filter'] = get_parameter('agent_custom_field_filter');
                                $es['agent_os_filter'] = get_parameter('agent_os_filter');
                                $es['agent_custom_fields'] = get_parameter('agent_custom_fields');
                                $es['agent_status_filter'] = get_parameter('agent_status_filter');
                                $es['agent_version_filter'] = get_parameter('agent_version_filter');
                                $es['agent_module_search_filter'] = get_parameter('agent_module_search_filter');
                                $es['agent_group_filter'] = get_parameter('agent_group_filter');
                                $es['agent_remote_conf'] = get_parameter('agent_remote_conf');

                                $values['external_source'] = json_encode($es);
                            break;

                            case 'modules_inventory':
                                $es['agent_server_filter'] = get_parameter('agent_server_filter');
                                $es['module_group'] = get_parameter('module_group');
                                $es['agent_group_filter'] = get_parameter('agent_group_filter');
                                $es['search_module_name'] = get_parameter('search_module_name');
                                $es['tags'] = get_parameter('tags');
                                $es['alias'] = get_parameter('alias', '');
                                $es['description_switch'] = get_parameter('description_switch', '');
                                $es['last_status_change'] = get_parameter('last_status_change', '');

                                $values['external_source'] = json_encode($es);
                            break;

                            case 'IPAM_network':
                                $es['network_filter'] = get_parameter('network_filter');
                                $es['alive_ip'] = get_parameter('alive_ip');
                                $es['agent_not_assigned_to_ip'] = get_parameter('agent_not_assigned_to_ip');

                                // $values['external_source'] = json_encode($es);
                            break;

                            case 'top_n':
                            case 'general':
                            case 'exception':
                                $text_agent = get_parameter('text_agent', '');
                                $text_agent_module = get_parameter('text_agent_module', '');
                                if (empty($text_agent) === false) {
                                    $style['text_agent'] = base64_encode($text_agent);
                                }

                                if (empty($text_agent_module) === false) {
                                    $style['text_agent_module'] = base64_encode($text_agent_module);
                                }
                            break;

                            default:
                                // Default.
                            break;
                        }

                        $values['style'] = io_safe_input(json_encode($style));

                        if (is_metaconsole()) {
                            metaconsole_restore_db();
                        }

                        if ($good_format) {
                            $resultOperationDB = db_process_sql_update(
                                'treport_content',
                                $values,
                                ['id_rc' => $idItem]
                            );
                        } else {
                            $resultOperationDB = false;
                        }
                    break;

                    case 'save':
                        $values = [];

                        $values['server_name'] = get_parameter('server_name');
                        $server_id = (int) get_parameter('server_id');
                        if ($server_id != 0) {
                            $connection = metaconsole_get_connection_by_id(
                                $server_id
                            );
                            metaconsole_connect($connection);
                            $values['server_name'] = $connection['server_name'];
                        }

                        $values['id_report'] = $idReport;
                        $values['type'] = get_parameter('type', null);
                        $values['description'] = get_parameter('description');
                        $label = get_parameter('label', '');

                        $values['recursion'] = get_parameter('recursion', null);
                        $values['show_extended_events'] = get_parameter(
                            'include_extended_events',
                            null
                        );

                        $id_agent = get_parameter('id_agent');
                        $id_agent_module = get_parameter('id_agent_module');

                        // Add macros name.
                        $name_it = (string) get_parameter('name');

                        $agent_description = agents_get_description($id_agent);
                        $agent_group = agents_get_agent_group($id_agent);
                        $agent_address = agents_get_address($id_agent);
                        $agent_alias = agents_get_alias($id_agent);
                        $module_name = modules_get_agentmodule_name(
                            $id_agent_module
                        );

                        $module_description = modules_get_agentmodule_descripcion(
                            $id_agent_module
                        );

                        if (is_metaconsole()) {
                            metaconsole_restore_db();
                        }

                        $items_label = [
                            'type'               => get_parameter('type'),
                            'id_agent'           => $id_agent,
                            'id_agent_module'    => $id_agent_module,
                            'agent_description'  => $agent_description,
                            'agent_group'        => $agent_group,
                            'agent_address'      => $agent_address,
                            'agent_alias'        => $agent_alias,
                            'module_name'        => $module_name,
                            'module_description' => $module_description,
                        ];

                        $values['name'] = $name_it;

                        $values['landscape'] = get_parameter('landscape');
                        $values['pagebreak'] = get_parameter('pagebreak');

                        // Support for projection graph, prediction date
                        // and SLA reports 'top_n_value', 'top_n' and 'text'
                        // fields will be reused for these types of report.
                        switch ($values['type']) {
                            case 'projection_graph':
                                $values['period'] = get_parameter('period1');
                                $values['top_n_value'] = get_parameter(
                                    'period2'
                                );
                                $values['text'] = get_parameter('text');
                                $good_format = true;
                            break;

                            case 'prediction_date':
                                $values['period'] = get_parameter('period1');
                                $values['top_n'] = get_parameter(
                                    'radiobutton_max_min_avg'
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $interval_max = get_parameter('max_interval');
                                $interval_min = get_parameter('min_interval');
                                // Checks intervals fields.
                                if (preg_match(
                                    '/^(\-)*[0-9]*\.?[0-9]+$/',
                                    $interval_max
                                )
                                    && preg_match(
                                        '/^(\-)*[0-9]*\.?[0-9]+$/',
                                        $interval_min
                                    )
                                ) {
                                    $good_format = true;
                                }

                                $intervals = get_parameter(
                                    'max_interval'
                                ).';'.get_parameter('min_interval');
                                $values['text'] = $intervals;
                            break;

                            case 'SLA':
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter(
                                    'combo_sla_sort_options',
                                    0
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $values['text'] = get_parameter('text');
                                $values['show_graph'] = get_parameter(
                                    'combo_graph_options'
                                );

                                $good_format = true;
                            break;

                            case 'inventory':
                                $values['period'] = 0;
                                $es['date'] = get_parameter('date');
                                $es['id_agents'] = get_parameter('id_agents');
                                $es['inventory_modules'] = get_parameter(
                                    'inventory_modules'
                                );
                                $es['inventory_regular_expression'] = get_parameter('inventory_regular_expression', '');
                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'event_report_log':
                                $agents_to_report = get_parameter('id_agents3');
                                $source = get_parameter('source', '');
                                $search = get_parameter('search', '');
                                $full_text = (integer) get_parameter('full_text', 0);
                                $log_number = get_parameter('log_number', '');

                                $es['source'] = $source;
                                $es['id_agents'] = $agents_to_report;
                                $es['search'] = $search;
                                $es['full_text'] = $full_text;
                                $es['log_number'] = $log_number;

                                $values['external_source'] = json_encode($es);
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'agent_module':
                            case 'agent_module_status':
                                $agents_to_report_text = get_parameter('id_agents2-multiple-text');
                                $modules_to_report_text = get_parameter('module-multiple-text', '');

                                // Decode json check modules.
                                $agents_to_report = json_decode(
                                    io_safe_output($agents_to_report_text),
                                    true
                                );
                                $modules_to_report = json_decode(
                                    io_safe_output($modules_to_report_text),
                                    true
                                );

                                $es['module'] = get_same_modules_all(
                                    $agents_to_report,
                                    $modules_to_report
                                );

                                // Encode json modules and agents.
                                $es['module'] = base64_encode(json_encode($es['module']));
                                $es['id_agents'] = base64_encode(json_encode($agents_to_report));
                                $es['show_type'] = get_parameter('show_type', 0);

                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'alert_report_actions':
                                $alert_templates_to_report = get_parameter('alert_templates');
                                $alert_actions_to_report = get_parameter('alert_actions');
                                $show_summary = get_parameter('show_summary', 0);
                                $group_by = get_parameter('group_by');
                                $only_data = get_parameter('only_data', 0);

                                $agents_to_report_text = get_parameter('id_agents2-multiple-text');
                                $modules_to_report_text = get_parameter('module-multiple-text', '');

                                // Decode json check modules.
                                $agents_to_report = json_decode(
                                    io_safe_output($agents_to_report_text),
                                    true
                                );
                                $modules_to_report = json_decode(
                                    io_safe_output($modules_to_report_text),
                                    true
                                );

                                $es['module'] = get_same_modules_all(
                                    $agents_to_report,
                                    $modules_to_report
                                );

                                // Encode json modules and agents.
                                $es['module'] = base64_encode(json_encode($es['module']));
                                $es['id_agents'] = base64_encode(json_encode($agents_to_report));

                                $es['templates'] = $alert_templates_to_report;
                                $es['actions'] = $alert_actions_to_report;
                                $es['show_summary'] = $show_summary;
                                $es['group_by'] = $group_by;
                                $es['only_data'] = $only_data;

                                $values['external_source'] = json_encode($es);

                                $values['period'] = get_parameter('period');
                                $values['lapse_calc'] = get_parameter(
                                    'lapse_calc'
                                );
                                $values['lapse'] = get_parameter('lapse');

                                $good_format = true;
                            break;

                            case 'inventory_changes':
                                $values['period'] = get_parameter('period');
                                $es['id_agents'] = get_parameter('id_agents');
                                $es['inventory_modules'] = get_parameter(
                                    'inventory_modules'
                                );
                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'agent_configuration':
                                $values['id_agent'] = get_parameter('id_agent');
                                $good_format = true;
                            break;

                            case 'group_configuration':
                                $values['id_group'] = get_parameter('id_group');
                                $good_format = true;
                            break;

                            case 'netflow_area':
                            case 'netflow_data':
                            case 'netflow_summary':
                            case 'netflow_top_N':
                                $values['text'] = get_parameter(
                                    'netflow_filter'
                                );
                                $values['description'] = get_parameter(
                                    'description'
                                );
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter('resolution');
                                $values['top_n_value'] = get_parameter(
                                    'max_values'
                                );
                                $good_format = true;
                            break;

                            case 'availability':
                                $values['period'] = get_parameter('period');
                                // HACK it is saved in show_graph field.
                                // Show interfaces instead the modules.
                                $values['show_graph'] = get_parameter(
                                    'checkbox_show_address_agent'
                                );
                                $good_format = true;
                            break;

                            case 'simple_graph':
                                $values['graph_render'] = (int) get_parameter(
                                    'graph_render'
                                );
                            case 'simple_baseline_graph':
                                // HACK it is saved in show_graph field.
                                $values['show_graph'] = (int) get_parameter(
                                    'time_compare_overlapped'
                                );
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'network_interfaces_report':
                                $values['graph_render'] = (int) get_parameter(
                                    'graph_render'
                                );
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'custom_render':
                                $macro_custom_name = get_parameter('macro_custom_name', []);
                                $macro_custom_type = get_parameter('macro_custom_type', []);
                                $macro_custom_value = get_parameter('macro_custom_value', []);
                                $macro_custom_key = get_parameter('macro_custom_key', []);
                                $macros_definition = [];

                                foreach ($macro_custom_name as $key_macro => $value_macro) {
                                    $kl = (empty($macro_custom_key[$key_macro]) === true) ? 0 : $macro_custom_key[$key_macro];
                                    $macros_definition[$key_macro]['name'] = $value_macro;
                                    $macros_definition[$key_macro]['type'] = $macro_custom_type[$key_macro];


                                    if (is_array($macro_custom_value[$kl]) === true) {
                                        foreach ($macro_custom_value[$kl] as $k => $v) {
                                            $macros_definition[$key_macro][$k] = $v;
                                        }
                                    } else {
                                        $macros_definition[$key_macro]['value'] = $macro_custom_value[$key_macro];
                                    }
                                }

                                $values['macros_definition'] = json_encode($macros_definition);
                                $values['render_definition'] = get_parameter('render_definition', '');
                                $good_format = true;
                            break;

                            case 'min_value':
                            case 'max_value':
                            case 'avg_value':
                                $values['period'] = get_parameter('period');
                                $values['lapse_calc'] = get_parameter(
                                    'lapse_calc'
                                );
                                $values['lapse'] = get_parameter('lapse');
                                $values['visual_format'] = get_parameter(
                                    'visual_format'
                                );
                                $values['use_prefix_notation'] = get_parameter(
                                    'use_prefix_notation'
                                );
                                $good_format = true;
                            break;

                            default:
                                $values['period'] = get_parameter('period');
                                $values['top_n'] = get_parameter(
                                    'radiobutton_max_min_avg',
                                    0
                                );
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $values['text'] = get_parameter('text');
                                $values['show_graph'] = get_parameter(
                                    'combo_graph_options'
                                );
                                $values['use_prefix_notation'] = get_parameter(
                                    'use_prefix_notation'
                                );
                                $good_format = true;
                            break;
                        }

                        if ($values['server_name'] == '') {
                            $values['server_name'] = get_parameter(
                                'combo_server'
                            );
                        }

                        $values['id_agent'] = get_parameter('id_agent');
                        $values['id_gs'] = get_parameter('id_custom_graph');
                        if (($values['type'] == 'alert_report_agent')
                            || ($values['type'] == 'event_report_agent')
                            || ($values['type'] == 'agent_configuration')
                            || ($values['type'] == 'group_configuration')
                        ) {
                            $values['id_agent_module'] = '';
                        } else {
                            $values['id_agent_module'] = get_parameter(
                                'id_agent_module'
                            );
                        }

                        $values['ipam_network_filter'] = get_parameter('network_filter', 0);
                        $values['ipam_alive_ips'] = get_parameter('alive_ip', 0);
                        $values['ipam_ip_not_assigned_to_agent'] = get_parameter('agent_not_assigned_to_ip', 0);

                        $values['only_display_wrong'] = (int) get_parameter(
                            'checkbox_only_display_wrong',
                            0
                        );
                        $values['monday'] = get_parameter('monday', 0);
                        $values['tuesday'] = get_parameter('tuesday', 0);
                        $values['wednesday'] = get_parameter('wednesday', 0);
                        $values['thursday'] = get_parameter('thursday', 0);
                        $values['friday'] = get_parameter('friday', 0);
                        $values['saturday'] = get_parameter('saturday', 0);
                        $values['sunday'] = get_parameter('sunday', 0);
                        $values['compare_work_time'] = get_parameter(
                            'compare_work_time',
                            0
                        );
                        $values['total_time'] = get_parameter('total_time', 0);
                        $values['time_failed'] = get_parameter(
                            'time_failed',
                            0
                        );
                        $values['time_in_ok_status'] = get_parameter(
                            'time_in_ok_status',
                            0
                        );
                        $values['time_in_warning_status'] = get_parameter(
                            'time_in_warning_status',
                            0
                        );
                        $values['time_in_unknown_status'] = get_parameter(
                            'time_in_unknown_status',
                            0
                        );
                        $values['time_of_not_initialized_module'] = get_parameter(
                            'time_of_not_initialized_module',
                            0
                        );
                        $values['time_of_downtime'] = get_parameter(
                            'time_of_downtime',
                            0
                        );
                        $values['total_checks'] = get_parameter(
                            'total_checks',
                            0
                        );
                        $values['checks_failed'] = get_parameter(
                            'checks_failed',
                            0
                        );
                        $values['checks_in_ok_status'] = get_parameter(
                            'checks_in_ok_status',
                            0
                        );
                        $values['checks_in_warning_status'] = get_parameter(
                            'checks_in_warning_status',
                            0
                        );
                        $values['unknown_checks'] = get_parameter(
                            'unknown_checks',
                            0
                        );
                        $values['agent_max_value'] = get_parameter(
                            'agent_max_value',
                            0
                        );
                        $values['agent_min_value'] = get_parameter(
                            'agent_min_value',
                            0
                        );

                        $values['time_from'] = get_parameter(
                            'time_from'
                        );
                        $values['time_to'] = get_parameter('time_to');

                        $values['group_by_agent'] = get_parameter(
                            'checkbox_row_group_by_agent',
                            0
                        );
                        $values['show_resume'] = get_parameter(
                            'checkbox_show_resume',
                            0
                        );
                        $values['order_uptodown'] = get_parameter(
                            'radiobutton_order_uptodown',
                            0
                        );
                        $values['exception_condition'] = (int) get_parameter(
                            'exception_condition',
                            0
                        );
                        $values['exception_condition_value'] = get_parameter(
                            'exception_condition_value'
                        );
                        $values['id_module_group'] = get_parameter(
                            'combo_modulegroup'
                        );
                        $values['id_group'] = get_parameter('combo_group');


                        if ((($values['type'] == 'custom_graph')
                            || ($values['type'] == 'automatic_custom_graph'))
                            && ($values['id_gs'] == 0 || $values['id_gs'] == '')
                        ) {
                            $resultOperationDB = false;
                            break;
                        }

                        if ($config['metaconsole'] == 1
                            && defined('METACONSOLE')
                        ) {
                            if (($values['type'] == 'custom_graph')
                                || ($values['type'] == 'automatic_custom_graph')
                            ) {
                                $id_gs = substr(
                                    $values['id_gs'],
                                    0,
                                    strpos($values['id_gs'], '|')
                                );
                                if ($id_gs !== false && $id_gs !== '') {
                                    $server_name = strstr(
                                        $values['id_gs'],
                                        '|'
                                    );
                                    $values['id_gs'] = $id_gs;
                                    $values['server_name'] = substr(
                                        $server_name,
                                        1,
                                        strlen($server_name)
                                    );
                                }
                            }
                        }

                        if (($values['type'] == 'sql')
                            || ($values['type'] == 'sql_graph_hbar')
                            || ($values['type'] == 'sql_graph_vbar')
                            || ($values['type'] == 'sql_graph_pie')
                        ) {
                            $values['treport_custom_sql_id'] = get_parameter(
                                'id_custom'
                            );
                            if ($values['treport_custom_sql_id'] == 0) {
                                $sql = get_parameter('sql', '');
                                $values['external_source'] = $sql;
                            }

                            $values['historical_db'] = get_parameter(
                                'historical_db_check'
                            );
                            $values['top_n_value'] = get_parameter('max_items');

                            if ($values['type'] === 'sql_graph_hbar'
                                || ($values['type'] === 'sql_graph_vbar')
                                || ($values['type'] === 'sql_graph_pie')
                            ) {
                                $values['server_name'] = get_parameter('combo_server_sql');
                            } else {
                                $values['server_name'] = get_parameter('combo_server');
                            }

                            if ($sql !== '') {
                                if ($values['server_name'] === 'all') {
                                    $servers_connection = metaconsole_get_connections();
                                    foreach ($servers_connection as $key => $s) {
                                        $good_format = db_validate_sql($sql, $s['server_name']);
                                    }

                                    // Reconnected in nodo if exist.
                                    if ($server_id !== 0) {
                                        $connection = metaconsole_get_connection_by_id(
                                            $server_id
                                        );
                                        metaconsole_connect($connection);
                                    }
                                } else if ($server_id === 0) {
                                    // Connect with node if not exist conexion.
                                    $good_format = db_validate_sql($sql, (is_metaconsole() === true) ? $values['server_name'] : false);
                                } else {
                                    $good_format = db_validate_sql($sql);
                                }
                            }
                        } else if ($values['type'] == 'url') {
                            $values['external_source'] = get_parameter('url');
                        } else if ($values['type'] == 'event_report_group') {
                            $values['id_agent'] = get_parameter('group');
                        }

                        if ($values['type'] == 'sumatory') {
                            $values['uncompressed_module'] = get_parameter('uncompressed_module', 0);
                        }

                        $values['header_definition'] = get_parameter('header');
                        $values['column_separator'] = get_parameter('field');
                        $values['line_separator'] = get_parameter('line');

                        $values['current_month'] = get_parameter('current_month');

                        $values['failover_mode'] = get_parameter(
                            'failover_mode',
                            0
                        );

                        $values['failover_type'] = get_parameter(
                            'failover_type',
                            REPORT_FAILOVER_TYPE_NORMAL
                        );

                        $values['summary'] = get_parameter(
                            'summary',
                            0
                        );

                        $style = [];
                        $style['show_in_same_row'] = get_parameter(
                            'show_in_same_row',
                            0
                        );
                        $style['hide_notinit_agents'] = get_parameter(
                            'hide_notinit_agents',
                            0
                        );
                        $style['priority_mode'] = get_parameter(
                            'priority_mode',
                            REPORT_PRIORITY_MODE_OK
                        );
                        $style['dyn_height'] = get_parameter('dyn_height', 230);

                        switch ($values['type']) {
                            case 'event_report_group':
                                $server_multiple = get_parameter(
                                    'server_multiple',
                                    ''
                                );
                                $style['server_multiple'] = json_encode(
                                    $server_multiple
                                );
                            case 'event_report_agent':
                            case 'event_report_module':
                                $show_summary_group = get_parameter(
                                    'show_summary_group',
                                    0
                                );
                                $filter_event_severity = get_parameter(
                                    'filter_event_severity',
                                    ''
                                );
                                $filter_event_type = get_parameter(
                                    'filter_event_type',
                                    ''
                                );
                                $filter_event_status = get_parameter(
                                    'filter_event_status',
                                    ''
                                );

                                $event_graph_by_agent = get_parameter(
                                    'event_graph_by_agent',
                                    0
                                );
                                $event_graph_by_user_validator = get_parameter(
                                    'event_graph_by_user_validator',
                                    0
                                );
                                $event_graph_by_criticity = get_parameter(
                                    'event_graph_by_criticity',
                                    0
                                );
                                $event_graph_validated_vs_unvalidated = get_parameter(
                                    'event_graph_validated_vs_unvalidated',
                                    0
                                );

                                $event_filter_search = get_parameter(
                                    'filter_search',
                                    ''
                                );

                                $event_filter_exclude = get_parameter(
                                    'filter_exclude',
                                    ''
                                );

                                $custom_data_events = get_parameter_switch(
                                    'custom_data_events',
                                    0
                                );


                                // Added for events items.
                                $style['show_summary_group'] = $show_summary_group;
                                $style['filter_event_severity'] = json_encode(
                                    $filter_event_severity
                                );
                                $style['filter_event_type'] = json_encode(
                                    $filter_event_type
                                );
                                $style['filter_event_status'] = json_encode(
                                    $filter_event_status
                                );

                                $style['event_graph_by_agent'] = $event_graph_by_agent;
                                $style['event_graph_by_user_validator'] = $event_graph_by_user_validator;
                                $style['event_graph_by_criticity'] = $event_graph_by_criticity;
                                $style['event_graph_validated_vs_unvalidated'] = $event_graph_validated_vs_unvalidated;
                                $style['event_filter_search'] = $event_filter_search;
                                $style['event_filter_exclude'] = $event_filter_exclude;
                                $style['custom_data_events'] = $custom_data_events;

                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'simple_graph':
                                // Warning. We are using this column to hold
                                // this value to avoid the modification
                                // of the database for compatibility reasons.
                                $style['percentil'] = (int) get_parameter(
                                    'percentil'
                                );
                                $style['fullscale'] = (int) get_parameter(
                                    'fullscale'
                                );
                                $style['image_threshold'] = (int) get_parameter(
                                    'image_threshold'
                                );
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'network_interfaces_report':
                                $style['fullscale'] = (int) get_parameter(
                                    'fullscale'
                                );
                            break;

                            case 'module_histogram_graph':
                            case 'agent_configuration':
                            case 'alert_report_agent':
                            case 'alert_report_module':
                            case 'historical_data':
                            case 'sumatory':
                            case 'database_serialized':
                            case 'monitor_report':
                            case 'min_value':
                            case 'max_value':
                            case 'avg_value':
                            case 'projection_graph':
                            case 'prediction_date':
                            case 'simple_baseline_graph':
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
                                }
                            break;

                            case 'permissions_report':
                                $es['id_users'] = get_parameter('selected-select-id_users');
                                $es['users_groups'] = get_parameter('users_groups', 0);
                                $es['select_by_group'] = get_parameter('select_by_group', 0);
                                $description = get_parameter('description');
                                $values['external_source'] = json_encode($es);
                            break;

                            case 'agents_inventory':
                                $es['agent_server_filter'] = get_parameter('agent_server_filter');
                                $es['agents_inventory_display_options'] = get_parameter('agents_inventory_display_options');
                                $es['agent_custom_field_filter'] = get_parameter('agent_custom_field_filter');
                                $es['agent_os_filter'] = get_parameter('agent_os_filter');
                                $es['agent_custom_fields'] = get_parameter('agent_custom_fields');
                                $es['agent_status_filter'] = get_parameter('agent_status_filter');
                                $es['agent_version_filter'] = get_parameter('agent_version_filter');
                                $es['agent_module_search_filter'] = get_parameter('agent_module_search_filter');
                                $es['agent_group_filter'] = get_parameter('agent_group_filter');
                                $es['agent_remote_conf'] = get_parameter('agent_remote_conf');

                                $values['external_source'] = json_encode($es);
                            break;

                            case 'modules_inventory':
                                $es['agent_server_filter'] = get_parameter('agent_server_filter');
                                $es['module_group'] = get_parameter('module_group');
                                $es['agent_group_filter'] = get_parameter('agent_group_filter');
                                $es['search_module_name'] = get_parameter('search_module_name');
                                $es['tags'] = get_parameter('tags');
                                $es['alias'] = get_parameter('alias', '');
                                $es['description_switch'] = get_parameter('description_switch', '');
                                $es['last_status_change'] = get_parameter('last_status_change', '');

                                $values['external_source'] = json_encode($es);
                            break;

                            case 'IPAM_network':
                                $es['network_filter'] = get_parameter('network_filter');
                                $es['alive_ip'] = get_parameter('alive_ip');
                                $es['agent_not_assigned_to_ip'] = get_parameter('agent_not_assigned_to_ip');
                            break;

                            case 'top_n':
                            case 'general':
                            case 'exception':
                                $text_agent = get_parameter('text_agent', '');
                                $text_agent_module = get_parameter('text_agent_module', '');
                                if (empty($text_agent) === false) {
                                    $style['text_agent'] = base64_encode($text_agent);
                                }

                                if (empty($text_agent_module) === false) {
                                    $style['text_agent_module'] = base64_encode($text_agent_module);
                                }
                            break;

                            default:
                                // Default.
                            break;
                        }

                        $values['style'] = io_safe_input(json_encode($style));

                        if ($good_format) {
                            $result = db_process_sql_insert(
                                'treport_content',
                                $values
                            );

                            if ($result === false) {
                                $resultOperationDB = false;
                            } else {
                                $idItem = $result;

                                $max = db_get_all_rows_sql(
                                    'SELECT max(`order`) AS max
                                    FROM treport_content
                                    WHERE id_report = '.$idReport.';'
                                );

                                if ($max === false) {
                                    $max = 0;
                                } else {
                                    $max = $max[0]['max'];
                                }

                                db_process_sql_update(
                                    'treport_content',
                                    ['`order`' => ($max + 1)],
                                    ['id_rc' => $idItem]
                                );

                                $resultOperationDB = true;
                            }

                            break;
                        } else {
                            // If fields dont have good format.
                            $resultOperationDB = false;
                        }
                    break;

                    default:
                        // Default.
                    break;
                }
            break;

            default:
                if ($enterpriseEnable && $activeTab != 'advanced') {
                    $resultOperationDB = reporting_enterprise_update_action();
                }
            break;
        }
    break;

    case 'filter':
    case 'edit':
        $resultOperationDB = null;
        $report = db_get_row_filter(
            'treport',
            ['id_report' => $idReport]
        );

        $reportName = $report['name'];
        $idGroupReport = $report['id_group'];
        $description = $report['description'];
        $type_access_selected = reports_get_type_access($report);
        $id_group_edit = $report['id_group_edit'];
        $report_id_user = $report['id_user'];
        $non_interactive = $report['non_interactive'];
        $cover_page_render = $report['cover_page_render'];
        $index_render = $report['index_render'];
    break;

    case 'delete':
        $idItem = get_parameter('id_item');

        $report = db_get_row_filter('treport', ['id_report' => $idReport]);
        $reportName = $report['name'];

        $resultOperationDB = db_process_sql_delete(
            'treport_content_sla_combined',
            ['id_report_content' => $idItem]
        );
        $resultOperationDB2 = db_process_sql_delete(
            'treport_content_item',
            ['id_report_content' => $idItem]
        );
        if ($resultOperationDB !== false) {
            $resultOperationDB = db_process_sql_delete(
                'treport_content',
                ['id_rc' => $idItem]
            );
        }

        if ($resultOperationDB2 !== false) {
            $resultOperationDB2 = db_process_sql_delete(
                'treport_content',
                ['id_rc' => $idItem]
            );
        }
    break;

    case 'order':
        $resultOperationDB = null;
        $report = db_get_row_filter(
            'treport',
            ['id_report' => $idReport]
        );

        $reportName = $report['name'];
        $idGroupReport = $report['id_group'];
        $description = $report['description'];

        $idItem = get_parameter('id_item');
        $dir = get_parameter('dir');
        $field = get_parameter('field', null);

        switch ($field) {
            case 'module':
            case 'agent':
            case 'type':

                // Sort functionality for normal console.
                if (!defined('METACONSOLE')) {
                    switch ($field) {
                        case 'module':
                            $sql = '
								SELECT t1.id_rc, t2.nombre
								FROM treport_content t1
									LEFT JOIN tagente_modulo t2
										ON t1.id_agent_module = t2.id_agente_modulo
								WHERE %s
								ORDER BY nombre %s
							';
                        break;

                        case 'agent':
                            $sql = '
								SELECT t4.id_rc, t5.nombre
								FROM
									(
									SELECT t1.*, id_agente
									FROM treport_content t1
										LEFT JOIN tagente_modulo t2
											ON t1.id_agent_module = id_agente_modulo
									) t4
									LEFT JOIN tagente t5
										ON (t4.id_agent = t5.id_agente OR t4.id_agente = t5.id_agente)
								WHERE %s
								ORDER BY t5.nombre %s
							';
                        break;

                        case 'type':
                            $sql = 'SELECT id_rc FROM treport_content WHERE %s ORDER BY type %s';
                        break;

                        default:
                            // Default.
                        break;
                    }

                    $sql = sprintf($sql, 'id_report = '.$idReport, '%s');
                    switch ($dir) {
                        case 'up':
                            $sql = sprintf($sql, 'ASC');
                        break;

                        case 'down':
                            $sql = sprintf($sql, 'DESC');
                        break;

                        default:
                            // Default.
                        break;
                    }

                    $ids = db_get_all_rows_sql($sql);
                } else if ($config['metaconsole'] == 1) {
                    // Sort functionality for metaconsole.
                    switch ($field) {
                        case 'agent':
                        case 'module':
                            $sql = 'SELECT id_rc, id_agent, id_agent_module, server_name FROM treport_content WHERE %s ORDER BY server_name';
                            $sql = sprintf(
                                $sql,
                                'id_report = '.$idReport,
                                '%s'
                            );

                            $report_items = db_get_all_rows_sql($sql);

                            $ids = [];
                            $temp_sort = [];
                            $i = 0;

                            if (!empty($report_items)) {
                                foreach ($report_items as $report_item) {
                                    $connection = metaconsole_get_connection(
                                        $report_item['server_name']
                                    );
                                    if (metaconsole_load_external_db($connection) != NOERR) {
                                        continue;
                                    }

                                    switch ($field) {
                                        case 'agent':
                                            $agents_name = agents_get_agents(
                                                ['id_agente' => $report_item['id_agent']],
                                                'nombre'
                                            );

                                            // Item without agent.
                                            if (!$agents_name) {
                                                $element_name = '';
                                            } else {
                                                $agent_name = array_shift(
                                                    $agents_name
                                                );
                                                $element_name = $agent_name['nombre'];
                                            }
                                        break;

                                        case 'module':
                                            $module_name = modules_get_agentmodule_name(
                                                $report_item['id_agent_module']
                                            );

                                            // Item without module.
                                            if (!$module_name) {
                                                $element_name = '';
                                            } else {
                                                $element_name = $module_name;
                                            }
                                        break;

                                        default:
                                            // Default.
                                        break;
                                    }

                                    metaconsole_restore_db();

                                    $temp_sort[$report_item['id_rc']] = $element_name;
                                }

                                // Performes sorting.
                                switch ($dir) {
                                    case 'up':
                                        asort($temp_sort);
                                    break;

                                    case 'down':
                                        arsort($temp_sort);
                                    break;

                                    default:
                                        // Default.
                                    break;
                                }

                                foreach ($temp_sort as $temp_element_key => $temp_element_val) {
                                    $ids[$i]['id_rc'] = $temp_element_key;
                                    $ids[$i]['element_name'] = $temp_element_val;
                                    $i++;
                                }

                                // Free resources.
                                unset($temp_sort);
                                unset($report_items);
                            }
                        break;

                        // Type case only depends of local database.
                        case 'type':
                            $sql = 'SELECT id_rc
								FROM treport_content
								WHERE %s ORDER BY type %s';

                            $sql = sprintf(
                                $sql,
                                'id_report = '.$idReport,
                                '%s'
                            );
                            switch ($dir) {
                                case 'up':
                                    $sql = sprintf($sql, 'ASC');
                                break;

                                case 'down':
                                    $sql = sprintf($sql, 'DESC');
                                break;

                                default:
                                    // Default.
                                break;
                            }

                            $ids = db_get_all_rows_sql($sql);
                        break;

                        default:
                            // Default.
                        break;
                    }
                }

                $count = 1;
                $resultOperationDB = true;
                foreach ($ids as $id) {
                    $result = db_process_sql_update(
                        'treport_content',
                        ['order' => $count],
                        ['id_rc' => $id['id_rc']]
                    );

                    if ($result === false) {
                        $resultOperationDB = false;
                        break;
                    }

                    $count++;
                }
            break;

            default:
                $oldOrder = db_get_value_sql(
                    'SELECT `order`
                    FROM treport_content
                    WHERE id_rc = '.$idItem
                );

                switch ($dir) {
                    case 'up':
                        $newOrder = ($oldOrder - 1);
                    break;

                    case 'down':
                        $newOrder = ($oldOrder + 1);
                    break;

                    default:
                        // Default.
                    break;
                }

                $resultOperationDB = db_process_sql_update(
                    'treport_content',
                    ['`order`' => $oldOrder],
                    [
                        '`order`'   => $newOrder,
                        'id_report' => $idReport,
                    ]
                );


                if ($resultOperationDB !== false) {
                    $resultOperationDB = db_process_sql_update(
                        'treport_content',
                        ['`order`' => $newOrder],
                        ['id_rc' => $idItem]
                    );
                }
            break;
        }
    break;

    // Added for report templates.
    default:
        if ($enterpriseEnable) {
            $buttons = [
                'list_reports' => [
                    'active' => false,
                    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/logs@svg.svg', true, ['title' => __('Reports list'), 'class' => 'invert_filter main_menu_icon']).'</a>',
                ],
            ];

            $buttons = reporting_enterprise_add_main_Tabs($buttons);

            $subsection = '';
            $helpers = '';
            switch ($activeTab) {
                case 'main':
                    $buttons['list_reports']['active'] = true;
                    $subsection = __('Custom reporting');
                break;

                default:
                    $data_tab = reporting_enterprise_add_subsection_main(
                        $activeTab,
                        $buttons
                    );

                    $subsection = $data_tab['subsection'];
                    $buttons = $data_tab['buttons'];
                    $helpers = $data_tab['helper'];
                break;
            }

            // Header.
            ui_print_standard_header(
                $subsection,
                'images/op_reporting.png',
                false,
                '',
                false,
                $buttons,
                [
                    [
                        'link'  => '',
                        'label' => __('Reporting'),
                    ],
                    [
                        'link'  => '',
                        'label' => __('Custom reports'),
                    ],
                ]
            );

            reporting_enterprise_select_main_tab($action);
        }
    return;
        break;
}

if ($enterpriseEnable) {
    $result = reporting_enterprise_actions_DB($action, $activeTab, $idReport);
    if ($result !== null) {
        $resultOperationDB = $result;
    }
}

$urlB = 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder';
$buttons = [
    'list_reports' => [
        'active' => false,
        'text'   => '<a href="'.$urlB.'&pure='.$pure.'">'.html_print_image(
            'images/report_list.png',
            true,
            [
                'title' => __('Reports list'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>',
    ],
    'main'         => [
        'active' => false,
        'text'   => '<a href="'.$urlB.'&tab=main&action=edit&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/op_reporting.png', true, ['title' => __('Main data'), 'class' => 'main_menu_icon invert_filter']).'</a>',
    ],
    'list_items'   => [
        'active' => false,
        'text'   => '<a href="'.$urlB.'&tab=list_items&action=edit&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/logs@svg.svg', true, ['title' => __('List items'), 'class' => 'main_menu_icon invert_filter']).'</a>',
    ],
    'item_editor'  => [
        'active' => false,
        'text'   => '<a href="'.$urlB.'&tab=item_editor&action=new&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/edit.svg', true, ['title' => __('Item editor'), 'class' => 'main_menu_icon invert_filter']).'</a>',
    ],
];

if ($enterpriseEnable) {
    $buttons = reporting_enterprise_add_Tabs(
        $buttons,
        $idReport
    );
}

$buttons['view'] = [
    'active' => false,
    'text'   => '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$idReport.'&pure='.$pure.'">'.html_print_image(
        'images/see-details@svg.svg',
        true,
        [
            'title' => __('View report'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

$buttons[$activeTab]['active'] = true;

if ($idReport != 0) {
    $textReportName = (empty($reportName) === false) ? $reportName : $report['name'];
} else {
    $temp = $buttons['main'];
    $buttons = null;
    $buttons = [
        'main' => [
            'active' => true,
            'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/report_list.png', true, ['title' => __('Reports list'), 'class' => 'main_menu_icon invert_filter']).'</a>',
        ],
    ];
    $textReportName = __('Create Custom Report');
}

$tab_builder = ($activeTab === 'item_editor') ? 'reporting_item_editor_tab' : '';

if (is_metaconsole() === true || $action !== 'update') {
    // Header.
    ui_print_standard_header(
        $textReportName,
        'images/op_reporting.png',
        false,
        $tab_builder,
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Reporting'),
            ],
            [
                'link'  => '',
                'label' => __('Custom reports'),
            ],
        ]
    );
}

if ($resultOperationDB !== null) {
    if ($action == 'update') {
        $buttons[$activeTab]['active'] = false;
        $activeTab = 'list_items';
        $buttons[$activeTab]['active'] = true;

        if (is_metaconsole() === false) {
            // Header.
            ui_print_standard_header(
                $textReportName,
                'images/op_reporting.png',
                false,
                '',
                false,
                $buttons,
                [
                    [
                        'link'  => '',
                        'label' => __('Reporting'),
                    ],
                    [
                        'link'  => '',
                        'label' => __('Custom reports'),
                    ],
                ]
            );
        }
    }

    $err = '';
    switch ($_POST['type']) {
        case 'custom_graph':
            $err .= 'You must enter custom graph';
        break;

        case 'SLA':
            $err .= 'You must enter some character in SLA limit field';
        default:
            $err .= '';
        break;
    }

    ui_print_result_message(
        $resultOperationDB,
        __('Successfull action'),
        __('Unsuccessful action<br><br>'.$err)
    );
}

switch ($activeTab) {
    case 'main':
        include_once $config['homedir'].'/godmode/reporting/reporting_builder.main.php';
    break;

    case 'list_items':
        include_once $config['homedir'].'/godmode/reporting/reporting_builder.list_items.php';
    break;

    case 'item_editor':
        include_once $config['homedir'].'/godmode/reporting/reporting_builder.item_editor.php';
    break;

    default:
        reporting_enterprise_select_tab($activeTab);
    break;
}
