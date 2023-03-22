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

check_login();

require_once $config['homedir'].'/include/functions_integriaims.php';

$update = (isset($_GET['incident_id']) === true);

// Header tabs.
$onheader = integriaims_tabs('create_tab', $_GET['incident_id']);
if ($update) {
    $title_header = __('Update Integria IMS Ticket');
} else {
    $title_header = __('Create Integria IMS Ticket');
}

ui_print_standard_header(
    $title_header,
    '',
    false,
    '',
    false,
    $onheader,
    [
        [
            'link'  => '',
            'label' => __('Issues'),
        ],
        [
            'link'  => '',
            'label' => $title_header,
        ],
    ]
);

// Check if Integria integration enabled.
if ($config['integria_enabled'] == 0) {
    ui_print_error_message(__('In order to access ticket management system, integration with Integria IMS must be enabled and properly configured'));
    return;
}

// Check connection to Integria IMS API.
$has_connection = integria_api_call(null, null, null, null, 'get_login');

if ($has_connection === false) {
    ui_print_error_message(__('Integria IMS API is not reachable'));
    return;
}

// Styles.
ui_require_css_file('integriaims');

// If everything OK, get parameters from Integria IMS API in order to populate combos.
$integria_group_values = [];
$integria_criticity_values = [];
$integria_users_values = [];
$integria_types_values = [];
$integria_status_values = [];

$integria_groups_csv = integria_api_call(null, null, null, null, 'get_groups');

get_array_from_csv_data_pair($integria_groups_csv, $integria_group_values);

$integria_status_csv = integria_api_call(null, null, null, null, 'get_incidents_status');

get_array_from_csv_data_pair($integria_status_csv, $integria_status_values);

$integria_criticity_levels_csv = integria_api_call(null, null, null, null, 'get_incident_priorities');

get_array_from_csv_data_pair($integria_criticity_levels_csv, $integria_criticity_values);

$integria_users_csv = integria_api_call(null, null, null, null, 'get_users');

$csv_array = explode("\n", $integria_users_csv);

foreach ($csv_array as $csv_line) {
    if (!empty($csv_line)) {
        $integria_users_values[$csv_line] = $csv_line;
    }
}

$integria_types_csv = integria_api_call(null, null, null, null, 'get_types');

get_array_from_csv_data_pair($integria_types_csv, $integria_types_values);

$integria_resolution_csv = integria_api_call(null, null, null, null, 'get_incidents_resolutions');

get_array_from_csv_data_pair($integria_resolution_csv, $integria_resolution_values);

$event_id = (int) get_parameter('from_event');
$incident_id_edit = (int) get_parameter('incident_id');
$create_incident = (bool) get_parameter('create_incident', 0);
$update_incident = (bool) get_parameter('update_incident', 0);
$incident_group_id = (int) get_parameter('group');
$incident_criticity_id = (int) get_parameter('criticity');
$incident_owner = get_parameter('owner');
$incident_type = (int) get_parameter('type');
$incident_creator = get_parameter('creator');
$incident_status = (int) get_parameter('status');
$incident_resolution = (int) get_parameter('resolution');
$incident_title = events_get_field_value_by_event_id($event_id, get_parameter('incident_title'));
$incident_content = events_get_field_value_by_event_id($event_id, get_parameter('incident_content'));
$file_description = get_parameter('file_description');

// Separator conversions.
$incident_title = str_replace(',', ':::', $incident_title);
$incident_content = str_replace(',', ':::', $incident_content);

// Perform action.
if ($create_incident === true) {
    // Disregard incident resolution unless status is 'closed'.
    if ($incident_status !== 7) {
        $incident_resolution = 0;
    }

    // Call Integria IMS API method to create an incident.
    $result_api_call = integria_api_call(null, null, null, null, 'create_incident', [$incident_title, $incident_group_id, $incident_criticity_id, $incident_content, '', $incident_type, '', $incident_owner, '0', $incident_status, '', $incident_resolution], false, '', ',');

    if ($userfile !== '' && $result_api_call !== false) {
        integriaims_upload_file('userfile', $result_api_call, $file_description);
    }

    // Necessary to explicitly set true if not false because function returns result of api call in case of success instead of true value.
    $incident_created_ok = ($result_api_call != false) ? true : false;

    ui_print_result_message(
        $incident_created_ok,
        __('Successfully created in Integria IMS'),
        __('Could not be created in Integria IMS')
    );
} else if ($update_incident === true) {
    // Disregard incident resolution unless status is 'closed'.
    if ($incident_status !== 7) {
        $incident_resolution = 0;
    }

    // Call Integria IMS API method to update an incident.
    $result_api_call = integria_api_call(null, null, null, null, 'update_incident', [$incident_id_edit, $incident_title, $incident_content, '', $incident_group_id, $incident_criticity_id, $incident_resolution, $incident_status, $incident_owner, 0, $incident_type], false, '', ',');

    if ($userfile !== '') {
        integriaims_upload_file('userfile', $incident_id_edit, $file_description);
    }

    // Necessary to explicitly set true if not false because function returns api call result in case of success instead of true value.
    $incident_updated_ok = ($result_api_call != false) ? true : false;

    ui_print_result_message(
        $incident_updated_ok,
        __('Successfully updated in Integria IMS'),
        __('Could not be updated in Integria IMS')
    );
}

