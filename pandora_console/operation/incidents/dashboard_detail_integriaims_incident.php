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

if (!check_acl($config['id_user'], 0, 'IR')) {
    // Doesn't have access to this page.
    db_pandora_audit('ACL Violation', 'Trying to access IntegriaIMS ticket creation');
    include 'general/noaccess.php';
    exit;
}

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

// Get id incident.
$incident_id = (int) get_parameter('incident_id');
// API call.
$result_api_call_list = integria_api_call(
    $config['integria_hostname'],
    $config['integria_user'],
    $config['integria_pass'],
    $config['integria_api_pass'],
    'get_incident_details',
    [$incident_id]
);

// Return array of api call 'get_incidents'.
$array_get_incidents = [];
get_array_from_csv_data_all($result_api_call_list, $array_get_incidents);
// Remove index (id)
$array_get_incidents = $array_get_incidents[$incident_id];


// Header tabs.
$onheader = integriaims_tabs(false, $incident_id);
ui_print_page_header($array_get_incidents[3].' - '.__('Details'), '', false, '', false, $onheader);


// Data.
$status = $array_get_incidents[6];
$resolution = $array_get_incidents[12];
$group = $array_get_incidents[8];
$priority = $array_get_incidents[7];
$type = $array_get_incidents[17];
$description = $array_get_incidents[4];
$creator = $array_get_incidents[10];
$owner = $array_get_incidents[5];
$closed_by = $array_get_incidents[23];
$created_at = $array_get_incidents[1];
$updated_at = $array_get_incidents[9];
$closed_at = $array_get_incidents[2];

if ($closed_at == '0000-00-00 00:00:00') {
    $closed_at = __('Not yet');
}

if ($closed_by == '') {
    $closed_by = __('Not closed yet');
}


// API calls.
$status_text = integriaims_get_details('status', $status);
$group_text = integriaims_get_details('group', $group);
$priority_text = integriaims_get_details('priority', $priority);
$resolution_text = integriaims_get_details('resolution', $resolution);
$type_text = integriaims_get_details('type', $type);

// Incident file management.
$upload_file = get_parameter('upload_file');
$delete_file_id = get_parameter('delete_file');
$download_file_id = get_parameter('download_file');
$download_file_name = get_parameter('download_file_name');

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

if (check_acl($config['id_user'], 0, 'IW')) {
    $table_files->head[5] = __('Delete');
}

$table_files->data = [];

// Upload file.
if ($upload_file && ($_FILES['userfile']['name'] != '')) {
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

        $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'attach_file', [$incident_id, $filename, $filesize, $filedescription, $filecontent]);

        // API method returns '0' string if success.
        $file_added = ($result_api_call === '0') ? true : false;

        ui_print_result_message(
            $file_added,
            __('File successfully added'),
            __('File could not be added')
        );
    } else {
        ui_print_error_message(__('File has an invalid extension'));
    }
}

// Delete file.
if (isset($_GET['delete_file'])) {
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'delete_file', [$delete_file_id]);

    $file_deleted = false;

    if ($result_api_call === '0') {
        $file_deleted = true;
    }

    ui_print_result_message(
        $file_deleted,
        __('File successfully deleted'),
        __('File could not be deleted')
    );
}

// Download file.
if (isset($_GET['download_file'])) {
    $file_base64 = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'download_file', [$download_file_id]);
    ob_end_clean();

    $decoded = base64_decode($file_base64);

    file_put_contents($download_file_name, $decoded);
    ob_end_clean();

    if (file_exists($download_file_name)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($download_file_name).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($download_file_name));
        ob_end_clean();
        readfile($download_file_name);
        unlink($download_file_name);
        exit;
    }

    header('Location: index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$incident_id);
}

