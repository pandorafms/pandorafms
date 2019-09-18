<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

check_login();

if (! check_acl($config['id_user'], 0, 'IR') && ! check_acl($config['id_user'], 0, 'IW') && ! check_acl($config['id_user'], 0, 'IM')) {
    // Doesn't have access to this page.
    db_pandora_audit('ACL Violation', 'Trying to access IntegriaIMS ticket creation');
    include 'general/noaccess.php';
    exit;
}

// Get status.
$status_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incidents_status');
$status_incident = [];
get_array_from_csv_data_pair($status_api_call, $status_incident);

// Get group.
$group_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_groups');
$group_incident = [];
get_array_from_csv_data_pair($group_api_call, $group_incident);

// Get priority.
$priority_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_priorities');
$priority_incident = [];
get_array_from_csv_data_pair($priority_api_call, $priority_incident);

// Get resolution.
$resolution_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incidents_resolutions');
$resolution_incident = [];
get_array_from_csv_data_pair($resolution_api_call, $resolution_incident);


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
        'id_incidencia'     => $tickets_csv_array[$key][0],
        'titulo'            => $tickets_csv_array[$key][3],
        'id_grupo'          => $tickets_csv_array[$key][8],
        'estado_resolution' => $tickets_csv_array[$key][6].' / '.$tickets_csv_array[$key][12],
        'prioridad'         => $tickets_csv_array[$key][7],
        'actualizacion'     => $tickets_csv_array[$key][9],
        'id_creator'        => $tickets_csv_array[$key][10],
        'owner'             => $tickets_csv_array[$key][5],
    ];
}


// Header for CSV file.
$header = [
    __('ID Ticket'),
    __('Title'),
    __('Group/Company'),
    __('Status/Resolution'),
    __('Priority'),
    __('Updated/Started'),
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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename='.$filename);

// BOM.
echo pack('C*', 0xEF, 0xBB, 0xBF);

// CSV file.
echo io_safe_output($tickets_csv);
