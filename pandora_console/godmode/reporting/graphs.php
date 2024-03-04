<?php
/**
 * Reporting graphs.
 *
 * @category   Reporting
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

// Begin.
global $config;

require_once 'include/functions_custom_graphs.php';

// Check user credentials
check_login();

$report_r = check_acl($config['id_user'], 0, 'RR');
$report_w = check_acl($config['id_user'], 0, 'RW');
$report_m = check_acl($config['id_user'], 0, 'RM');

if (!$report_r && !$report_w && !$report_m) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Inventory Module Management'
    );
    include 'general/noaccess.php';
    return;
}



$access = ($report_r == true) ? 'RR' : (($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RR'));

$activeTab = get_parameter('tab', 'main');

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
    $enterpriseEnable = true;
}

$buttons['graph_list'] = [
    'active' => true,
    'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs">'.html_print_image(
        'images/logs@svg.svg',
        true,
        [
            'title' => __('Graph list'),
            'class' => 'main_menu_icon invert_filter',
        ]
    ).'</a>',
];

if ($enterpriseEnable) {
    $buttons = reporting_enterprise_add_template_graph_tabs($buttons);
}

$subsection = '';
switch ($activeTab) {
    case 'main':
        $buttons['graph_list']['active'] = true;
        $subsection = ' &raquo; '.__('Graph list');
    break;

    default:
        $subsection = reporting_enterprise_add_graph_template_subsection($activeTab, $buttons);
    break;
}

switch ($activeTab) {
    case 'main':
        include_once 'godmode/reporting/graphs.php';
    break;

    default:
        reporting_enterprise_select_graph_template_tab($activeTab);
    break;
}

if ($enterpriseEnable) {
    $buttons['graph_container'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container">'.html_print_image(
            'images/graph-container@svg.svg',
            true,
            [
                'title' => __('Graphs containers'),
                'class' => 'invert_filter',
            ]
        ).'</a>',
    ];
}

$delete_graph = (bool) get_parameter('delete_graph');
$view_graph = (bool) get_parameter('view_graph');
$id = (int) get_parameter('id');
$multiple_delete = (bool) get_parameter('multiple_delete', 0);

// Header.
ui_print_standard_header(
    __('List of custom graphs'),
    'images/chart.png',
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
            'label' => __('Custom graphs'),
        ],
    ]
);

// Delete module SQL code
if ($delete_graph) {
    $graph_group = db_get_value('id_group', 'tgraph', 'id_graph', $id);

    if (check_acl_restricted_all($config['id_user'], $graph_group, 'RW')
        || check_acl_restricted_all($config['id_user'], $graph_group, 'RM')
    ) {
        $exist = db_get_value('id_graph', 'tgraph_source', 'id_graph', $id);
        if ($exist) {
            $result = db_process_sql_delete('tgraph_source', ['id_graph' => $id]);

            if ($result) {
                $result = ui_print_success_message(__('Successfully deleted'));
            } else {
                $result = ui_print_error_message(__('Not deleted. Error deleting data'));
            }
        }

        $result = db_process_sql_delete('tgraph', ['id_graph' => $id]);

        $auditMessage = ($result === true) ? 'Delete graph' : 'Fail try to delete graph';

        ui_print_result_message(
            $result,
            __('Successfully deleted'),
            __('Not deleted. Error deleting data')
        );

        db_pandora_audit(
            AUDIT_LOG_REPORT_MANAGEMENT,
            sprintf('%s #%s', $auditMessage, $id)
        );

        echo $result;
    } else {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to delete a graph from access graph builder'
        );
        include 'general/noaccess.php';
        exit;
    }
}

if ($multiple_delete) {
    $ids = (array) get_parameter('delete_multiple', []);

    foreach ($ids as $id) {
        $result = db_process_sql_delete(
            'tgraph',
            ['id_graph' => $id]
        );

        if ($result === false) {
            break;
        }
    }

    if ($result !== false) {
        $result = true;
    } else {
        $result = false;
    }

    $auditMessage = ($result === true) ? 'Multiple delete graph' : 'Fail try to delete graphs';

    $str_ids = implode(',', $ids);

    db_pandora_audit(
        AUDIT_LOG_REPORT_MANAGEMENT,
        sprintf('%s: %s', $auditMessage, $str_ids)
    );

    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('Not deleted. Error deleting data')
    );
}


$id_group = (int) get_parameter('id_group', 0);
$search = trim(get_parameter('search', ''));
$graphs = custom_graphs_get_user($config['id_user'], false, true, $access);
$offset = (int) get_parameter('offset');
$table_aux = new stdClass();
if (isset($strict_user) === false) {
    $strict_user = false;
}

$table_aux->width = '100%';
if (is_metaconsole() === true) {
    $table_aux->class = 'databox filters';
    $table_aux->cellpadding = 0;
    $table_aux->cellspacing = 0;
    $table_aux->colspan[0][0] = 4;
    $table_aux->data[0][0] = '<b>'.__('Group').'</b>';
    $table_aux->data[0][1] = html_print_select_groups(false, $access, true, 'id_group', $id_group, '', '', '', true, false, true, '', false, '', false, false, 'id_grupo', $strict_user).'<br>';
    $table_aux->data[0][2] = '<b>'.__('Free text for search: ').ui_print_help_tip(
        __('Search by report name or description, list matches.'),
        true
    ).'</b>';
    $table_aux->data[0][3] = html_print_input_text('search', $search, '', 30, '', true);
    $table_aux->data[0][6] = html_print_submit_button(__('Search'), 'search_submit', false, 'class="sub upd"', true);
    $filter = "<form class ='' action='index.php?sec=reporting&sec2=godmode/reporting/graphs&id_group=$id_group&pure=$pure'
        method='post'>";
    $filter .= html_print_table($table_aux, true);
    $filter .= '</form>';
    ui_toggle($filter, __('Show Option'));
} else {
    $table_aux->class = 'filter-table-adv';
    $table_aux->size[0] = '50%';

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
            'id_grupo',
            $strict_user
        )
    );
    $table_aux->data[0][1] = html_print_label_input_block(
        __('Free text for search: ').ui_print_help_tip(__('Search by report name or description, list matches.'), true),
        html_print_input_text('search', $search, '', 30, '', true)
    );

    if (isset($pure) === false) {
        $pure = '';
    }

    $searchForm = '<form action="index.php?sec=reporting&sec2=godmode/reporting/graphs&id_group='.$id_group.'&pure='.$pure.'"method="post">';
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
        true,
        false,
        '',
        'white-box-content',
        'box-flat white_table_graph fixed_filter_bar'
    );
}

// Show only selected groups.
if ($id_group > 0) {
    $group = ["$id_group" => $id_group];
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
    $group = ["$id_group" => $id_group];
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

if ($id_group != null || $search != null) {
    $graphs = custom_graphs_search($id_group, $search);
}

if (!empty($graphs)) {
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->cellpadding = 0;
    $table->cellspacing = 0;
    $table->align = [];
    $table->head = [];

    $table->head[0] = __('Graph name');
    $table->head[1] = __('Description');
    $table->head[2] = __('Number of Graphs');
    $table->head[3] = __('Group');
    $table->size[0] = '30%';
    $table->size[2] = '200px';
    $table->size[3] = '200px';
    $table->align[2] = 'left';
    $table->align[3] = 'left';
    $op_column = false;
    if ($report_w || $report_m) {
        $op_column = true;
        $table->align[4] = 'left';
        $table->head[4] = __('Op.');
        $table->size[4] = '90px';
    }

    if ($report_w || $report_m) {
        $table->align[5] = 'left';
        $table->head[5] = html_print_checkbox('all_delete', 0, false, true, false);
        $table->size[5] = '20px';
    }

    $table->data = [];

    $result_graphs = array_slice($graphs, $offset, $config['block_size']);

    foreach ($result_graphs as $graph) {
        $data = [];

        $data[0] = '<a href="index.php?sec=reporting&sec2=operation/reporting/graph_viewer&view_graph=1&id='.$graph['id_graph'].'">'.ui_print_truncate_text(
            $graph['name'],
            70
        ).'</a>';

        $data[1] = ui_print_truncate_text($graph['description'], 70);

        $data[2] = $graph['graphs_count'];
        $data[3] = ui_print_group_icon($graph['id_group'], true);

        $data[4] = '';
        $table->cellclass[][4] = 'table_action_buttons';
        if (($report_w || $report_m)) {
            $data[4] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&id='.$graph['id_graph'].'">'.html_print_image(
                'images/edit.svg',
                true,
                ['class' => 'invert_filter  main_menu_icon']
            ).'</a>';
        }

        $data[5] = '';
        if (check_acl_restricted_all($config['id_user'], $graph['id_group'], 'RM')) {
            $data[4] .= '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs&delete_graph=1&id='.$graph['id_graph'].'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
            return false;">'.html_print_image(
                'images/delete.svg',
                true,
                [
                    'alt'   => __('Delete'),
                    'title' => __('Delete'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ).'</a>';

            $data[5] .= html_print_checkbox_extended(
                'delete_multiple[]',
                $graph['id_graph'],
                false,
                false,
                '',
                [
                    'class'      => 'check_delete mrgn_lft_2px check_delete',
                    'form'       => 'form_delete',
                    'data-value' => $graph['id_graph'],
                ],
                true
            );
        }

        array_push($table->data, $data);
    }

    if (is_metaconsole() === true) {
        if (!empty($result_graphs)) {
            echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graphs'>";
            html_print_input_hidden('multiple_delete', 1);
            html_print_table($table);
            ui_pagination(count($graphs), false, 0, 0, false, 'offset', true, '');
            echo "<div class='right'>";
            html_print_submit_button(__('Delete'), 'delete_btn', false, 'class="sub delete"');
            echo '</form>';
        }


        echo "<div class='right'>";
        if ($report_w || $report_m) {
            echo '<form method="post" class="right" action="index.php?sec=reporting&sec2=godmode/reporting/graph_builder">';
            html_print_submit_button(__('Create graph'), 'create', false, 'class="sub next mrgn_right_5px"');
            echo '</form>';
        }
    } else {
        if ($report_w || $report_m) {
            $ActionButtons[] = '<form method="post" class="right mrgn_lft_10px" action="index.php?sec=reporting&sec2=godmode/reporting/graph_builder">';
            $ActionButtons[] = html_print_submit_button(
                __('Create graph'),
                'create',
                false,
                [
                    'class' => 'sub ok',
                    'icon'  => 'next',
                ],
                true
            );
            $ActionButtons[] = '</form>';
        }

        if (!empty($result_graphs)) {
            $ActionButtons[] = "<form method='post' id='form_delete' action='index.php?sec=reporting&sec2=godmode/reporting/graphs'>";
            $ActionButtons[] = html_print_input_hidden('multiple_delete', 1, true);
            $ActionButtons[] = html_print_submit_button(
                __('Delete'),
                'delete_btn',
                false,
                [
                    'class' => 'secondary',
                    'icon'  => 'delete',
                ],
                true
            );
            $ActionButtons[] = '</form>';

            $offset = (int) get_parameter('offset', 0);
            $block_size = (int) $config['block_size'];

            $tablePagination = ui_pagination(
                count($graphs),
                false,
                $offset,
                $block_size,
                true,
                'offset',
                false
            );
        }

        // FALTA METER EL PRINT TABLE.
        html_print_table($table);

        if (is_metaconsole() === true) {
            html_print_action_buttons(
                implode('', $ActionButtons),
                ['type' => 'form_action']
            );
        } else {
            html_print_action_buttons(
                implode('', $ActionButtons),
                [
                    'type'          => 'form_action',
                    'right_content' => $tablePagination,
                ]
            );
        }
    }

        echo '</div>';

    echo '</div>';
} else {
    include_once $config['homedir'].'/general/first_task/custom_graphs.php';
}

?>

<script type="text/javascript">

    $( document ).ready(function() {

        $('[id^=checkbox-delete_multiple]').click(function(){
            if($(this).prop("checked") === false ){
                $(this).prop("checked", false);
            } else {
                $(this).prop("checked", true);
            }
        });

        $('#checkbox-all_delete').click(function(){
            if ($("#checkbox-all_delete").prop("checked") === true) {
                $("[id^=checkbox-delete_multiple]").prop("checked", true);
            }
            else{
                $("[id^=checkbox-delete_multiple]").prop("checked", false);
            }
        });

    });
    
</script>