// Retrieve files belonging to incident and create list table.
$result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_files', [$incident_id]);

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
    $table_files->data[$i][0] = '<a id="link_delete_file" href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$incident_id.'&download_file='.$value[0]).'&download_file_name='.$value[11].'">'.$value[11].'</a>';
    $table_files->data[$i][1] = $value[14];
    $table_files->data[$i][2] = $value[12];
    $table_files->data[$i][3] = $value[8];
    $table_files->data[$i][4] = $value[13];
    if (check_acl($config['id_user'], 0, 'IW')) {
        $table_files->data[$i][5] .= '<a id="link_delete_file" href="'.ui_get_full_url('index.php?sec=incident&sec2=operation/incidents/dashboard_detail_integriaims_incident&incident_id='.$incident_id.'&delete_file='.$value[0]).'"
                                    onClick="javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;">';
        $table_files->data[$i][5] .= html_print_image('images/cross.png', true, ['title' => __('Delete')]);
    }

    $table_files->data[$i][5] .= '</a>';

    $i++;
}

$table_files_section->data[0][0] = '<div class="label_select"><p class="input_label">'.__('File name').':</p>';
$table_files_section->data[0][0] .= html_print_input_file('userfile', true);
$table_files_section->data[1][0] = '<div class="label_select"><p class="input_label">'.__('Description').':</p>';
$table_files_section->data[1][0] .= html_print_textarea(
    'file_description',
    3,
    20,
    '',
    '',
    true
);

$table_files_section->data[2][0] .= '<div style="width: 100%; text-align:right;">'.html_print_submit_button(__('Upload'), 'accion', false, 'class="sub wand"', true).'</div>';

$upload_file_form = '<div>';

if (check_acl($config['id_user'], 0, 'IW')) {
    $upload_file_form .= '<form method="post" id="file_control" enctype="multipart/form-data">'.'<h4>'.__('Add attachment').'</h4>'.html_print_table($table_files_section, true).html_print_input_hidden('upload_file', 1, true);
}

$upload_file_form .= '<h4>'.__('Attached files').'</h4>'.html_print_table($table_files, true).'</form></div>';

// Incident comments management.
$upload_comment = get_parameter('upload_comment');
$comment_description = get_parameter('comment_description');

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

$comment_disabled = ($array_get_incidents[6] == 7);

if ($comment_disabled === true) {
    $attribute = 'disabled=disabled';
}

$table_comments_section->data[0][0] = '<div class="label_select"><p class="input_label">'.__('Description').':</p>';
$table_comments_section->data[0][0] .= html_print_textarea(
    'comment_description',
    3,
    20,
    '',
    $attribute,
    true
);

$table_comments_section->data[1][1] .= '<div style="width: 100%; text-align:right;">'.html_print_submit_button(__('Add'), 'accion', $comment_disabled, 'class="sub wand"', true).'</div>';

// Upload comment. If ticket is closed, this action cannot be performed.
if ($upload_comment && $array_get_incidents[6] != 7) {
    $result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'create_workunit', [$incident_id, $comment_description, '0.00', 0, 1, '0']);

    // API method returns id of new comment if success.
    $comment_added = ($result_api_call >= '0') ? true : false;

    ui_print_result_message(
        $comment_added,
        __('Comment successfully added'),
        __('Comment could not be added')
    );
}

// Retrieve comments belonging to incident and create comments table.
$result_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_workunits', [$incident_id]);

if ($result_api_call != false && strlen($result_api_call) > 0) {
    $comments = [];
    $csv_array = explode("\n", $result_api_call);

    foreach ($csv_array as $csv_line) {
        if (!empty($csv_line)) {
            $comments[] = explode(',', $csv_line);
        }
    }
}

$comment_table = '';

if (!empty($comments)) {
    foreach ($comments as $key => $value) {
        $comment_table .= '<div class="comment_title">'.$value[3].'<span>&nbspsaid&nbsp</span>'.$value[1].'<span style="float: right;">'.$value[2].'&nbspHours</span></div>';
        $comment_table .= '<div class="comment_body">'.$value[4].'</div>';
    }
} else {
    $comment_table = __('No comments found');
}

$upload_comment_form = '<div>';

if (check_acl($config['id_user'], 0, 'IW')) {
    $upload_comment_form .= '<form method="post" id="comment_form" enctype="multipart/form-data"><h4>'.__('Add comment').'</h4>'.html_print_table($table_comments_section, true).html_print_input_hidden('upload_comment', 1, true).'</form>';
}

$upload_comment_form .= '<h4>'.__('Comments').'</h4>'.$comment_table.'</div>';

