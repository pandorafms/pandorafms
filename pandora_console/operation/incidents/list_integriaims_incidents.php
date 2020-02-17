<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

require_once 'include/functions_integriaims.php';

check_login();

if (! check_acl($config['id_user'], 0, 'IR')) {
    // Doesn't have access to this page.
    db_pandora_audit('ACL Violation', 'Trying to access IntegriaIMS ticket creation');
    include 'general/noaccess.php';
    exit;
}

// Header tabs.
$onheader = integriaims_tabs('list_tab');
ui_print_page_header(__('Integria IMS Tickets'), '', false, '', false, $onheader);

// Check if Integria integration enabled.
if ($config['integria_enabled'] == 0) {
    ui_print_error_message(__('Integria integration must be enabled in Pandora setup'));
    return;
}

// Check connection to Integria IMS API.
$has_connection = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_login', []);

if ($has_connection === false) {
    ui_print_error_message(__('Integria IMS API is not reachable'));
    return;
}

// Styles.
ui_require_css_file('integriaims');

// Get parameters for filters.
$incident_text = (string) get_parameter('incident_text', '');
$incident_status = (int) get_parameter('incident_status', 0);
$incident_group = (int) get_parameter('incident_group', 1);
$incident_owner = (string) get_parameter('incident_owner', '');
$incident_creator = (string) get_parameter('incident_creator', '');
$incident_priority = (int) get_parameter('incident_priority', -1);
$incident_resolution = (string) get_parameter('incident_resolution', '');
$created_from = (string) get_parameter('created_from', '');
$created_to = (string) get_parameter('created_to', '');

$offset = (int) get_parameter('offset');

$delete_incident = get_parameter('delete_incident');
if ($delete_incident) {
    // Call Integria IMS API method to delete an incident.
    $result_api_call_delete = integria_api_call(
        $config['integria_hostname'],
        $config['integria_user'],
        $config['integria_pass'],
        $config['integria_api_pass'],
        'delete_incident',
        [$delete_incident]
    );

    $incident_deleted_ok = ($result_api_call_delete !== false) ? true : false;

    ui_print_result_message(
        $incident_deleted_ok,
        __('Successfully deleted'),
        __('Could not be deleted')
    );
}

// Full url with all filters.
$url = ui_get_full_url(
    'index.php?sec=incident&sec2=operation/incidents/list_integriaims_incidents&incident_text='.$incident_text.'&incident_status='.$incident_status.'&incident_group='.$incident_group.'&incident_owner='.$incident_owner.'&incident_creator='.$incident_creator.'&incident_priority='.$incident_priority.'&incident_resolution='.$incident_resolution.'&created_from='.$created_from.'&created_to='.$created_to.'&offset='.$offset
);


// ---- FILTERS ----
// API calls to fill the filters.
$status_incident = integriaims_get_details('status');
$group_incident = integriaims_get_details('group');
$priority_incident = integriaims_get_details('priority');
$resolution_incident = integriaims_get_details('resolution');


// TABLE FILTERS.
$table = new StdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->styleTable = 'margin-bottom:0px';
$table->cellpadding = '0';
$table->cellspacing = '0';
$table->data = [];

$table->data[0][0] = __('Text filter');
$table->data[0][1] = html_print_input_text('incident_text', $incident_text, '', 30, 100, true);

$table->data[0][2] = __('Status');
$table->data[0][3] = html_print_select(
    $status_incident,
    'incident_status',
    $incident_status,
    '',
    __('All'),
    0,
    true
);

$table->data[0][4] = __('Group');
$table->data[0][5] = html_print_select(
    $group_incident,
    'incident_group',
    $incident_group,
    '',
    __('All'),
    1,
    true
);

$table->data[1][0] = __('Owner');
$table->data[1][1] = html_print_autocomplete_users_from_integria('incident_owner', $incident_owner, true);

$table->data[1][2] = __('Creator');
$table->data[1][3] = html_print_autocomplete_users_from_integria('incident_creator', $incident_creator, true);

$table->data[1][4] = __('Priority');
$table->data[1][5] = html_print_select(
    $priority_incident,
    'incident_priority',
    $incident_priority,
    '',
    __('All'),
    -1,
    true
);

$table->data[2][0] = __('Resolution');
$table->data[2][1] = html_print_select(
    $resolution_incident,
    'incident_resolution',
    $incident_resolution,
    '',
    __('All'),
    '',
    true
);

// TODO: field type date.
$table->data[2][2] = __('Date');
$table->data[2][3] = html_print_input_text_extended(
    'created_from',
    $created_from,
    'created_from',
    '',
    12,
    50,
    false,
    '',
    'placeholder="'.__('Created from').'"',
    true
);
$table->data[2][3] .= html_print_input_text_extended(
    'created_to',
    $created_to,
    'created_to',
    '',
    12,
    50,
    false,
    '',
    'style="margin-left:5px;" placeholder="'.__('Created to').'"',
    true
);

// TODO: image of Integria IMS.
$table->data[2][4] = '';
$table->data[2][5] = '';


// Send filters to get_tickets_integriaims().
$tickets_filters = [
    'incident_text'       => $incident_text,
    'incident_status'     => $incident_status,
    'incident_group'      => $incident_group,
    'incident_owner'      => $incident_owner,
    'incident_creator'    => $incident_creator,
    'incident_priority'   => $incident_priority,
    'incident_resolution' => $incident_resolution,
    'created_from'        => $created_from,
    'created_to'          => $created_to,
];

// Data to export to csv file.
$decode_csv = base64_encode(json_encode($tickets_filters));


