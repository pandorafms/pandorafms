<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/functions_graph.php';

check_login();

$enterprise_include = enterprise_include_once('godmode/admin_access_logs.php');

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to access audit view');
    include 'general/noaccess.php';
    exit;
}

$offset = (int) get_parameter('offset');
$filter_type = (string) get_parameter('filter_type');
$filter_user = (string) get_parameter('filter_user');
$filter_text = (string) get_parameter('filter_text');
$filter_period = get_parameter('filter_period', null);
$filter_period = ($filter_period !== null) ? (int) $filter_period : 24;
$filter_ip = (string) get_parameter('filter_ip');

$filter_query = '&filter_type='.$filter_type.'&filter_user='.$filter_user.'&filter_text='.$filter_text.'&filter_period='.$filter_period.'&filter_ip='.$filter_ip;

$csv_url = ui_get_full_url(false, false, false, false).'index.php?sec=gextensions&sec2=godmode/audit_log_csv'.$filter_query;
$csv_img = html_print_image('images/csv_mc.png', true, ['title' => __('Export to CSV')]);
$header_buttons = [
    'csv' => [
        'active' => false,
        'text'   => '<a href="'.$csv_url.'">'.$csv_img.'</a>',
    ],
];

ui_print_page_header(__('%s audit', get_product_name()).' &raquo; '.__('Review Logs'), 'images/gm_log.png', false, '', true, $header_buttons);

$table = new stdClass();
$table->class = 'databox filters';
$table->cellstyle = [];
$table->cellstyle[0] = [];
$table->cellstyle[1] = [];
$table->cellstyle[0][0] = 'text-align: right;';
$table->cellstyle[0][1] = 'text-align: left;';
$table->cellstyle[0][2] = 'text-align: right;';
$table->cellstyle[0][3] = 'text-align: left;';
$table->cellstyle[0][4] = 'text-align: right;';
$table->cellstyle[0][5] = 'text-align: left;';
$table->cellstyle[1][0] = 'text-align: right;';
$table->cellstyle[1][1] = 'text-align: left;';
$table->cellstyle[1][2] = 'text-align: right;';
$table->cellstyle[1][3] = 'text-align: left;';
$table->cellstyle[1][5] = 'text-align: right;';
$table->data = [];

$data = [];

$data[0] = '<b>'.__('Search').'</b>';
$data[1] = html_print_input_text('filter_text', $filter_text, __('Free text for search (*)'), 20, 40, true);

$data[2] = '<b>'.__('Max. hours old').'</b>';
$data[3] = html_print_input_text('filter_period', $filter_period, __('Max. hours old'), 3, 6, true);

$data[4] = '<b>'.__('IP').'</b>';
$data[5] = html_print_input_text('filter_ip', $filter_ip, __('IP'), 15, 15, true);

$table->data[0] = $data;
$data = [];

$actions_sql = 'SELECT DISTINCT(accion), accion AS text FROM tsesion';
$data[0] = '<b>'.__('Action').'</b>';
$data[1] = html_print_select_from_sql($actions_sql, 'filter_type', $filter_type, '', __('All'), '', true);

$users_sql = 'SELECT id_user, id_user AS text FROM tusuario';
$data[2] = '<b>'.__('User').'</b>';
$data[3] = html_print_select_from_sql($users_sql, 'filter_user', $filter_user, '', __('All'), '', true);

$data[4] = '';
$data[5] = html_print_submit_button(__('Filter'), 'filter', false, 'class="sub search"', true);

$table->data[1] = $data;

$form = '<form name="query_sel" method="post" action="index.php?sec=glog&sec2=godmode/admin_access_logs">';
$form .= html_print_table($table, true);
$form .= '</form>';
ui_toggle($form, __('Filter'), '', '', false);

$filter = '1=1';

if (!empty($filter_type)) {
    $filter .= sprintf(" AND accion = '%s'", $filter_type);
}

if (!empty($filter_user)) {
    $filter .= sprintf(" AND id_usuario = '%s'", $filter_user);
}

if (!empty($filter_text)) {
    $filter .= sprintf(" AND (accion LIKE '%%%s%%' OR descripcion LIKE '%%%s%%')", $filter_text, $filter_text);
}

if (!empty($filter_ip)) {
    $filter .= sprintf(" AND ip_origen LIKE '%%%s%%'", $filter_ip);
}

