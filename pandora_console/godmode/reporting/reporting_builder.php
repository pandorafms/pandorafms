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

        $('[id^=checkbox-massive_report_check]').change(function(){
            if($(this).parent().parent().parent().hasClass('checkselected')){
                $(this).parent().parent().parent().removeClass('checkselected');
            }
            else{
                $(this).parent().parent().parent().addClass('checkselected');
            }
        });

        $('[id^=checkbox-all_delete]').change(function(){
            if ($("#checkbox-all_delete").prop("checked")) {
                $('[id^=checkbox-massive_report_check]')
                    .parent()
                    .parent()
                    .parent()
                    .addClass('checkselected');
                $(".check_delete").prop("checked", true);
                $('.check_delete').each(function(){
                    $('#hidden-id_report_'+$(this).val()).prop("disabled", false);    
                });
            }
            else{
                $('[id^=checkbox-massive_report_check]')
                    .parent()
                    .parent()
                    .parent()
                    .removeClass('checkselected');
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

/*
    IMPORTANT NOTE: All reporting pages are used also
    for metaconsole reporting functionality.
    So, it's very important to specify full url and paths to resources
    because metaconsole has a different.
    entry point: enterprise/meta/index.php than normal console !!!.
*/

// Login check.
check_login();

enterprise_hook('open_meta_frame');
$report_r = check_acl($config['id_user'], 0, 'RR');
$report_w = check_acl($config['id_user'], 0, 'RW');
$report_m = check_acl($config['id_user'], 0, 'RM');
$access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));
if (!$report_r && !$report_w && !$report_m) {
    db_pandora_audit(
        'ACL Violation',
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

    ui_print_result_message(
        $result,
        __('Your report has been planned, and the system will email you a PDF with the report as soon as its finished'),
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

        if ($action == 'delete_report') {
            if ($config['id_user'] == $report['id_user']
                || is_user_admin($config['id_user'])
            ) {
                $delete_report_bypass = true;
            }
        }

        if (!$delete_report_bypass) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access report builder'
            );
            include 'general/noaccess.php';
            exit;
        }
    }
}

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

    case 'delete_report':
    case 'list':
        $buttons = [
            'list_reports' => [
                'active' => false,
                'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/report_list.png', true, ['title' => __('Reports list')]).'</a>',
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
                $helpers = $data_tab['helpers'];
            break;
        }

        // Page header for metaconsole.
        if ($enterpriseEnable && defined('METACONSOLE')) {
            // Bread crumbs.
            ui_meta_add_breadcrumb(
                [
                    'link' => 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure,
                    'text' => __('Reporting'),
                ]
            );

            ui_meta_print_page_header($nav_bar);

            // Print header.
            ui_meta_print_header(__('Reporting'), '', $buttons);
        } else {
            // Page header for normal console.
            ui_print_page_header(
                __('Custom reporting'),
                'images/op_reporting.png',
                false,
                '',
                false,
                $buttons,
                false,
                '',
                60
            );
        }


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
                        $delete = check_acl(
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

            if (! $delete && !empty($type_access_selected)) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access report builder deletion'
                );
                include 'general/noaccess.php';
                exit;
            }

            $result = reports_delete_report($idReport);
            if ($result !== false) {
                db_pandora_audit(
                    'Report management',
                    'Delete report #'.$idReport
                );
            } else {
                db_pandora_audit(
                    'Report management',
                    'Fail try to delete report #'.$idReport
                );
            }

            ui_print_result_message(
                $result,
                __('Successfully deleted'),
                __('Could not be deleted')
            );
        }

        $id_group = (int) get_parameter('id_group', 0);
        $search = trim(get_parameter('search', ''));

        $search_sql = '';
        if ($search != '') {
            $search_name = '%'.$search."%' OR description LIKE '%".$search.'%';
        }

        $table_aux = new stdClass();
        $table_aux->width = '100%';
        $table_aux->class = 'databox filters';
        $table_aux->cellpadding = 0;
        $table_aux->cellspacing = 0;

        $table_aux->colspan[0][0] = 4;
        $table_aux->data[0][0] = '<b>'.__('Group').'</b>';

        $table_aux->data[0][1] = html_print_select_groups(
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
        ).'<br>';

        $table_aux->data[0][2] = '<b>'.__('Free text for search: ');
        $table_aux->data[0][2] .= ui_print_help_tip(
            __('Search by report name or description, list matches.'),
            true
        );
        $table_aux->data[0][2] .= '</b>';
        $table_aux->data[0][3] = html_print_input_text(
            'search',
            $search,
            '',
            30,
            '',
            true
        );

        $table_aux->data[0][6] = html_print_submit_button(
            __('Search'),
            'search_submit',
            false,
            'class="sub upd"',
            true
        );

        $url_rb = 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder';
        if (is_metaconsole()) {
            $filter = '<form action="'.$url_rb.'&id_group='.$id_group.'&pure='.$pure.'" method="post">';
            $filter .= html_print_table($table_aux, true);
            $filter .= '</form>';
            ui_toggle($filter, __('Show Option'));
        } else {
            echo '<form action="'.$url_rb.'&id_group='.$id_group.'&pure='.$pure.'" method="post">';
            html_print_table($table_aux);
            echo '</form>';
        }

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
        if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'RM')) {
            $return_all_group = true;
        } else {
            $return_all_group = false;
        }

        if ($search != '') {
            $filter = [
                'name'  => $search_name,
                'order' => 'name',
            ];
        } else {
            $filter = ['order' => 'name'];
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
            $url = 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder';
            ui_pagination($total_reports, $url, $offset, $pagination);

            $table = new stdClass();
            $table->id = 'report_list';
            $table->width = '100%';
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
                if (defined('METACONSOLE')) {
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
                if (!defined('METACONSOLE')) {
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

                if (check_acl($config['id_user'], $report['id_group'], 'RW')
                    || check_acl($config['id_user'], $report['id_group'], 'RM')
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
                } else if (!$report['non_interactive']) {
                    $data[2] = '<a href="'.$config['homeurl'].'index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'&pure='.$pure.'">';
                    $data[2] .= html_print_image(
                        'images/html.png',
                        true,
                        ['title' => __('HTML view')]
                    );
                    $data[2] .= '</a>';
                    $data[3] = '<a href="'.ui_get_full_url(false, false, false, false).'ajax.php?page='.$config['homedir'].'/operation/reporting/reporting_xml&id='.$report['id_report'].'">';
                    $data[3] .= html_print_image(
                        'images/xml.png',
                        true,
                        ['title' => __('Export to XML')]
                    );
                    $data[3] .= '</a>';
                    // I chose ajax.php because it's supposed
                    // to give XML anyway.
                } else {
                    $data[2] = html_print_image(
                        'images/html_disabled.png',
                        true
                    );
                    $data[3] = html_print_image(
                        'images/xml_disabled.png',
                        true
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
                        'groups_small',
                        '',
                        !defined('METACONSOLE')
                    );
                    $next++;
                }

                $type_access_selected = reports_get_type_access($report);
                $edit = false;
                $delete = false;

                switch ($type_access_selected) {
                    case 'group_view':
                        $edit = check_acl(
                            $config['id_user'],
                            $report['id_group'],
                            'RW'
                        );
                        $delete = $edit ||
                            is_user_admin($config['id_user']) ||
                            $config['id_user'] == $report['id_user'];
                    break;

                    case 'group_edit':
                        $edit = check_acl(
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
                    $table->cellclass[][$next] = 'action_buttons';

                    if (!isset($table->head[$next])) {
                        $table->head[$next] = '<span title="Operations">'.__('Op.').'</span>'.html_print_checkbox('all_delete', 0, false, true, false);
                        $table->size = [];
                        // $table->size[$next] = '80px';
                    }

                    if ($edit) {
                        $data[$next] = '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&action=edit&pure='.$pure.'" style="display:inline">';
                        $data[$next] .= html_print_input_image(
                            'edit',
                            'images/config.png',
                            1,
                            '',
                            true,
                            ['title' => __('Edit')]
                        );
                        $data[$next] .= html_print_input_hidden(
                            'id_report',
                            $report['id_report'],
                            true
                        );
                        $data[$next] .= '</form>';
                    }

                    if ($delete) {
                        $data[$next] .= '<form method="post" style="display:inline;" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
                        $data[$next] .= html_print_input_image(
                            'delete',
                            'images/cross.png',
                            1,
                            'margin-right: 10px;',
                            true,
                            ['title' => __('Delete')]
                        );
                        $data[$next] .= html_print_input_hidden(
                            'id_report',
                            $report['id_report'],
                            true
                        );
                        $data[$next] .= html_print_input_hidden(
                            'action',
                            'delete_report',
                            true
                        );

                        $data[$next] .= html_print_checkbox_extended(
                            'massive_report_check',
                            $report['id_report'],
                            false,
                            false,
                            '',
                            'class="check_delete"',
                            true
                        );

                        $data[$next] .= '</form>';
                    }
                } else {
                    if ($op_column) {
                        $data[$next] = '';
                    }
                }

                array_push($table->data, $data);
            }

            if ($columnview) {
                $count = 0;
                foreach ($table->data as $datos) {
                    if (!isset($datos[9])) {
                        $table->data[$count][9] = '';
                    }

                    $count++;
                }
            }

            html_print_table($table);
            ui_pagination(
                $total_reports,
                $url,
                $offset,
                $pagination,
                false,
                'offset',
                true,
                'pagination-bottom'
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
            echo '<form method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=new&pure='.$pure.'">';
            if (defined('METACONSOLE')) {
                echo '<div class="action-buttons" style="width: 100%; ">';
            } else {
                echo '<div class="action-buttons" style="width: 100%;">';
            }

            html_print_submit_button(
                __('Create report'),
                'create',
                false,
                'class="sub next"'
            );

            echo '</form>';
            echo '<form style="display:inline;" id="massive_report_form" method="post" action="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=delete">';

            foreach ($reports as $report) {
                echo '<input class="massive_report_form_elements" id="hidden-id_report_'.$report['id_report'].'" name="id_report[]" type="hidden" disabled value="'.$report['id_report'].'">';
            }

            echo '<input id="hidden-action" name="action" type="hidden" value="delete_report">';
            html_print_submit_button(
                __('Delete'),
                'delete_btn',
                false,
                'class="sub delete" style="margin-left:5px;"'
            );
            echo '</form>';
            echo '</div>';
        }

        enterprise_hook('close_meta_frame');
    return;

        break;
    case 'new':
        switch ($activeTab) {
            case 'main':
                $reportName = '';
                $idGroupReport = 0;
                // All groups.
                $description = '';
                $resultOperationDB = null;
                $report_id_user = 0;
                $type_access_selected = reports_get_type_access(false);
                $id_group_edit = 0;
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

                // Pretty font by default for pdf.
                $custom_font = 'FreeSans.ttf';

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
                            'name'            => $reportName,
                            'id_group'        => $idGroupReport,
                            'description'     => $description,
                            'private'         => $private,
                            'id_group_edit'   => $id_group_edit,
                            'non_interactive' => $non_interactive,
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

                        if ($resultOperationDB !== false) {
                            db_pandora_audit(
                                'Report management',
                                'Update report #'.$idReport
                            );
                        } else {
                            db_pandora_audit(
                                'Report management',
                                'Fail try to update report #'.$idReport
                            );
                        }
                    } else {
                        $resultOperationDB = false;
                    }

                    $action = 'edit';
                } else if ($action == 'save') {
                    if ($reportName != '' && $idGroupReport != '') {
                        // This flag allow to differentiate
                        // between normal console and metaconsole reports.
                        $metaconsole_report = (int) is_metaconsole();

                        if ($config['custom_report_front']) {
                            $custom_font = $config['custom_report_front_font'];
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
                                'name'            => $reportName,
                                'id_group'        => $idGroupReport,
                                'description'     => $description,
                                'first_page'      => $first_page,
                                'private'         => $private,
                                'id_group_edit'   => $id_group_edit,
                                'id_user'         => $config['id_user'],
                                'metaconsole'     => $metaconsole_report,
                                'non_interactive' => $non_interactive,
                                'custom_font'     => $custom_font,
                                'custom_logo'     => $logo,
                                'header'          => $header,
                                'footer'          => $footer,
                            ]
                        );

                        if ($idOrResult !== false) {
                            db_pandora_audit(
                                'Report management',
                                'Create report #'.$idOrResult
                            );
                        } else {
                            db_pandora_audit(
                                'Report management',
                                'Fail try to create report'
                            );
                        }
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
                        $server_name = get_parameter('server_id');
                        if (is_metaconsole() && $server_name != '') {
                            $id_meta = metaconsole_get_id_server($server_name);
                            $connection = metaconsole_get_connection_by_id(
                                $id_meta
                            );
                            metaconsole_connect($connection);
                            $values['server_name'] = $connection['server_name'];
                        }

                        $values['id_report'] = $idReport;
                        $values['description'] = get_parameter('description');
                        $values['type'] = get_parameter('type', null);
                        $values['recursion'] = get_parameter('recursion', null);
                        $values['show_extended_events'] = get_parameter('include_extended_events', null);

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

                        $values['name'] = reporting_label_macro(
                            $items_label,
                            $name_it
                        );

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
                                $log_number = get_parameter('log_number', '');

                                $es['source'] = $source;
                                $es['id_agents'] = $agents_to_report;
                                $es['search'] = $search;
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

                            case 'SLA_monthly':
                            case 'SLA_weekly':
                            case 'SLA_hourly':
                            case 'SLA_services':
                            case 'SLA':
                            case 'availability_graph':
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
                                $agents_to_report = get_parameter('id_agents2');
                                $modules_to_report = get_parameter(
                                    'module',
                                    ''
                                );

                                $es['module'] = get_same_modules(
                                    $agents_to_report,
                                    $modules_to_report
                                );
                                $es['id_agents'] = $agents_to_report;

                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'inventory':
                                $values['period'] = 0;
                                $es['date'] = get_parameter('date');
                                $es['id_agents'] = get_parameter('id_agents');
                                $es['inventory_modules'] = get_parameter(
                                    'inventory_modules'
                                );
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
                                $values['unknown_checks'] = get_parameter(
                                    'unknown_checks'
                                );
                                $values['agent_max_value'] = get_parameter(
                                    'agent_max_value'
                                );
                                $values['agent_min_value'] = get_parameter(
                                    'agent_min_value'
                                );
                                $good_format = true;
                            break;

                            case 'simple_graph':
                            case 'simple_baseline_graph':
                                // HACK it is saved in show_graph field.
                                $values['show_graph'] = (int) get_parameter(
                                    'time_compare_overlapped'
                                );
                                $values['period'] = get_parameter('period');
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
                                $good_format = true;
                            break;

                            case 'nt_top_n':
                                $values['period'] = get_parameter('period');
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
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
                        $values['total_time'] = get_parameter('total_time', 0);
                        $values['time_failed'] = get_parameter(
                            'time_failed',
                            0
                        );
                        $values['time_in_ok_status'] = get_parameter(
                            'time_in_ok_status',
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
                        $values['server_name'] = get_parameter('server_name');

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

                        // If metaconsole is activated.
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
                                $values['external_source'] = get_parameter(
                                    'sql'
                                );
                            }

                            $values['historical_db'] = get_parameter(
                                'historical_db_check'
                            );
                            $values['top_n_value'] = get_parameter('max_items');
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
                            case 'event_report_agent':
                            case 'event_report_group':
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

                                $style['event_graph_by_agent'] = $event_graph_by_agent;
                                $style['event_graph_by_user_validator'] = $event_graph_by_user_validator;
                                $style['event_graph_by_criticity'] = $event_graph_by_criticity;
                                $style['event_graph_validated_vs_unvalidated'] = $event_graph_validated_vs_unvalidated;
                                $style['event_filter_search'] = $event_filter_search;

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
                            case 'nt_top_n':
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
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

                        $values['name'] = reporting_label_macro(
                            $items_label,
                            $name_it
                        );

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
                                $values['external_source'] = json_encode($es);
                                $good_format = true;
                            break;

                            case 'event_report_log':
                                $agents_to_report = get_parameter('id_agents3');
                                $source = get_parameter('source', '');
                                $search = get_parameter('search', '');
                                $log_number = get_parameter('log_number', '');

                                $es['source'] = $source;
                                $es['id_agents'] = $agents_to_report;
                                $es['search'] = $search;
                                $es['log_number'] = $log_number;

                                $values['external_source'] = json_encode($es);
                                $values['period'] = get_parameter('period');
                                $good_format = true;
                            break;

                            case 'agent_module':
                                $agents_to_report = get_parameter('id_agents2');
                                $modules_to_report = get_parameter(
                                    'module',
                                    ''
                                );

                                $es['module'] = get_same_modules(
                                    $agents_to_report,
                                    $modules_to_report
                                );
                                $es['id_agents'] = $agents_to_report;

                                $values['external_source'] = json_encode($es);
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
                            case 'simple_baseline_graph':
                                // HACK it is saved in show_graph field.
                                $values['show_graph'] = (int) get_parameter(
                                    'time_compare_overlapped'
                                );
                                $values['period'] = get_parameter('period');
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
                                $good_format = true;
                            break;

                            case 'nt_top_n':
                                $values['top_n_value'] = get_parameter(
                                    'quantity'
                                );
                                $values['period'] = get_parameter('period');
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
                        $values['total_time'] = get_parameter('total_time', 0);
                        $values['time_failed'] = get_parameter(
                            'time_failed',
                            0
                        );
                        $values['time_in_ok_status'] = get_parameter(
                            'time_in_ok_status',
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
                            'radiobutton_exception_condition',
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
                                $values['external_source'] = get_parameter(
                                    'sql'
                                );
                            }

                            $values['historical_db'] = get_parameter(
                                'historical_db_check'
                            );
                            $values['top_n_value'] = get_parameter('max_items');
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
                            case 'event_report_agent':
                            case 'event_report_group':
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
                            case 'nt_top_n':
                                if ($label != '') {
                                    $style['label'] = $label;
                                } else {
                                    $style['label'] = '';
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
                    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/report_list.png', true, ['title' => __('Reports list')]).'</a>',
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

            // Page header for metaconsole.
            if ($enterpriseEnable && defined('METACONSOLE')) {
                // Bread crumbs.
                ui_meta_add_breadcrumb(
                    [
                        'link' => 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure,
                        'text' => __('Reporting'),
                    ]
                );

                ui_meta_print_page_header($nav_bar);

                // Print header.
                ui_meta_print_header(__('Reporting'), '', $buttons);
            } else {
                // Page header for normal console.
                ui_print_page_header(
                    $subsection,
                    'images/op_reporting.png',
                    false,
                    '',
                    false,
                    $buttons,
                    false,
                    '',
                    60
                );
            }

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

$buttons = [
    'list_reports' => [
        'active' => false,
        'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/report_list.png', true, ['title' => __('Reports list')]).'</a>',
    ],
    'main'         => [
        'active' => false,
        'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=main&action=edit&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/op_reporting.png', true, ['title' => __('Main data')]).'</a>',
    ],
    'list_items'   => [
        'active' => false,
        'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=list_items&action=edit&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/list.png', true, ['title' => __('List items')]).'</a>',
    ],
    'item_editor'  => [
        'active' => false,
        'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&tab=item_editor&action=new&id_report='.$idReport.'&pure='.$pure.'">'.html_print_image('images/pen.png', true, ['title' => __('Item editor')]).'</a>',
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
    'text'   => '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$idReport.'&pure='.$pure.'">'.html_print_image('images/operation.png', true, ['title' => __('View report')]).'</a>',
];

$buttons[$activeTab]['active'] = true;

if ($idReport != 0) {
    $textReportName = $reportName;
} else {
    $temp = $buttons['main'];
    $buttons = null;
    $buttons = [
        'main' => [
            'active' => true,
            'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure.'">'.html_print_image('images/report_list.png', true, ['title' => __('Reports list')]).'</a>',
        ],
    ];
    $textReportName = __('Create Custom Report');
}

// Page header for metaconsole.
if ($enterpriseEnable && defined('METACONSOLE')) {
    // Bread crumbs.
    ui_meta_add_breadcrumb(
        [
            'link' => 'index.php?sec=reporting&sec2=godmode/reporting/reporting_builder&pure='.$pure,
            'text' => __('Reporting'),
        ]
    );

    ui_meta_print_page_header($nav_bar);

    // Print header.
    ui_meta_print_header(__('Reporting').$textReportName, '', $buttons);
} else {
    switch ($activeTab) {
        case 'main':
            $helpers = '';
        break;

        default:
            $helpers = 'reporting_'.$activeTab.'_tab';
        break;
    }

    if ($action !== 'update' && !is_metaconsole()) {
        ui_print_page_header(
            $textReportName,
            'images/op_reporting.png',
            false,
            $helpers,
            false,
            $buttons,
            false,
            '',
            60
        );
    }
}

if ($resultOperationDB !== null) {
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

    if ($action == 'update') {
        $buttons[$activeTab]['active'] = false;
        $activeTab = 'list_items';
        $buttons[$activeTab]['active'] = true;

        if (!is_metaconsole()) {
            ui_print_page_header(
                $textReportName,
                'images/op_reporting.png',
                false,
                $helpers,
                false,
                $buttons,
                false,
                '',
                60
            );
        }
    }
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

enterprise_hook('close_meta_frame');
