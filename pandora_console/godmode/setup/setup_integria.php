<?php
/**
 * Integria setup.
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

 // Load globals.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_integriaims.php';

if (is_ajax() === true) {
    $operation = (string) get_parameter('operation', '');

    $integria_user = get_parameter('integria_user', '');
    $integria_pass = get_parameter('integria_pass', '');
    $integria_api_hostname = get_parameter('api_hostname', '');
    $integria_api_pass = get_parameter('api_pass', '');
    $user_level_conf = get_parameter('user_level_conf', 0);
    $user_level_conf_bool = ((bool) $user_level_conf === true);

    $login_result = integria_api_call($integria_api_hostname, $integria_user, $integria_pass, $integria_api_pass, 'get_login', [], false, '', '', $user_level_conf_bool);

    echo json_encode(['login' => ($login_result !== false) ? 1 : 0]);

    return;
}

$has_connection = integria_api_call(null, null, null, null, 'get_login', []);

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

        $ticket_types = integria_api_call(null, null, null, null, 'get_types', '', false, 'json');

        $types_string = '';

        if ($ticket_types !== '') {
            foreach (json_decode($ticket_types, true) as $key => $value) {
                $types_string .= $value['id'].','.$value['name'].';';
            }
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
                    'fields_descriptions' => '["'.io_safe_input('Ticket title').'","'.io_safe_input('Ticket group ID').'","'.io_safe_input('Ticket priority').'","'.io_safe_input('Ticket owner').'","'.io_safe_input('Ticket type').'","'.io_safe_input('Ticket status').'","'.io_safe_input('Ticket description').'","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_"]',
                    'fields_values'       => '["", "", "","","'.$types_string.'","","","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_"]',
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
            // Update 'Integria IMS Ticket' command with ticket types retrieved from Integria IMS.
            $sql_update_command_values = sprintf(
                '
                UPDATE talert_commands
                SET fields_values = \'["","","","","%s","","","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_","_integria_type_custom_field_"]\'
                WHERE name="%s"',
                $types_string,
                io_safe_input('Integria IMS Ticket')
            );

            db_process_sql($sql_update_command_values);

            // Update those actions that make use of 'Integria IMS Ticket' command when setup default fields are updated. Empty fields in actions will be filled in with default values.
            $update_action_values = [
                $config['incident_title'],
                $config['default_group'],
                $config['default_criticity'],
                $config['default_owner'],
                $config['incident_type'],
                $config['incident_status'],
                $config['incident_content'],
            ];

            foreach ($update_action_values as $key => $value) {
                $field_key = ($key + 1);

                $sql_update_action_field = sprintf(
                    '
                    UPDATE talert_actions taa
                    INNER JOIN talert_commands tac
                    ON taa.id_alert_command=tac.id
                    SET field%s= "%s"
                    WHERE tac.name="Integria&#x20;IMS&#x20;Ticket"
                    AND (
                        taa.field%s IS NULL OR taa.field%s=""
                    )',
                    $field_key,
                    $value,
                    $field_key,
                    $field_key,
                    $field_key
                );

                db_process_sql($sql_update_action_field);
            }

            foreach ($update_action_values as $key => $value) {
                $field_key = ($key + 1);

                $sql_update_action_recovery_field = sprintf(
                    '
                    UPDATE talert_actions taa
                    INNER JOIN talert_commands tac
                    ON taa.id_alert_command=tac.id
                    SET field%s_recovery = "%s"
                    WHERE tac.name="Integria&#x20;IMS&#x20;Ticket"
                    AND (
                        taa.field%s_recovery IS NULL OR taa.field%s_recovery=""
                    )',
                    $field_key,
                    $value,
                    $field_key,
                    $field_key,
                    $field_key
                );

                db_process_sql($sql_update_action_recovery_field);
            }
        }
    } else {
        if ($event_response_exists !== false) {
            // Delete 'Create incident in IntegriaIMS from event' event response if it does exist and IntegriaIMS integration is disabled.
            db_process_sql_delete('tevent_response', ['name' => io_safe_input('Create ticket in IntegriaIMS from event')]);
        }
    }
}

// Get parameters from Integria IMS API.
$integria_group_values = [];
$integria_criticity_values = [];
$integria_users_values = [];
$integria_types_values = [];
$integria_status_values = [];

$integria_groups_csv = integria_api_call(null, null, null, null, 'get_groups', []);

get_array_from_csv_data_pair($integria_groups_csv, $integria_group_values);

$integria_status_csv = integria_api_call(null, null, null, null, 'get_incidents_status', []);

get_array_from_csv_data_pair($integria_status_csv, $integria_status_values);

$integria_criticity_levels_csv = integria_api_call(null, null, null, null, 'get_incident_priorities', []);

get_array_from_csv_data_pair($integria_criticity_levels_csv, $integria_criticity_values);

$integria_users_csv = integria_api_call(null, null, null, null, 'get_users', []);

$csv_array = explode("\n", $integria_users_csv);

foreach ($csv_array as $csv_line) {
    if (empty($csv_line) === false) {
        $integria_users_values[$csv_line] = $csv_line;
    }
}

$integria_types_csv = integria_api_call(null, null, null, null, 'get_types', []);

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
$table_remote->class = 'databox filters filter-table-adv';
$table_remote->size['hostname'] = '50%';
$table_remote->size['api_pass'] = '50%';

// Enable Integria user configuration.
$row = [];
$row['user_level'] = html_print_label_input_block(
    __('Integria configuration at user level'),
    html_print_checkbox_switch(
        'integria_user_level_conf',
        1,
        $config['integria_user_level_conf'],
        true
    )
);
$table_remote->data['integria_user_level_conf'] = $row;

// Integria user.
$row = [];
$row['user'] = html_print_label_input_block(
    __('User'),
    html_print_input_text(
        'integria_user',
        $config['integria_user'],
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'integria-remote-setup-integria_user']
);

// Integria password.
$row['password'] = html_print_label_input_block(
    __('Password'),
    html_print_input_password(
        'integria_pass',
        io_output_password($config['integria_pass']),
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'integria-remote-setup-integria_pass']
);
$table_remote->data['integria_pass'] = $row;

// Integria hostname.
$row = [];
$row['hostname'] = html_print_label_input_block(
    __('URL to Integria IMS setup').ui_print_help_tip(__('Full URL to your Integria IMS setup (e.g., http://192.168.1.20/integria, https://support.mycompany.com).'), true),
    html_print_input_text(
        'integria_hostname',
        $config['integria_hostname'],
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'integria-remote-setup-integria_hostname']
);

// API password.
$row['api_pass'] = html_print_label_input_block(
    __('API Password'),
    html_print_input_password(
        'integria_api_pass',
        io_output_password($config['integria_api_pass']),
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'integria-remote-setup-integria_api_pass']
);
$table_remote->data['integria_api_pass'] = $row;

// Request timeout.
$row = [];
$row['req_timeout'] = html_print_label_input_block(
    __('Request timeout'),
    html_print_input_text(
        'integria_req_timeout',
        $config['integria_req_timeout'],
        '',
        3,
        10,
        true
    ),
    ['div_class' => 'integria-remote-setup-integria_req_timeout']
);
$table_remote->data['integria_req_timeout'] = $row;

$row = [];
$row['control'] = __('Inventory');
$row['control'] .= html_print_button(
    __('Sync inventory'),
    'sync-inventory',
    false,
    '',
    [
        'icon' => 'cog',
        'mode' => 'secondary mini',
    ],
    true
);
$row['control'] .= '<span id="test-integria-spinner-sync" style="display:none;">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
$row['control'] .= '<span id="test-integria-success-sync" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
$row['control'] .= '<span id="test-integria-failure-sync" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
$table_remote->data['integria_sync_inventory'] = $row;

// Alert settings.
$table_alert_settings = new StdClass();
$table_alert_settings->data = [];
$table_alert_settings->width = '100%';
$table_alert_settings->styleTable = 'margin-bottom: 10px;';
$table_alert_settings->id = 'integria-cr-settings-setup';
$table_alert_settings->class = 'databox filters filter-table-adv';
$table_alert_settings->size[0] = '50%';
$table_alert_settings->size[1] = '50%';

// Alert incident title.
$row = [];
$row[0] = html_print_label_input_block(
    __('Title'),
    html_print_input_text(
        'incident_title',
        $config['incident_title'],
        __('Name'),
        50,
        100,
        true,
        false,
        false
    )
);

// Alert incident description.
$row[1] = html_print_label_input_block(
    __('Ticket body'),
    html_print_textarea(
        'incident_content',
        3,
        25,
        $config['incident_content'],
        '',
        true
    )
);
$table_alert_settings->data[0] = $row;

// Alert default group.
$row = [];
$row[0] = html_print_label_input_block(
    __('Group'),
    html_print_select(
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
    )
);

// Alert default criticity.
$row[1] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
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
    )
);
$table_alert_settings->data[1] = $row;

// Alert default owner.
$row = [];
$row[0] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_integria(
        'default_owner',
        $config['default_owner'],
        true,
        '30',
        false,
        false,
        'w100p'
    ),
    ['div_class' => 'inline']
);

// Alert default incident type.
$row[1] = html_print_label_input_block(
    __('Type'),
    html_print_select(
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
    )
);
$table_alert_settings->data[2] = $row;

// Alert default incident status.
$row = [];
$row[0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
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
    )
);
$table_alert_settings->data[3] = $row;

// Custom response settings.
$table_cr_settings = new StdClass();
$table_cr_settings->data = [];
$table_cr_settings->width = '100%';
$table_cr_settings->styleTable = 'margin-bottom: 10px;';
$table_cr_settings->id = 'integria-cr-settings-setup';
$table_cr_settings->class = 'databox filters filter-table-adv';
$table_cr_settings->size[0] = '50%';
$table_cr_settings->size[1] = '50%';

// Custom response incident title.
$row = [];
$row[0] = html_print_label_input_block(
    __('Title'),
    html_print_input_text(
        'cr_incident_title',
        $config['cr_incident_title'],
        __('Name'),
        50,
        100,
        true,
        false,
        false
    )
);

// Custom response incident description.
$row[1] = html_print_label_input_block(
    __('Ticket body'),
    html_print_textarea(
        'cr_incident_content',
        3,
        25,
        $config['cr_incident_content'],
        '',
        true
    )
);

$table_cr_settings->data[0] = $row;

// Custom response default group.
$row = [];
$row[0] = html_print_label_input_block(
    __('Group'),
    html_print_select(
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
    )
);

// Custom response default criticity.
$row[1] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
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
    )
);
$table_cr_settings->data[1] = $row;

// Custom response default owner.
$row = [];
$row[0] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_integria(
        'cr_default_owner',
        $config['cr_default_owner'],
        true,
        '30',
        false,
        false,
        'w100p'
    ),
    ['div_class' => 'inline']
);

// Custom response default incident type.
$row[1] = html_print_label_input_block(
    __('Type'),
    html_print_select(
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
    )
);
$table_cr_settings->data[2] = $row;

// Custom response default incident status.
$row = [];
$row[0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
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
    )
);
$table_cr_settings->data[3] = $row;

// Test.
$row = [];
$row['control'] = __('Test connection');
$row['control'] .= html_print_button(
    __('Test'),
    'test-integria',
    false,
    '',
    [
        'icon' => 'cog',
        'mode' => 'secondary mini',
    ],
    true
);
$row['control'] .= '<span id="test-integria-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
$row['control'] .= '<span id="test-integria-success" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'&nbsp;'.__('Connection its OK').'</span>';
$row['control'] .= '<span id="test-integria-failure" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'&nbsp;'.__('Connection failed').'</span>';
$row['control'] .= '&nbsp;<span id="test-integria-message" class="invisible"></span>';
$table_remote->data['integria_test'] = $row;

// Print.
echo '<div class="center pdd_b_10px mrgn_btn_20px white_box max_floating_element_size">';
echo '<a target="_blank" rel="noopener noreferrer" href="http://integriaims.com">';
html_print_image(
    'images/integria_logo.svg',
    false,
    ['class' => 'w400px mrgn_top_15px']
);
echo '</a>';
echo '<br />';
echo '<div clsas="integria_title">';
echo __('Integria IMS');
echo '</div>';
echo '<a target="_blank" rel="noopener noreferrer" href="https://integriaims.com">';
echo 'https://integriaims.com';
echo '</a>';
echo '</div>';

echo "<form method='post' class='max_floating_element_size'>";
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
    echo '<fieldset class="mrgn_top_15px">';
    echo '<legend>'.__('Alert default values').'&nbsp'.ui_print_help_icon('alert_macros', true).'</legend>';

    html_print_table($table_alert_settings);

    echo '</fieldset>';
    echo '</div>';

    // Form custom response default settings.
    echo '<div id="form_custom_response_settings">';
    echo '<fieldset class="mrgn_top_15px">';
    echo '<legend>'.__('Event custom response default values').'&nbsp'.ui_print_help_icon('alert_macros', true).'</legend>';

    html_print_table($table_cr_settings);

    echo '</fieldset>';
    echo '</div>';

    $update_button = html_print_submit_button(
        __('Update'),
        'update_button',
        false,
        ['icon' => 'update'],
        true
    );
} else {
    $update_button = html_print_submit_button(
        __('Update and continue'),
        'update_button',
        false,
        ['icon' => 'update'],
        true
    );
}

html_print_action_buttons($update_button);

echo '</form>';

?>

<script type="text/javascript">

    if($('input:checkbox[name="integria_user_level_conf"]').is(':checked'))
    {
        $('.integria-remote-setup-integria_user').hide();
        $('.integria-remote-setup-integria_pass').hide()
    }

    var handleUserLevel = function(event) {
        var is_checked = $('input:checkbox[name="integria_enabled"]').is(':checked');
        var is_checked_userlevel = $('input:checkbox[name="integria_user_level_conf"]').is(':checked');

        if (event.target.value == '1' && is_checked && !is_checked_userlevel) {
            showUserPass();
            $('input:checkbox[name="integria_user_level_conf"]').attr('checked', true);
        }
        else {
            hideUserPass();
            $('input:checkbox[name="integria_user_level_conf"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="integria_enabled"]').change(handleEnable);
    $('input:checkbox[name="integria_user_level_conf"]').change(handleUserLevel);

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
        $('.integria-remote-setup-integria_user').hide();
        $('.integria-remote-setup-integria_pass').hide();
    }

    var showUserPass = function () {
        $('.integria-remote-setup-integria_user').show();
        $('.integria-remote-setup-integria_pass').show();
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
        var user_level_conf = $('input:checkbox[name="integria_user_level_conf"]').is(':checked');

        var data = {
            page: 'godmode/setup/setup_integria',
            operation: 'check_api_access',
            integria_user: integria_user,
            integria_pass: integria_pass,
            api_hostname: api_hostname,
            api_pass: api_pass,
            user_level_conf: user_level_conf,
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

    var handleInventorySync = function (event) {

        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';

        var hideLoadingImage = function () {
            $('span#test-integria-spinner-sync').hide();
        }
        var showLoadingImage = function () {
            $('span#test-integria-spinner-sync').show();
        }
        var hideSuccessImage = function () {
            $('span#test-integria-success-sync').hide();
        }
        var showSuccessImage = function () {
            $('span#test-integria-success-sync').show();
        }
        var hideFailureImage = function () {
            $('span#test-integria-failure-sync').hide();
        }
        var showFailureImage = function () {
            $('span#test-integria-failure-sync').show();
        }

        hideSuccessImage();
        hideFailureImage();
        showLoadingImage();

        var integria_user = $('input[name=integria_user]').val();
        var integria_pass = $('input[name=integria_pass]').val();
        var api_hostname = $('input[name=integria_hostname]').val();
        var api_pass = $('input[name=integria_api_pass]').val();

        if (!api_hostname.match(/^[a-zA-Z]+:\/\//))
        {
            api_hostname = 'http://' + api_hostname;
        }

        var url = api_hostname + '/integria/include/api.php';

        <?php
        // Retrieve all agents and codify string in the format that will be sent over in Ajax call.
            $agent_fields = [
                'nombre',
                'alias',
                'id_os',
                'direccion',
                'id_agente',
                'id_grupo',
            ];

            $agents = agents_get_agents(false, $agent_fields);

            $agents_query_string_array = [];

            foreach ($agents as $agent_data) {
                $agents_query_string_array[] = implode('|;|', $agent_data);
            }
            ?>

        var agents_query_string_array = <?php echo json_encode($agents_query_string_array); ?>;

        var data = {
            op: 'sync_pandora_agents_inventory',
            user: integria_user,
            user_pass: integria_pass,
            pass: api_pass,
            params: agents_query_string_array,
            token: '|;|'
        }

        // AJAX call to check API connection.
        $.ajax({
            type: "POST",
            url: url,
            dataType: "json",
            data: data
        })
        .done(function(data, textStatus, xhr) {
            showSuccessImage();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            showFailureImage();
        })
        .always(function(xhr, textStatus) {
            hideLoadingImage();
        });
    }

    $('#button-test-integria').click(handleTest);
    $('#button-sync-inventory').click(handleInventorySync);

</script>
