<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Login check
check_login();

require_once 'include/functions_reports.php';

// Load enterprise extensions
enterprise_include('operation/reporting/custom_reporting.php');

// Header
ui_print_page_header(__('Reporting').' &raquo;  '.__('Custom reporting'), 'images/reporting.png', false, '', false, '');

$reports = reports_get_reports();

if (count($reports) == 0) {
    echo "<div class='nf'>".__('There are no defined reportings').'</div>';
    return;
}

$table->width = '98%';
$table->head = [];
$table->head[0] = __('Report name');
$table->head[1] = __('Description');
$table->head[2] = __('HTML');
$table->head[3] = __('XML');

enterprise_hook('load_custom_reporting_1', [$table]);

$table->align = [];
$table->align[2] = 'center';
$table->align[3] = 'center';
$table->align[4] = 'center';
$table->align[5] = 'center';
$table->data = [];

foreach ($reports as $report) {
    if ($report['private'] && ($report['id_user'] != $config['id_user'] && ! is_user_admin($config['id_user']))) {
        continue;
    }

    $data = [];

    $data[0] = $report['name'];
    $data[1] = $report['description'];
    $data[2] = '<a href="index.php?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$report['id_report'].'">'.html_print_image('images/reporting.png', true).'</a>';
    $data[3] = '<a href="ajax.php?page=operation/reporting/reporting_xml&id='.$report['id_report'].'">'.html_print_image('images/database_lightning.png', true).'</a>';
    // I chose ajax.php because it's supposed to give XML anyway
    enterprise_hook('load_custom_reporting_2');
    array_push($table->data, $data);
}

html_print_table($table);