if (!empty($filter_period)) {
    switch ($config['dbtype']) {
        case 'mysql':
            $filter .= ' AND fecha >= DATE_ADD(NOW(), INTERVAL -'.$filter_period.' HOUR)';
        break;

        case 'postgresql':
            $filter .= ' AND fecha >= NOW() - INTERVAL \''.$filter_period.' HOUR \'';
        break;

        case 'oracle':
            $filter .= ' AND fecha >= (SYSTIMESTAMP - INTERVAL \''.$filter_period.'\' HOUR)';
        break;
    }
}

$count_sql = sprintf('SELECT COUNT(*) FROM tsesion WHERE %s', $filter);
$count = (int) db_get_value_sql($count_sql);
$url = 'index.php?sec=godmode&sec2=godmode/admin_access_logs'.$filter_query;
ui_pagination($count, $url);

switch ($config['dbtype']) {
    case 'mysql':
        $sql = sprintf(
            'SELECT *
			FROM tsesion
			WHERE %s
			ORDER BY fecha DESC
			LIMIT %d, %d',
            $filter,
            $offset,
            $config['block_size']
        );
    break;

    case 'postgresql':
        $sql = sprintf(
            'SELECT *
			FROM tsesion
			WHERE %s
			ORDER BY fecha DESC
			LIMIT %d OFFSET %d',
            $filter,
            $config['block_size'],
            $offset
        );
    break;

    case 'oracle':
        $set = [];
        $set['limit'] = $config['block_size'];
        $set['offset'] = $offset;
        $sql = sprintf(
            'SELECT *
			FROM tsesion
			WHERE %s
			ORDER BY fecha DESC',
            $filter
        );
        $result = oracle_recode_query($sql, $set);
    break;
}

$result = db_get_all_rows_sql($sql);
if (empty($result)) {
    $result = [];
}

$table = new stdClass();
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = '100%';
$table->class = 'info_table';
$table->size = [];
$table->data = [];
$table->head = [];
$table->align = [];
$table->rowclass = [];

$table->head[0] = __('User');
$table->head[1] = __('Action');
$table->head[2] = __('Date');
$table->head[3] = __('Source IP');
$table->head[4] = __('Comments');
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
    $table->head[5] = enterprise_hook('tableHeadEnterpriseAudit', ['title1']);
    $table->head[6] = enterprise_hook('tableHeadEnterpriseAudit', ['title2']);
}

$table->size[0] = 80;
$table->size[2] = 130;
$table->size[3] = 100;
$table->size[4] = 200;
if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
    $table->size[5] = enterprise_hook('tableHeadEnterpriseAudit', ['size1']);
    $table->size[6] = enterprise_hook('tableHeadEnterpriseAudit', ['size2']);
    $table->align[5] = enterprise_hook('tableHeadEnterpriseAudit', ['align']);
    $table->align[6] = enterprise_hook('tableHeadEnterpriseAudit', ['align2']);
}

$table->colspan = [];
$table->rowstyle = [];

$rowPair = true;
$iterator = 0;

// Get data
foreach ($result as $row) {
    $iterator++;

    $table->rowclass[] = $rowPair ? 'rowPair' : 'rowOdd';
    $rowPair = !$rowPair;

    $data = [];
    $data[0] = io_safe_output($row['id_usuario']);
    $data[1] = ui_print_session_action_icon($row['accion'], true).$row['accion'];
    $data[2] = ui_print_help_tip(date($config['date_format'], $row['utimestamp']), true).ui_print_timestamp($row['utimestamp'], true);
    $data[3] = io_safe_output($row['ip_origen']);
    $data[4] = io_safe_output($row['descripcion']);

    if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
        $data[5] = enterprise_hook('cell1EntepriseAudit', [$row['id_sesion']]);
        $data[6] = enterprise_hook('cell2EntepriseAudit', [$row['id_sesion']]);
    }

    $table->data[] = $data;

    if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
        rowEnterpriseAudit($table, $iterator, $row['id_sesion']);
    }
}

foreach ($table->rowclass as $key => $value) {
    if (strpos($value, 'limit_scroll') !== false) {
        $table->colspan[$key] = [7];
    } else {
        if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
            $table->cellclass[$key][6] = 'action_buttons';
        }
    }
}

html_print_table($table);
ui_pagination($count, $url, 0, 0, false, 'offset', true, 'pagination-bottom');

if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
    enterprise_hook('enterpriseAuditFooter');
}
