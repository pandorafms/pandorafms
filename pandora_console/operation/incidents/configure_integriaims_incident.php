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

check_login();

if (! check_acl($config['id_user'], 0, 'IR') && ! check_acl($config['id_user'], 0, 'IW') && ! check_acl($config['id_user'], 0, 'IM')) {
    // Doesn't have access to this page.
    db_pandora_audit('ACL Violation', 'Trying to access IntegriaIMS ticket creation');
    include 'general/noaccess.php';
    exit;
}

ui_print_page_header(__('Create Integria IMS Incident'), '', false, '', false, '');

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

// If everything OK, get parameters from Integria IMS API.
$group_values = [];
$integria_criticity_values = [];
$integria_users_values = [];
$integria_types_values = [];

$integria_groups_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_groups', []);

get_array_from_csv_data($integria_groups_csv, $group_values);

$integria_criticity_levels_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_priorities', []);

get_array_from_csv_data($integria_criticity_levels_csv, $integria_criticity_values);

$integria_users_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_users', []);

$csv_array = explode("\n", $integria_users_csv);

foreach ($csv_array as $csv_line) {
    if (!empty($csv_line)) {
        $integria_users_values[$csv_line] = $csv_line;
    }
}

$integria_types_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_types', []);

get_array_from_csv_data($integria_types_csv, $integria_types_values);

$event_id = (int) get_parameter('from_event');
$create_incident = (int) get_parameter('create_incident', 0);
$incident_group_id = (int) get_parameter('default_group');
$incident_default_criticity_id = (int) get_parameter('default_criticity');
$incident_default_owner = (int) get_parameter('default_owner');
$incident_type = (int) get_parameter('incident_type');
$incident_title = events_get_field_value_by_event_id($event_id, get_parameter('incident_title'));
$incident_content = events_get_field_value_by_event_id($event_id, get_parameter('incident_content'));

if ($create_incident === 1) {
    // Call Integria IMS API method to create an incident.
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'create_incident', [$incident_title, $incident_group_id, $incident_default_criticity_id, $incident_content, '', '0', '', 'admin', '0', '1']);

    $incident_created_ok = ($result_api_call != false) ? true : false;

    ui_print_result_message(
        $incident_created_ok,
        __('Successfully created'),
        __('Could not be created')
    );
}

$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filters';
$table->head = [];

$table->data = [];
$table->size = [];
$table->size = [];
$table->size[0] = '15%';
$table->size[1] = '90%';

$table->data[0][0] = __('Group');
$table->data[0][1] = html_print_select(
    $group_values,
    'default_group',
    $config['default_group'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[1][0] = __('Default Criticity');
$table->data[1][1] = html_print_select(
    $integria_criticity_values,
    'default_criticity',
    $config['default_criticity'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[2][0] = __('Default Owner');
$table->data[2][1] = html_print_select(
    $integria_users_values,
    'default_owner',
    $config['default_owner'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[0][2] = __('Incident Type');
$table->data[0][3] = html_print_select(
    $integria_types_values,
    'incident_type',
    $config['incident_type'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);

$table->data[1][2] = __('Incident title').ui_print_help_icon('response_macros', true);
$table->data[1][3] = html_print_input_text(
    'incident_title',
    $config['incident_title'],
    __('Name'),
    50,
    100,
    true,
    false,
    true
);

$table->data[2][2] = __('Incident content').ui_print_help_icon('response_macros', true);
$table->data[2][3] = html_print_input_text(
    'incident_content',
    $config['incident_content'],
    '',
    50,
    100,
    true,
    false,
    true
);

echo '<form name="create_integria_incident_form" method="POST">';
html_print_table($table);
html_print_input_hidden('create_incident', 1);
echo '<div style="width: 100%; text-align:right;">';
html_print_submit_button(__('Create'), 'accion', false, 'class="sub wand"');
echo '</div>';
echo '</form>';
