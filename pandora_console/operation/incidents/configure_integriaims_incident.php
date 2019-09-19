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

// If everything OK, get parameters from Integria IMS API in order to populate combos.
$integria_group_values = [];
$integria_criticity_values = [];
$integria_users_values = [];
$integria_types_values = [];
$integria_status_values = [];

$integria_groups_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_groups', []);

get_array_from_csv_data_pair($integria_groups_csv, $integria_group_values);

$integria_status_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incidents_status', []);

get_array_from_csv_data_pair($integria_status_csv, $integria_status_values);

$integria_criticity_levels_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_priorities', []);

get_array_from_csv_data_pair($integria_criticity_levels_csv, $integria_criticity_values);

$integria_users_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_users', []);

$csv_array = explode("\n", $integria_users_csv);

foreach ($csv_array as $csv_line) {
    if (!empty($csv_line)) {
        $integria_users_values[$csv_line] = $csv_line;
    }
}

$integria_types_csv = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_types', []);

get_array_from_csv_data_pair($integria_types_csv, $integria_types_values);

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
$incident_title = events_get_field_value_by_event_id($event_id, get_parameter('incident_title'));
$incident_content = events_get_field_value_by_event_id($event_id, get_parameter('incident_content'));

$update = (isset($_GET['incident_id']) === true);

// If incident id is specified, retrieve incident values from api to populate combos with such values.
if ($update) {
    // Call Integria IMS API method to get details of an incident given its id.
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_details', [$incident_id_edit]);

    // API call does not return indexes, therefore future modifications of API function in Integria IMS may lead to inconsistencies when accessing resulting array in this file.
    $incident_details = explode(',', $result_api_call);
}

// Perform action.
if ($create_incident === true) {
    // Call Integria IMS API method to create an incident.
    $result_api_call = integria_api_call($config['integria_hostname'], $incident_creator, $config['integria_pass'], $config['integria_api_pass'], 'create_incident', [$incident_title, $incident_group_id, $incident_criticity_id, $incident_content, '', '0', '', $incident_owner, '0', $incident_status]);

    // Necessary to explicitly set true if not false because function returns api call result in case of success instead of true value.
    $incident_created_ok = ($result_api_call != false) ? true : false;

    ui_print_result_message(
        $incident_created_ok,
        __('Successfully created in Integria IMS'),
        __('Could not be created in Integria IMS')
    );
} else if ($update_incident === true) {
    // Call Integria IMS API method to update an incident.
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'update_incident', [$incident_id_edit, $incident_title, $incident_content, '', $incident_group_id, $incident_criticity_id, 0, $incident_status, $incident_owner]);

    // Necessary to explicitly set true if not false because function returns api call result in case of success instead of true value.
    $incident_updated_ok = ($result_api_call != false) ? true : false;

    ui_print_result_message(
        $incident_updated_ok,
        __('Successfully updated in Integria IMS'),
        __('Could not be updated in Integria IMS')
    );
}

// Main table.
$table = new stdClass();
$table->width = '100%';
$table->id = 'add_alert_table';
$table->class = 'databox filters integria_incidents_options';
$table->head = [];

$table->data = [];
$table->size = [];
$table->size = [];
$table->style[0] = 'width: 33%; padding-right: 50px; padding-left: 100px;';
$table->style[1] = 'width: 33%; padding-right: 50px; padding-left: 50px;';
$table->style[2] = 'width: 33%; padding-right: 100px; padding-left: 50px;';
$table->colspan[0][0] = 2;
$table->colspan[3][0] = 3;

$table->data[0][0] = '<div class="label_select"><p class="input_label">'.__('Title').':&nbsp'.ui_print_help_icon('response_macros', true).'</p>';
$table->data[0][0] .= '<div class="label_select_parent">'.html_print_input_text(
    'incident_title',
    $update ? $incident_details[3] : $config['incident_title'],
    __('Name'),
    50,
    100,
    true,
    false,
    true
).'</div>';

