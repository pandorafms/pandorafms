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

ui_print_page_header($array_get_incidents[3].__(' - Details'), '', false, '', false, '');

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
// Get status.
$status_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incidents_status');
$status_incident = [];
get_array_from_csv_data_pair($status_api_call, $status_incident);

if ($status_incident[$status] == '') {
    $status_text = __('None');
} else {
    $status_text = $status_incident[$status];
}

// Get group.
$group_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_groups');
$group_incident = [];
get_array_from_csv_data_pair($group_api_call, $group_incident);
$group_text = $group_incident[$group];

// Get priority.
$priority_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incident_priorities');
$priority_incident = [];
get_array_from_csv_data_pair($priority_api_call, $priority_incident);
$priority_text = $priority_incident[$priority];

// Get resolution.
$resolution_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_incidents_resolutions');
$resolution_incident = [];
get_array_from_csv_data_pair($resolution_api_call, $resolution_incident);

if ($resolution_incident[$resolution] == '') {
    $resolution_text = __('None');
} else {
    $resolution_text = $resolution_incident[$resolution];
}

// Get types.
$type_api_call = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_types');
$type_incident = [];
get_array_from_csv_data_pair($type_api_call, $type_incident);
if ($type_incident[$type] == '') {
    $type_text = __('None');
} else {
    $type_text = $type_incident[$type];
}


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
    <div>'.ui_print_integria_incident_priority($priority, $priority_incident[$priority]).'</div>
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
$description_box = '<div class="integria_details_description">'.$description.'</div>';
ui_toggle($description_box, __('Description'), '', '', false);

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