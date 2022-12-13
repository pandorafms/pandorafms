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
// Load global vars
global $config;

require_once '../../include/config.php';
require_once '../../include/functions.php';
require_once '../../include/functions_integriaims.php';

check_login();

// API calls.
$status_incident = integriaims_get_details('status');
$group_incident = integriaims_get_details('group');
$priority_incident = integriaims_get_details('priority');
$resolution_incident = integriaims_get_details('resolution');


// Get data to export.
$tickets_filters = json_decode(
    base64_decode(
        get_parameter('tickets_filters', '')
    ),
    true
);

$tickets_csv_array = get_tickets_integriaims($tickets_filters);


// Build a new array to show only the fields in the table.
$tickets_csv_array_filter = [];
foreach ($tickets_csv_array as $key => $value) {
    // Status.
    if ($tickets_csv_array[$key][6] == 0) {
        $tickets_csv_array[$key][6] = 'None';
    } else {
        $tickets_csv_array[$key][6] = $status_incident[$tickets_csv_array[$key][6]];
    }

    // Priority.
    $tickets_csv_array[$key][7] = $priority_incident[$tickets_csv_array[$key][7]];

    // Group.
    $tickets_csv_array[$key][8] = $group_incident[$tickets_csv_array[$key][8]];

    // Resolution.
    if ($tickets_csv_array[$key][12] == 0) {
        $tickets_csv_array[$key][12] = 'None';
    } else {
        $tickets_csv_array[$key][12] = $resolution_incident[$tickets_csv_array[$key][12]];
    }

    $tickets_csv_array_filter[$key] = [
        'id_incidencia' => $tickets_csv_array[$key][0],
        'titulo'        => $tickets_csv_array[$key][3],
        'id_grupo'      => $tickets_csv_array[$key][8],
        'estado'        => $tickets_csv_array[$key][6],
        'resolution'    => $tickets_csv_array[$key][12],
        'prioridad'     => $tickets_csv_array[$key][7],
        'actualizacion' => $tickets_csv_array[$key][9],
        'inicio'        => $tickets_csv_array[$key][1],
        'id_creator'    => $tickets_csv_array[$key][10],
        'owner'         => $tickets_csv_array[$key][5],
    ];
}

// Header for CSV file.
$header = [
    __('ID Ticket'),
    __('Title'),
    __('Group/Company'),
    __('Status'),
    __('Resolution'),
    __('Priority'),
    __('Updated'),
    __('Started'),
    __('Creator'),
    __('Owner'),
];

$header_csv = '';
foreach ($header as $key => $value) {
    $header_csv .= $value.',';
}

$header_csv = io_safe_output($header_csv).PHP_EOL;


// Join header and content.
$tickets_csv = '';
foreach ($tickets_csv_array_filter as $key => $value) {
    $tickets_csv .= implode(',', $tickets_csv_array_filter[$key]).PHP_EOL;
}

$tickets_csv = $header_csv.$tickets_csv;


// Create csv file.
$filename = 'tickets_export-'.date('Ymd').'-'.date('His').'.csv';

ob_clean();

// Set cookie for download control.
setDownloadCookieToken();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);

// BOM.
echo pack('C*', 0xEF, 0xBB, 0xBF);

// CSV file.
echo io_safe_output($tickets_csv);