// If incident id is specified, retrieve incident values from api to populate combos with such values.
if ($update) {
    // Call Integria IMS API method to get details of an incident given its id.
    $result_api_call = integria_api_call(null, null, null, null, 'get_incident_details', [$incident_id_edit], false, '', ',');

    // API call does not return indexes, therefore future modifications of API function in Integria IMS may lead to inconsistencies when accessing resulting array in this file.
    $incident_details_separator = explode(',', $result_api_call);

    $incident_details = array_map(
        function ($item) {
            return str_replace(':::', ',', $item);
        },
        $incident_details_separator
    );
}

// Main table.
$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filter-table-adv';
$table->head = [];
$table->data = [];
$table->size = [];
$table->size = [];
$table->colspan[0][0] = 2;
$table->colspan[4][0] = 3;
$table->colspan[6][0] = 3;
$help_macros = isset($_GET['from_event']) ? ui_print_help_icon('response_macros', true) : '';

if ($update) {
    $input_value_title = $incident_details[3];
    $input_value_type = $incident_details[17];
    $input_value_status = $incident_details[6];
    $input_value_group = $incident_details[8];
    $input_value_criticity = $incident_details[7];
    $input_value_owner = $incident_details[5];
    $input_value_content = $incident_details[4];
    $input_value_resolution = $incident_details[12];
} else if (isset($_GET['from_event'])) {
    $input_value_title = $config['cr_incident_title'];
    $input_value_type = $config['cr_incident_type'];
    $input_value_status = $config['cr_incident_status'];
    $input_value_group = $config['cr_default_group'];
    $input_value_criticity = $config['cr_default_criticity'];
    $input_value_owner = $config['cr_default_owner'];
    $input_value_content = $config['cr_incident_content'];
    $input_value_resolution = 0;
} else {
    $input_value_title = '';
    $input_value_type = '';
    $input_value_status = '';
    $input_value_group = '';
    $input_value_criticity = '';
    $input_value_owner = '';
    $input_value_content = '';
    $input_value_resolution = 0;
}

$table->data[0][0] = html_print_label_input_block(
    __('Title').$help_macros,
    html_print_input_text(
        'incident_title',
        $input_value_title,
        __('Name'),
        50,
        100,
        true,
        false,
        true,
        '',
        'w100p'
    )
);

$integria_logo = 'images/integria_logo_gray.png';
if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    $integria_logo = 'images/integria_logo.svg';
}

$table->data[0][2] = html_print_image($integria_logo, true, ['style' => 'width: 30%; float: right;'], false);

$table->data[1][0] = html_print_label_input_block(
    __('Type'),
    html_print_select(
        $integria_types_values,
        'type',
        $input_value_type,
        '',
        __('Select'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Group'),
    html_print_select(
        $integria_group_values,
        'group',
        $input_value_group,
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[1][2] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
        $integria_criticity_values,
        'criticity',
        $input_value_criticity,
        '',
        __('Select'),
        0,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[2][0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $integria_status_values,
        'status',
        $input_value_status,
        '',
        __('Select'),
        1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[2][1] = html_print_label_input_block(
    __('Creator').ui_print_help_tip(__('This field corresponds to the Integria IMS user specified in Integria IMS setup'), true),
    html_print_input_text(
        'creator',
        $config['integria_user'],
        '',
        '30',
        100,
        true,
        true,
        false,
        '',
        'w100p'
    )
);

$table->data[2][2] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_integria(
        'owner',
        $input_value_owner,
        true,
        '30',
        false,
        false,
        'w100p'
    ),
    ['div_class' => 'inline']
);

$table->data[3][0] = html_print_label_input_block(
    __('Resolution'),
    html_print_select(
        $integria_resolution_values,
        'resolution',
        $input_value_resolution,
        '',
        '',
        1,
        true,
        false,
        true,
        '',
        false,
        'width: 100%;'
    )
);

$table->data[4][0] = html_print_label_input_block(
    __('Description').$help_macros,
    html_print_textarea(
        'incident_content',
        3,
        20,
        $input_value_content,
        '',
        true
    )
);

$table->data[5][0] = html_print_label_input_block(
    __('File name'),
    html_print_input_file('userfile', true)
);

$table->data[6][0] = html_print_label_input_block(
    __('Attachment description'),
    html_print_textarea(
        'file_description',
        3,
        20,
        '',
        '',
        true
    )
);

// Print forms and stuff.
echo '<form class="max_floating_element_size" id="create_integria_incident_form" name="create_integria_incident_form" method="POST" enctype="multipart/form-data">';
html_print_table($table);
$buttons = '';
if (!$update) {
    $buttons .= html_print_input_hidden('create_incident', 1, true);
    $buttons .= html_print_submit_button(
        __('Create'),
        'accion',
        false,
        [ 'icon' => 'next' ],
        true
    );
} else {
    $buttons .= html_print_input_hidden('update_incident', 1, true);
    $buttons .= html_print_submit_button(
        __('Update'),
        'accion',
        false,
        [ 'icon' => 'upd' ],
        true
    );
}

html_print_action_buttons($buttons);

echo '</form>';
?>

<script type="text/javascript">
    $(document).ready(function () {
        $('#add_alert_table-3').hide();

        var input_value_status =
        <?php
        $status_value = ($input_value_status === '') ? 0 : $input_value_status;
            echo $status_value;
        ?>
        ;

        if (input_value_status === 7) {
            $('#add_alert_table-3').show();
        } else {
            $('#add_alert_table-3').hide();
        }

        $('#status').on('change', function() {
            if ($(this).val() === '7') {
                $('#add_alert_table-3').show();
            } else {
                $('#add_alert_table-3').hide();
            }
        });
        
    });
</script>