// ---- PRINT TABLE FILTERS ----
$integria_incidents_form = '<form method="post" action="'.$url.'" style="padding:0px;">';
$integria_incidents_form .= html_print_table($table, true);
$integria_incidents_form .= '<div style="width:100%; text-align:right;">';
$integria_incidents_form .= '<div style="float:right; margin-left: 5px;">'.html_print_button(
    __('Export to CSV'),
    'csv_export',
    false,
    "location.href='operation/incidents/integriaims_export_csv.php?tickets_filters=$decode_csv'",
    'class="sub next"',
    true
).'</div>';
$integria_incidents_form .= '<div>'.html_print_submit_button(__('Filter'), 'filter_button', false, 'class="sub filter"', true).'</div>';
$integria_incidents_form .= '</div>';
$integria_incidents_form .= '</form>';

ui_toggle($integria_incidents_form, __('Filter'), '', '', false);

/*
 * Order api call 'get_incidents'.
 *
 * resolution    = $array_get_incidents[$key][12]
 * id_incidencia = $array_get_incidents[$key][0]
 * titulo        = $array_get_incidents[$key][3]
 * id_grupo      = $array_get_incidents[$key][8]
 * estado        = $array_get_incidents[$key][6]
 * prioridad     = $array_get_incidents[$key][7]
 * actualizacion = $array_get_incidents[$key][9]
 * id_creator    = $array_get_incidents[$key][10]
 *
 */

// ---- LIST OF INCIDENTS ----
// Get list of incidents.
$array_get_incidents = get_tickets_integriaims($tickets_filters);

// Prepare pagination.
$incidents_limit = $config['block_size'];
$incidents_paginated = array_slice($array_get_incidents, $offset, $incidents_limit, true);

// TABLE INCIDENTS.
$table = new stdClass();
$table->width = '100%';
$table->class = 'info_table';
$table->head = [];

$table->head[0] = __('ID');
$table->head[1] = __('Ticket');
$table->head[2] = __('Group/Company');
$table->head[3] = __('Status/Resolution');
$table->head[4] = __('Prior');
$table->head[5] = __('Updated/Started');
$table->head[6] = __('Creator');
$table->head[7] = __('Owner');
if (check_acl($config['id_user'], 0, 'IW') || check_acl($config['id_user'], 0, 'IM')) {
    $table->head[8] = '';
}

$table->data = [];
$i = 0;

foreach ($incidents_paginated as $key => $value) {
    if ($array_get_incidents[$key][6] == 0) {
        $status_incident[$array_get_incidents[$key][6]] = __('None');
    }

    if ($array_get_incidents[$key][12] == 0) {
        $resolution_incident[$array_get_incidents[$key][12]] = __('None');
    }

    $table->data[$i][0] = '#'.$array_get_incidents[$key][0];
    $table->data[$i][1] = '<a href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$array_get_incidents[$key][0]).'">';
    $table->data[$i][1] .= $array_get_incidents[$key][3];
    $table->data[$i][1] .= '</a>';
    $table->data[$i][2] = $group_incident[$array_get_incidents[$key][8]];
    $table->data[$i][3] = $status_incident[$array_get_incidents[$key][6]].' / '.$resolution_incident[$array_get_incidents[$key][12]];
    $table->data[$i][4] = ui_print_integria_incident_priority($array_get_incidents[$key][7], $priority_incident[$array_get_incidents[$key][7]]);
    $table->data[$i][5] = $array_get_incidents[$key][9].' / '.$array_get_incidents[$key][1];
    $table->data[$i][6] = $array_get_incidents[$key][10];
    $table->data[$i][7] = $array_get_incidents[$key][5];
    $table->data[$i][8] = '';
    $table->cellclass[$i][8] = 'action_buttons';
    if (check_acl($config['id_user'], 0, 'IW')) {
        $table->data[$i][8] .= '<a href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&incident_id='.$array_get_incidents[$key][0]).'">';
        $table->data[$i][8] .= html_print_image('images/config.png', true, ['title' => __('Edit')]);
        $table->data[$i][8] .= '</a>';
    }

    if (check_acl($config['id_user'], 0, 'IM')) {
        $table->data[$i][8] .= '<a id="link_delete_incident" href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/list_integriaims_incidents&delete_incident='.$array_get_incidents[$key][0]).'"        
        onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">';
        $table->data[$i][8] .= html_print_image('images/cross.png', true, ['title' => __('Delete')]);
        $table->data[$i][8] .= '</a>';
    }

    $i++;
}

// Show table incidents.
ui_pagination(count($array_get_incidents), $url, $offset);
if (empty($table->data) === true) {
    ui_print_info_message(['no_close' => true, 'message' => __('No tickets to show').'.' ]);
} else {
    html_print_table($table);
    ui_pagination(count($array_get_incidents), $url, $offset, 0, false, 'offset', true, 'pagination-bottom');
}

// Show button to create incident.
if (check_acl($config['id_user'], 0, 'IR')) {
    echo '<form method="POST" action="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident').'">';
        echo '<div style="width: 100%; text-align:right;">';
            html_print_submit_button(__('Create'), 'create_new_incident', false, 'class="sub next"');
        echo '</div>';
    echo '</form>';
}

// Datapicker library for show calendar.
ui_require_jquery_file('ui.datepicker-'.get_user_language(), 'include/javascript/i18n/');
?>


<script language="javascript" type="text/javascript">
    $(document).ready( function() {
        $("#created_from, #created_to").datepicker({
            dateFormat: "<?php echo DATE_FORMAT_JS; ?>"
        });  
    });
</script>