// Details box.
$details_box = '<div class="integriaims_details_box integriaims_details_box_five">';
$details_box .= '
    <div class="integriaims_details_titles">'.__('Status').'</div>
    <div class="integriaims_details_titles">'.__('Resolution').'</div>
    <div class="integriaims_details_titles">'.__('Group').'</div>
    <div class="integriaims_details_titles">'.__('Priority').'</div>
    <div class="integriaims_details_titles">'.__('Type').'</div>';
$details_box .= '
    <div>'.html_print_image('images/heart.png', true).'</div>
    <div>'.html_print_image('images/builder.png', true).'</div>
    <div>'.html_print_image('images/user_green.png', true).'</div>
    <div>'.ui_print_integria_incident_priority($priority, $priority_text).'</div>
    <div>'.html_print_image('images/incidents.png', true).'</div>';
$details_box .= '
    <div>'.$status_text.'</div>
    <div>'.$resolution_text.'</div>
    <div>'.$group_text.'</div>
    <div>'.$priority_text.'</div>
    <div>'.$type_text.'</div>';
$details_box .= '</div>';


// People box.
$people_box = '<div class="integriaims_details_box integriaims_details_box_three">';
$people_box .= '
    <div>'.html_print_image('images/header_user_green.png', true, ['width' => '21']).'</div>
    <div>'.html_print_image('images/header_user_green.png', true, ['width' => '21']).'</div>
    <div>'.html_print_image('images/header_user_green.png', true, ['width' => '21']).'</div>';
$people_box .= '
    <div class="integriaims_details_titles">'.__('Created by').':</div>
    <div class="integriaims_details_titles">'.__('Owned by').':</div>
    <div class="integriaims_details_titles">'.__('Closed by').':</div>';
$people_box .= '
    <div>'.$creator.'</div>
    <div>'.$owner.'</div>
    <div>'.$closed_by.'</div>';
$people_box .= '</div>';


// Dates box.
$dates_box = '<div class="integriaims_details_box integriaims_details_box_three">';
$dates_box .= '
    <div>'.html_print_image('images/tick.png', true).'</div>
    <div>'.html_print_image('images/update.png', true, ['width' => '21']).'</div>
    <div>'.html_print_image('images/mul.png', true).'</div>';
$dates_box .= '
    <div class="integriaims_details_titles">'.__('Created at').':</div>
    <div class="integriaims_details_titles">'.__('Updated at').':</div>
    <div class="integriaims_details_titles">'.__('Closed at').':</div>';
$dates_box .= '
    <div>'.$created_at.'</div>
    <div>'.$updated_at.'</div>
    <div>'.$closed_at.'</div>';
$dates_box .= '</div>';


// Show details, people and dates.
echo '<div class="integria_details">';
    ui_toggle($details_box, __('Details'), '', 'details_box', false, false, '', 'integria_details_content white-box-content', 'integria_details_shadow box-shadow white_table_graph');
    ui_toggle($people_box, __('People'), '', 'people_box', false, false, '', 'integria_details_content white-box-content', 'integria_details_shadow box-shadow white_table_graph');
    ui_toggle($dates_box, __('Dates'), '', 'dates_box', false, false, '', 'integria_details_content white-box-content', 'integria_details_shadow box-shadow white_table_graph');
echo '</div>';

 // Show description.
$description_box = '<div class="integria_details_description">'.html_print_textarea(
    'integria_details_description',
    3,
    0,
    $description,
    'disabled="disabled"',
    true
).'</div>';
ui_toggle($description_box, __('Description'), '', '', false);

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

echo '<div class="ui_toggle">';
ui_toggle(
    $upload_comment_form,
    __('Comments'),
    '',
    '',
    true,
    false,
    'white_box white_box_opened',
    'no-border flex'
);
echo '</div>';

?>
<script type="text/javascript">
$(document).ready (function () {

    $('#details_box .white_table_graph_header').click(function(){
        $('div#details_box').toggleClass('integria_details_shadow');
    });

    $('#people_box .white_table_graph_header').click(function(){
        $('div#people_box').toggleClass('integria_details_shadow');
    });

    $('#dates_box .white_table_graph_header').click(function(){
        $('div#dates_box').toggleClass('integria_details_shadow');
    });

});
</script>