$table->data[1][0] = '<div class="label_select"><p class="input_label">'.__('Type').': </p>';
$table->data[1][0] .= '<div class="label_select_parent">'.html_print_select(
    $integria_types_values,
    'type',
    $update ? $incident_details[17] : $config['incident_type'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
).'</div>';

$table->data[2][0] = '<div class="label_select"><p class="input_label">'.__('Status').': </p>';
$table->data[2][0] .= '<div class="label_select_parent">'.html_print_select(
    $integria_status_values,
    'status',
    $update ? $incident_details[6] : $config['incident_status'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
).'</div>';

$table->data[1][1] = '<div class="label_select"><p class="input_label">'.__('Group').': </p>';
$table->data[1][1] .= '<div class="label_select_parent">'.html_print_select(
    $integria_group_values,
    'group',
    $update ? $incident_details[8] : $config['default_group'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
).'</div>';

$table->data[2][1] = '<div class="label_select"><p class="input_label">'.__('Creator').': </p>';
$table->data[2][1] .= '<div class="label_select_parent">'.html_print_autocomplete_users_from_integria(
    'creator',
    $update ? $incident_details[10] : $config['default_creator'],
    true
).'</div>';

$table->data[1][2] = '<div class="label_select"><p class="input_label">'.__('Criticity').': </p>';
$table->data[1][2] .= '<div class="label_select_parent">'.html_print_select(
    $integria_criticity_values,
    'criticity',
    $update ? $incident_details[7] : $config['default_criticity'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false,
    'width: 100%;'
).'</div>';

$table->data[2][2] = '<div class="label_select"><p class="input_label">'.__('Owner').': </p>';

$table->data[2][2] .= '<div class="label_select_parent">'.html_print_autocomplete_users_from_integria(
    'owner',
    $update ? $incident_details[10] : $config['default_owner'],
    true
).'</div>';

$table->data[3][0] = '<div class="label_select"><p class="input_label">'.__('Description').':&nbsp'.ui_print_help_icon('response_macros', true).'</p>';
$table->data[3][0] .= '<div class="label_select_parent">'.html_print_textarea(
    'incident_content',
    3,
    20,
    $update ? $incident_details[4] : $config['incident_content'],
    '',
    true
).'</div>';

// Here starts incident file management.
$upload_file = get_parameter('upload_file');
$delete_file_id = get_parameter('delete_file');

// Files section table.
$table_files_section = new stdClass();
$table_files_section->width = '100%';
$table_files_section->id = 'files_section_table';
$table_files_section->class = 'databox filters';
$table_files_section->head = [];

$table_files_section->data = [];
$table_files_section->size = [];
$table_files_section->colspan[2][0] = 3;

// Files list table.
$table_files = new stdClass();
$table_files->width = '100%';
$table_files->class = 'info_table';
$table_files->head = [];

$table_files->head[0] = __('Filename');
$table_files->head[1] = __('Timestamp');
$table_files->head[2] = __('Description');
$table_files->head[3] = __('User');
$table_files->head[4] = __('Size');
$table_files->head[5] = __('Delete');

$table_files->data = [];

// Upload file.
if (check_acl($config['id_user'], 0, 'IW') && $upload_file && ($_FILES['userfile']['name'] != '')) {
    $filedescription = get_parameter('file_description', __('No description available'));

    $filename = io_safe_input($_FILES['userfile']['name']);
    $filesize = io_safe_input($_FILES['userfile']['size']);

    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $invalid_extensions = '/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|iso|
        pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
        htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i';

    if (!preg_match($invalid_extensions, $extension)) {
        // The following is if you have clamavlib installed.
        // (php5-clamavlib) and enabled in php.ini
        // http://www.howtoforge.com/scan_viruses_with_php_clamavlib
        if (extension_loaded('clamav')) {
            cl_setlimits(5, 1000, 200, 0, 10485760);
            $malware = cl_scanfile($_FILES['file']['tmp_name']);
            if ($malware) {
                $error = 'Malware detected: '.$malware.'<br>ClamAV version: '.clam_get_version();
                die($error);
                // On malware, we die because it's not good to handle it
            }
        }

        $filecontent = base64_encode(file_get_contents($_FILES['userfile']['tmp_name']));

        $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'attach_file', [$incident_id_edit, $filename, $filesize, $filedescription, $filecontent]);

        // API method returns '0' string if success.
        $file_added = ($result_api_call === '0') ? true : false;

        ui_print_result_message(
            $file_added,
            __('File successfully added'),
            __('File could not be added')
        );
    }
}

// Delete file.
if (isset($_GET['delete_file']) && check_acl($config['id_user'], 0, 'IW')) {
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'delete_file', [$delete_file_id]);
    header('Location: index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&incident_id='.$incident_id_edit);
}

// Retrieve files belonging to incident and create list table.
$result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_files', [$incident_id_edit]);

if ($result_api_call != false && strlen($result_api_call) > 0) {
    $files = [];
    $csv_array = explode("\n", $result_api_call);

    foreach ($csv_array as $csv_line) {
        if (!empty($csv_line)) {
            $files[] = explode(',', $csv_line);
        }
    }
}

$i = 0;

foreach ($files as $key => $value) {
    $table_files->data[$i][0] = $value[11];
    $table_files->data[$i][1] = $value[14];
    $table_files->data[$i][2] = $value[12];
    $table_files->data[$i][3] = $value[8];
    $table_files->data[$i][4] = $value[13];
    if (check_acl($config['id_user'], 0, 'IW')) {
        $table_files->data[$i][5] .= '<a id="link_delete_file" href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&incident_id='.$incident_id_edit.'&delete_file='.$value[0]).'"        
        onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">';
        $table_files->data[$i][5] .= html_print_image('images/cross.png', true, ['title' => __('Delete')]);
        $table_files->data[$i][5] .= '</a>';
    }

    $i++;
}

   // header("Content-type: text/plain");
   // header("Content-Disposition: attachment; filename=savethis.txt");
   // do your Db stuff here to get the content into $content
   // echo "This is some text...\n";
   // print $content;
$table_files_section->data[0][0] = '<div class="label_select"><p class="input_label">'.__('File name').':</p>';
$table_files_section->data[0][0] .= html_print_input_file('userfile', true);
$table_files_section->data[1][0] = '<div class="label_select"><p class="input_label">'.__('Description').':</p>';
$table_files_section->data[1][0] .= html_print_input_text(
    'file_description',
    '',
    __('Description'),
    50,
    100,
    true,
    false
);

$table_files_section->data[2][0] .= '<div style="width: 100%; text-align:right;">'.html_print_submit_button(__('Upload'), 'accion', false, 'class="sub wand"', true).'</div>';

$upload_file_form = '<div><form method="post" id="file_control" enctype="multipart/form-data"><h4>'.__('Add attachment').'</h4>'.html_print_table($table_files_section, true).html_print_input_hidden('upload_file', 1, true).'<h4>'.__('Attached files').'</h4>'.html_print_table($table_files, true).'</form></div>';

// Here starts incident comments management.
// Comments section table.
$table_comments_section = new stdClass();
$table_comments_section->width = '100%';
$table_comments_section->id = 'files_section_table';
$table_comments_section->class = 'databox filters';
$table_comments_section->head = [];

$table_comments_section->data = [];
$table_comments_section->size = [];

// Comments list table.
$table_comments = new stdClass();
$table_comments->width = '100%';
$table_comments->class = 'info_table';
$table_comments->head = [];

$table_comments->head[0] = __('Filename');
$table_comments->head[1] = __('Timestamp');
$table_comments->head[2] = __('Description');
$table_comments->head[3] = __('User');
$table_comments->head[4] = __('Size');
$table_comments->head[5] = __('Delete');

$table_comments->data = [];

$table_comments_section->data[0][0] = '<div class="label_select"><p class="input_label">'.__('Description').':</p>';
$table_comments_section->data[0][0] .= html_print_input_text(
    'file_description',
    '',
    __('Description'),
    50,
    100,
    true,
    false
);

$i = 0;

// Retrieve comments belonging to incident and create comments table.
$result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_workunits', [$incident_id_edit]);

if ($result_api_call != false && strlen($result_api_call) > 0) {
    $comments = [];
    $csv_array = explode("\n", $result_api_call);

    foreach ($csv_array as $csv_line) {
        if (!empty($csv_line)) {
            $comments[] = explode(',', $csv_line);
        }
    }
}

foreach ($comments as $key => $value) {
    $table_comments->data[$i][0] = $value[11];
    $table_comments->data[$i][1] = $value[14];
    $table_comments->data[$i][2] = $value[12];
    $table_comments->data[$i][3] = $value[8];
    $table_comments->data[$i][4] = $value[13];

    $i++;
}

/*
    $upload_file_form = '<div><form method="post" id="file_control" enctype="multipart/form-data"><h4>'.__('Add comment').'</h4>'
    .html_print_table($table_comments_section, true)
    .html_print_input_hidden('upload_file', 1, true)
    .'</form>'
    .'<h4>'.__('Comments').'</h4>'
    .html_print_table($table_comments, true)
    .'</div>';*/
//
// Print forms and stuff.
echo '<form id="create_integria_incident_form" name="create_integria_incident_form" method="POST">';
html_print_table($table);

if (!$update) {
    html_print_input_hidden('create_incident', 1);
} else {
    html_print_input_hidden('update_incident', 1);
}

echo '</form>';
echo '<div class="ui_toggle">';
ui_toggle(
    $upload_file_form,
    __('Attached files'),
    '',
    '',
    true,
    false,
    'white_box white_box_opened',
    'no-border flex'
);
echo '</div>';

echo '<div style="width: 100%; text-align:right;">';
html_print_submit_button(__('Create'), 'accion', false, 'form="create_integria_incident_form" class="sub wand"');
echo '</div>';
