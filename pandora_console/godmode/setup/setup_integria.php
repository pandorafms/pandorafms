<?php
/**
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_integriaims.php';

if (is_ajax()) {
    $integria_user = get_parameter('integria_user', '');
    $integria_pass = get_parameter('integria_pass', '');
    $integria_api_hostname = get_parameter('api_hostname', '');
    $integria_api_pass = get_parameter('api_pass', '');

    $login_result = integria_api_call($integria_api_hostname, $integria_user, $integria_pass, $integria_api_pass, 'get_login', []);

    if ($login_result != false) {
        echo json_encode(['login' => 1]);
    } else {
        echo json_encode(['login' => 0]);
    }

    return;
}

$has_connection = integria_api_call($config['integria_hostname'], $config['integria_user'], $config['integria_pass'], $config['integria_api_pass'], 'get_login', []);

if ($has_connection === false && $config['integria_enabled']) {
    ui_print_error_message(__('Integria IMS API is not reachable'));
}

if (get_parameter('update_config', 0) == 1) {
    // Try to retrieve event response 'Create incident in IntegriaIMS from event' to check if it exists.
    $event_response_exists = db_get_row_filter('tevent_response', ['name' => io_safe_input('Create ticket in IntegriaIMS from event')]);

    // Try to retrieve command 'Integia IMS Ticket' to check if it exists.
    $command_exists = db_get_row_filter('talert_commands', ['name' => io_safe_input('Integria IMS Ticket')]);

    if ($config['integria_enabled'] == 1) {
        if ($event_response_exists === false) {
            // Create 'Create incident in IntegriaIMS from event' event response only when user enables IntegriaIMS integration and it does not exist in database.
            db_process_sql_insert(
                'tevent_response',
                [
                    'name'           => io_safe_input('Create ticket in IntegriaIMS from event'),
                    'description'    => io_safe_input('Create a ticket in Integria IMS from an event'),
                    'target'         => io_safe_input('index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&from_event=_event_id_'),
                    'type'           => 'url',
                    'id_group'       => '0',
                    'modal_width'    => '0',
                    'modal_height'   => '0',
                    'new_window'     => '1',
                    'params'         => '',
                    'server_to_exec' => '0',
                ]
            );
        }

        if ($command_exists === false) {
            // Create 'Integria IMS Ticket' command only when user enables IntegriaIMS integration and it does not exist in database.
            $id_command_inserted = db_process_sql_insert(
                'talert_commands',
                [
                    'name'                => io_safe_input('Integria IMS Ticket'),
                    'command'             => io_safe_input('Internal type'),
                    'internal'            => 1,
                    'description'         => io_safe_input('Create a ticket in Integria IMS'),
                    'fields_descriptions' => '["'.io_safe_input('Ticket title').'","'.io_safe_input('Ticket group ID').'","'.io_safe_input('Ticket priority').'","'.io_safe_input('Ticket owner').'","'.io_safe_input('Ticket type').'","'.io_safe_input('Ticket status').'","'.io_safe_input('Ticket description').'"]',
                ]
            );

            // Create 'Create Integria IMS Ticket' action only when user enables IntegriaIMS integration and command exists in database.
            $action_values = [
                'field1'           => io_safe_input($config['incident_title']),
                'field1_recovery'  => io_safe_input($config['incident_title']),
                'field2'           => io_safe_input($config['default_group']),
                'field2_recovery'  => io_safe_input($config['default_group']),
                'field3'           => io_safe_input($config['default_criticity']),
                'field3_recovery'  => io_safe_input($config['default_criticity']),
                'field4'           => io_safe_input($config['default_owner']),
                'field4_recovery'  => io_safe_input($config['default_owner']),
                'field5'           => io_safe_input($config['incident_type']),
                'field5_recovery'  => io_safe_input($config['incident_type']),
                'field6'           => io_safe_input($config['incident_status']),
                'field6_recovery'  => io_safe_input($config['incident_status']),
                'field7'           => io_safe_input($config['incident_content']),
                'field7_recovery'  => io_safe_input($config['incident_content']),
                'id_group'         => 0,
                'action_threshold' => 0,
            ];

            alerts_create_alert_action(io_safe_input('Create Integria IMS ticket'), $id_command_inserted, $action_values);
        } else {
            // Update 'Create Integria IMS Ticket' action when setup data is updated, user enables IntegriaIMS integration and command does exist in database.
            db_process_sql_update(
                'talert_actions',
                [
                    'field1'          => io_safe_input($config['incident_title']),
                    'field1_recovery' => io_safe_input($config['incident_title']),
                    'field2'          => io_safe_input($config['default_group']),
                    'field2_recovery' => io_safe_input($config['default_group']),
                    'field3'          => io_safe_input($config['default_criticity']),
                    'field3_recovery' => io_safe_input($config['default_criticity']),
                    'field4'          => io_safe_input($config['default_owner']),
                    'field4_recovery' => io_safe_input($config['default_owner']),
                    'field5'          => io_safe_input($config['incident_type']),
                    'field5_recovery' => io_safe_input($config['incident_type']),
                    'field6'          => io_safe_input($config['incident_status']),
                    'field6_recovery' => io_safe_input($config['incident_status']),
                    'field7'          => io_safe_input($config['incident_content']),
                    'field7_recovery' => io_safe_input($config['incident_content']),
                ],
                ['name' => io_safe_input('Create Integria IMS ticket')]
            );
        }
    } else {
        if ($event_response_exists != false) {
            // Delete 'Create incident in IntegriaIMS from event' event response if it does exist and IntegriaIMS integration is disabled.
            db_process_sql_delete('tevent_response', ['name' => io_safe_input('Create ticket in IntegriaIMS from event')]);
        }

        if ($command_exists != false) {
            // Delete 'Integria IMS Ticket' command if it does exist and IntegriaIMS integration is disabled.
            db_process_sql_delete('talert_commands', ['name' => io_safe_input('Integria IMS Ticket')]);

            // Delete 'Create Integria IMS Ticket' action if command exists and IntegriaIMS integration is disabled.
            db_process_sql_delete('talert_actions', ['name' => io_safe_input('Create Integria IMS ticket')]);
        }
    }
}

// Get parameters from Integria IMS API.
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

// Enable table.
$table_enable = new StdClass();
$table_enable->data = [];
$table_enable->width = '100%';
$table_enable->id = 'integria-enable-setup';
$table_enable->class = 'databox filters';
$table_enable->size['name'] = '30%';
$table_enable->style['name'] = 'font-weight: bold';

// Enable Integria.
$row = [];
$row['name'] = __('Enable Integria IMS');
$row['control'] = html_print_checkbox_switch('integria_enabled', 1, $config['integria_enabled'], true);
$table_enable->data['integria_enabled'] = $row;

// Remote config table.
$table_remote = new StdClass();
$table_remote->data = [];
$table_remote->width = '100%';
$table_remote->styleTable = 'margin-bottom: 10px;';
$table_remote->id = 'integria-remote-setup';
$table_remote->class = 'databox filters';
$table_remote->size['name'] = '30%';
$table_remote->style['name'] = 'font-weight: bold';

// Integria user.
$row = [];
$row['name'] = __('User');
$row['control'] = html_print_input_text('integria_user', $config['integria_user'], '', 30, 100, true);
$table_remote->data['integria_user'] = $row;

// Integria password.
$row = [];
$row['name'] = __('Password');
$row['control'] = html_print_input_password('integria_pass', io_output_password($config['integria_pass']), '', 30, 100, true);
$table_remote->data['integria_pass'] = $row;

// Integria hostname.
$row = [];
$row['name'] = __('API Hostname');
$row['control'] = html_print_input_text('integria_hostname', $config['integria_hostname'], '', 30, 100, true);
$row['control'] .= ui_print_help_tip(__('Hostname of Integria IMS\' API (scheme must be specified. Example: http://192.168.0.0)'), true);
$table_remote->data['integria_hostname'] = $row;

// API password.
$row = [];
$row['name'] = __('API Password');
$row['control'] = html_print_input_password('integria_api_pass', io_output_password($config['integria_api_pass']), '', 30, 100, true);
$row['control'] .= ui_print_help_tip(__('Password of Integria IMS\' API'), true);
$table_remote->data['integria_api_pass'] = $row;

// Request timeout.
$row = [];
$row['name'] = __('Request timeout');
$row['control'] = html_print_input_text('integria_req_timeout', $config['integria_req_timeout'], '', 3, 10, true);
$row['control'] .= ui_print_help_tip(__('Time in seconds to set the maximum time of the requests to the Integria API').'. '.__('0 to disable'), true);
$table_remote->data['integria_req_timeout'] = $row;

// Alert settings.
$table_alert_settings = new StdClass();
$table_alert_settings->data = [];
$table_alert_settings->width = '100%';
$table_alert_settings->styleTable = 'margin-bottom: 10px;';
$table_alert_settings->id = 'integria-cr-settings-setup';
$table_alert_settings->class = 'databox filters';
$table_alert_settings->size['name'] = '30%';
$table_alert_settings->style['name'] = 'font-weight: bold';

// Alert incident title.
$row = [];
$row['name'] = __('Title');
$row['control'] = html_print_input_text(
    'incident_title',
    $config['incident_title'],
    __('Name'),
    50,
    100,
    true,
    false,
    false
).ui_print_help_icon('alert_macros', true);
$table_alert_settings->data['custom_response_incident_title'] = $row;

// Alert incident description.
$row = [];
$row['name'] = __('Description');
$row['control'] = html_print_input_text(
    'incident_content',
    $config['incident_content'],
    '',
    50,
    100,
    true,
    false,
    false
).ui_print_help_icon('alert_macros', true);
$table_alert_settings->data['custom_response_incident_content'] = $row;

// Alert default group.
$row = [];
$row['name'] = __('Group');
$row['control'] = html_print_select(
    $integria_group_values,
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
$table_alert_settings->data['custom_response_def_group'] = $row;

// Alert default criticity.
$row = [];
$row['name'] = __('Priority');
$row['control'] = html_print_select(
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
$table_alert_settings->data['custom_response_def_criticity'] = $row;

// Alert default owner.
$row = [];
$row['name'] = __('Owner');
$row['control'] = html_print_autocomplete_users_from_integria(
    'default_owner',
    $config['default_owner'],
    true
);

$table_alert_settings->data['custom_response_def_owner'] = $row;

// Alert default incident type.
$row = [];
$row['name'] = __('Type');
$row['control'] = html_print_select(
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
$table_alert_settings->data['custom_response_incident_type'] = $row;

// Alert default incident status.
$row = [];
$row['name'] = __('Status');
$row['control'] = html_print_select(
    $integria_status_values,
    'incident_status',
    $config['incident_status'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);
$table_alert_settings->data['custom_response_incident_status'] = $row;

// Custom response settings.
$table_cr_settings = new StdClass();
$table_cr_settings->data = [];
$table_cr_settings->width = '100%';
$table_cr_settings->styleTable = 'margin-bottom: 10px;';
$table_cr_settings->id = 'integria-cr-settings-setup';
$table_cr_settings->class = 'databox filters';
$table_cr_settings->size['name'] = '30%';
$table_cr_settings->style['name'] = 'font-weight: bold';

// Custom response incident title.
$row = [];
$row['name'] = __('Title');
$row['control'] = html_print_input_text(
    'cr_incident_title',
    $config['cr_incident_title'],
    __('Name'),
    50,
    100,
    true,
    false,
    false
).ui_print_help_icon('response_macros', true);
$table_cr_settings->data['custom_response_incident_title'] = $row;

// Custom response incident description.
$row = [];
$row['name'] = __('Description');
$row['control'] = html_print_input_text(
    'cr_incident_content',
    $config['cr_incident_content'],
    '',
    50,
    100,
    true,
    false,
    false
).ui_print_help_icon('response_macros', true);
$table_cr_settings->data['custom_response_incident_content'] = $row;

// Custom response default group.
$row = [];
$row['name'] = __('Group');
$row['control'] = html_print_select(
    $integria_group_values,
    'cr_default_group',
    $config['cr_default_group'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);
$table_cr_settings->data['custom_response_def_group'] = $row;

// Custom response default criticity.
$row = [];
$row['name'] = __('Priority');
$row['control'] = html_print_select(
    $integria_criticity_values,
    'cr_default_criticity',
    $config['cr_default_criticity'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);
$table_cr_settings->data['custom_response_def_criticity'] = $row;

// Custom response default owner.
$row = [];
$row['name'] = __('Owner');
$row['control'] = html_print_autocomplete_users_from_integria(
    'cr_default_owner',
    $config['cr_default_owner'],
    true
);

$table_cr_settings->data['custom_response_def_owner'] = $row;

// Custom response default incident type.
$row = [];
$row['name'] = __('Type');
$row['control'] = html_print_select(
    $integria_types_values,
    'cr_incident_type',
    $config['cr_incident_type'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);
$table_cr_settings->data['custom_response_incident_type'] = $row;

// Custom response default incident status.
$row = [];
$row['name'] = __('Status');
$row['control'] = html_print_select(
    $integria_status_values,
    'cr_incident_status',
    $config['cr_incident_status'],
    '',
    __('Select'),
    0,
    true,
    false,
    true,
    '',
    false
);
$table_cr_settings->data['custom_response_incident_status'] = $row;

// Test.
$row = [];
$row['name'] = __('Test');
$row['control'] = html_print_button(__('Start'), 'test-integria', false, '', 'class="sub next"', true);
$row['control'] .= '<span id="test-integria-spinner" style="display:none;">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
$row['control'] .= '<span id="test-integria-success" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
$row['control'] .= '<span id="test-integria-failure" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
$row['control'] .= '&nbsp;<span id="test-integria-message" style="display:none;"></span>';
$table_remote->data['integria_test'] = $row;

// Print.
echo '<div style="text-align: center; padding-bottom: 20px;">';
echo '<a target="_blank" rel="noopener noreferrer" href="http://integriaims.com">';
html_print_image('images/integria_logo.png');
echo '</a>';
echo '<br />';
echo '<div style="font-family: lato, "Helvetica Neue", Helvetica, Arial, sans-serif; color: #515151;">';
echo __('Integria IMS');
echo '</div>';
echo '<a target="_blank" rel="noopener noreferrer" href="https://integriaims.com">';
echo 'https://integriaims.com';
echo '</a>';
echo '</div>';

echo "<form method='post'>";
html_print_input_hidden('update_config', 1);

// Form enable.
echo '<div id="form_enable">';
html_print_table($table_enable);
echo '</div>';

// Form remote.
echo '<div id="form_remote">';
echo '<fieldset>';
echo '<legend>'.__('Integria API settings').'</legend>';

html_print_table($table_remote);

echo '</fieldset>';
echo '</div>';

if ($has_connection != false) {
    // Form alert default settings.
    echo '<div id="form_alert_settings">';
    echo '<fieldset>';
    echo '<legend>'.__('Alert default values').'</legend>';

    html_print_table($table_alert_settings);

    echo '</fieldset>';
    echo '</div>';

    // Form custom response default settings.
    echo '<div id="form_custom_response_settings">';
    echo '<fieldset>';
    echo '<legend>'.__('Event custom response default values').'</legend>';

    html_print_table($table_cr_settings);

    echo '</fieldset>';
    echo '</div>';

    echo '<div class="action-buttons" style="width: '.$table_remote->width.'">';
    html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
    echo '</div>';
} else {
    echo '<div class="action-buttons" style="width: '.$table_remote->width.'">';
    html_print_submit_button(__('Update and continue'), 'update_button', false, 'class="sub next"');
    echo '</div>';
}


echo '</form>';

?>

<script type="text/javascript">

    if(!$('input:checkbox[name="integria_enabled"]').is(':checked')) {
        $('#form_remote').hide();
        $('#form_custom_response_settings').hide();
    } else {
        $('#form_remote').show();
        $('#form_custom_response_settings').show();
    }

    $('#form_enable').css('margin-bottom','20px');
    var showFields = function () {
        $('#form_remote').show();
        $('#form_custom_response_settings').show();
    }
    var hideFields = function () {
        $('#form_remote').hide();
        $('#form_custom_response_settings').hide();
    }

    var hideUserPass = function () {
        $('#integria-remote-setup-integria_user').hide();
        $('#integria-remote-setup-integria_pass').hide();
    }

    var showUserPass = function () {
        $('#integria-remote-setup-integria_user').show();
        $('#integria-remote-setup-integria_pass').show();
    }

    var handleEnable = function (event) {
        var is_checked = $('input:checkbox[name="integria_enabled"]').is(':checked');

        if (event.target.value == '1' && is_checked) {
            showFields();
            $('input:checkbox[name="integria_enabled"]').attr('checked', true);
        }
        else {
            hideFields();
            $('input:checkbox[name="integria_enabled"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="integria_enabled"]').change(handleEnable);

    var handleTest = function (event) {
        var user = $('input#text-integria_user').val();
        var pass = $('input#password-integria_pass').val();
        var host = $('input#text-integria_hostname').val();
        var timeout = Number.parseInt($('input#text-integria_req_timeout').val(), 10);
    
        var timeoutMessage = '<?php echo __('Connection timeout'); ?>';
        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
        
        var hideLoadingImage = function () {
            $('span#test-integria-spinner').hide();
        }
        var showLoadingImage = function () {
            $('span#test-integria-spinner').show();
        }
        var hideSuccessImage = function () {
            $('span#test-integria-success').hide();
        }
        var showSuccessImage = function () {
            $('span#test-integria-success').show();
        }
        var hideFailureImage = function () {
            $('span#test-integria-failure').hide();
        }
        var showFailureImage = function () {
            $('span#test-integria-failure').show();
        }
        var hideMessage = function () {
            $('span#test-integria-message').hide();
        }
        var showMessage = function () {
            $('span#test-integria-message').show();
        }
        var changeTestMessage = function (message) {
            $('span#test-integria-message').text(message);
        }
        
        hideSuccessImage();
        hideFailureImage();
        hideMessage();
        showLoadingImage();

        var integria_user = $('input[name=integria_user]').val();
        var integria_pass = $('input[name=integria_pass]').val();
        var api_hostname = $('input[name=integria_hostname]').val();
        var api_pass = $('input[name=integria_api_pass]').val();

        var data = {
            page: "godmode/setup/setup_integria",
            check_api_access: 1,
            integria_user: integria_user,
            integria_pass: integria_pass,
            api_hostname: api_hostname,
            api_pass: api_pass,
        }

        // AJAX call to check API connection.
        $.ajax({
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            timeout: timeout ? timeout * 1000 : 0,
            data: data
        })
        .done(function(data, textStatus, xhr) {
            if (data.login == '1') {
                showSuccessImage();
            } else {
                showFailureImage();
                showMessage();
            }

        })
        .fail(function(xhr, textStatus, errorThrown) {
            showFailureImage();
            showMessage();
        })
        .always(function(xhr, textStatus) {
            hideLoadingImage();
        });
    }
    $('input#button-test-integria').click(handleTest);
    


</script>
