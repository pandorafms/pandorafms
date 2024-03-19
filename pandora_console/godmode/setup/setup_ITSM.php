<?php
/**
 * ITSM setup.
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\ITSM\ITSM;

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

$error = '';
$group_values = [];
$priority_values = [];
$object_types_values = [];
$status_values = [];
$node = [];
try {
    $ITSM = new ITSM();
    $has_connection = $ITSM->ping();
    $group_values = $ITSM->getGroups();
    $priority_values = $ITSM->getPriorities();
    $status_values = $ITSM->getStatus();
    $object_types_values = $ITSM->getObjectypes();
    if ((bool) get_parameter('update_config', 0) === true) {
        $set_config_inventories = $ITSM->createNode(
            [
                'serverAuth'         => $config['server_unique_identifier'],
                'apiPass'            => $config['api_password'],
                'agentsForExecution' => $config['ITSM_agents_sync'],
                'path'               => $config['ITSM_public_url'],
                'label'              => array_keys(servers_get_names())[0],
                'nodeId'             => $config['metaconsole_node_id'],
            ]
        );
    }

    try {
        $node = $ITSM->getNode($config['server_unique_identifier']);
    } catch (\Throwable $th) {
        $node = [];
    }
} catch (\Throwable $th) {
    $error = $th->getMessage();
    $has_connection = false;
}

if ($has_connection === false && $config['ITSM_enabled']) {
    ui_print_error_message(__('ITSM API is not reachable, %s', $error));
}

$table_enable = new StdClass();
$table_enable->data = [];
$table_enable->width = '100%';
$table_enable->id = 'itsm-enable-setup';
$table_enable->class = 'databox filters';
$table_enable->size['name'] = '30%';
$table_enable->style['name'] = 'font-weight: bold';

// Enable Pandora ITSM.
$row = [];
$row['name'] = __('Enable Pandora ITSM');
$row['control'] = html_print_checkbox_switch('ITSM_enabled', 1, $config['ITSM_enabled'], true);
$table_enable->data['ITSM_enabled'] = $row;

// Remote config table.
$table_remote = new StdClass();
$table_remote->data = [];
$table_remote->width = '100%';
$table_remote->styleTable = 'margin-bottom: 10px;';
$table_remote->id = 'ITSM-remote-setup';
$table_remote->class = 'databox filters filter-table-adv';
$table_remote->size['hostname'] = '50%';
$table_remote->size['api_pass'] = '50%';

// Enable ITSM user configuration.
$row = [];
$row['user_level'] = html_print_label_input_block(
    __('Pandora ITSM configuration at user level'),
    html_print_checkbox_switch(
        'ITSM_user_level_conf',
        1,
        $config['ITSM_user_level_conf'],
        true
    )
);
$table_remote->data['ITSM_user_level_conf'] = $row;

// ITSM hostname.
$row = [];
$row['hostname'] = html_print_label_input_block(
    __('URL to Pandora ITSM setup').ui_print_help_tip(
        __('Full URL to your Pandora ITSM setup (e.g., http://192.168.1.20/integria/api/v2).'),
        true
    ),
    html_print_input_text(
        'ITSM_hostname',
        $config['ITSM_hostname'],
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'ITSM-remote-setup-ITSM_hostname']
);

if (isset($config['ITSM_token']) === false) {
    $config['ITSM_token'] = '';
}

// ITSM token.
$row['password'] = html_print_label_input_block(
    __('Token'),
    html_print_input_password(
        'ITSM_token',
        io_output_password($config['ITSM_token']),
        '',
        30,
        100,
        true
    ),
    ['div_class' => 'ITSM-remote-setup-ITSM_token']
);
$table_remote->data['ITSM_token'] = $row;

// Test.
$row = [];
$button_test = html_print_button(
    __('Test'),
    'ITSM',
    false,
    '',
    [
        'icon' => 'cog',
        'mode' => 'secondary mini',
    ],
    true
);
$button_test .= '<span id="ITSM-spinner" class="invisible">&nbsp;';
$button_test .= html_print_image(
    'images/spinner.gif',
    true
);
$button_test .= '</span>';
$button_test .= '<span id="ITSM-success" class="invisible">&nbsp;';
$button_test .= html_print_image(
    'images/status_sets/default/severity_normal.png',
    true
);
$button_test .= '&nbsp;'.__('Connection its OK').'</span>';
$button_test .= '<span id="ITSM-failure" class="invisible">&nbsp;';
$button_test .= html_print_image(
    'images/status_sets/default/severity_critical.png',
    true
);
$button_test .= '&nbsp;'.__('Connection failed').'</span>';
$button_test .= '&nbsp;<span id="ITSM-message" class="invisible"></span>';

$row['control'] = html_print_label_input_block(
    __('Test connection pandora to ITSM'),
    $button_test,
    ['div_class' => 'ITSM-remote-setup-ITSM_token']
);
$table_remote->data['ITSM_test'] = $row;

$row = [];
$itsm_public_url = $config['ITSM_public_url'];
if (empty($itsm_public_url) === true) {
    $itsm_public_url = $config['homeurl'];
    if (isset($config['public_url']) === true && empty($config['public_url']) === false) {
        $itsm_public_url = $config['public_url'];
    }
}

$row['publicUrl'] = html_print_label_input_block(
    __('URL conect to API %s', get_product_name()).ui_print_help_tip(
        __('Full URL to your Pandora (e.g., http://192.168.1.20).'),
        true
    ),
    html_print_input_text(
        'ITSM_public_url',
        $itsm_public_url,
        '',
        30,
        100,
        true
    )
);

$row['agentsSync'] = html_print_label_input_block(
    __('Number Agents to synchronize').ui_print_help_tip(
        __('Number of agents that will synchronize at the same time, minimum 10 max 1000'),
        true
    ),
    html_print_input_number(
        [
            'name'  => 'ITSM_agents_sync',
            'min'   => 10,
            'max'   => 1000,
            'value' => ($config['ITSM_agents_sync'] ?? 20),
        ]
    )
);

$table_remote->data['ITSM_sync_inventory'] = $row;

// Test.
$row = [];
$button_test_pandora = html_print_button(
    __('Test'),
    'ITSM-pandora',
    false,
    '',
    [
        'icon' => 'cog',
        'mode' => 'secondary mini',
    ],
    true
);
$button_test_pandora .= '<span id="ITSM-spinner-pandora" class="invisible">&nbsp;';
$button_test_pandora .= html_print_image(
    'images/spinner.gif',
    true
);
$button_test_pandora .= '</span>';
$button_test_pandora .= '<span id="ITSM-success-pandora" class="invisible">&nbsp;';
$button_test_pandora .= html_print_image(
    'images/status_sets/default/severity_normal.png',
    true
);
$button_test_pandora .= '&nbsp;'.__('Connection its OK').'</span>';
$button_test_pandora .= '<span id="ITSM-failure-pandora" class="invisible">&nbsp;';
$button_test_pandora .= html_print_image(
    'images/status_sets/default/severity_critical.png',
    true
);
$button_test_pandora .= '&nbsp;'.__('Connection failed').'</span>';
$button_test_pandora .= '&nbsp;<span id="ITSM-message-pandora" class="invisible"></span>';

$row['control-test'] = html_print_label_input_block(
    __('Test conection ITSM to pandora'),
    $button_test_pandora
);

if (empty($node) === false) {
    $progressbar = '';

    $progress = 0;
    if (empty($node['total']) === false) {
        if (empty($node['accumulate']) === true) {
            $node['accumulate'] = 0;
        }

        $progress = round(($node['accumulate'] * 100 / $node['total']));
    }

    if (empty($node['error']) === false) {
        $progressbar = $node['error'];
    } else if (empty($node['total']) === false) {
        $progressbar = '<div class="flex mrgn_5px">';
        $progressbar .= ui_progress($progress, '150px', '1.3', '#14524f', true, '', false, 'margin-right:5px; color:#c0ccdc');
        $progressbar .= ' ( '.$node['accumulate'].' / '.$node['total'].' ) '.__('Agents');
        $progressbar .= '</div>';
    } else {
        $progressbar = '--';
    }

    // $progressbar .= (empty($node['dateStart']) === false) ? human_time_comparation($node['dateStart']) : __('Never');
    $row['control-test-pandora'] = html_print_label_input_block(
        __('Progress agents to synch'),
        $progressbar
    );
}

$table_remote->data['ITSM_test_pandora'] = $row;

// Alert settings.
$table_alert_settings = new StdClass();
$table_alert_settings->data = [];
$table_alert_settings->rowspan = [];
$table_alert_settings->width = '100%';
$table_alert_settings->styleTable = 'margin-bottom: 10px;';
$table_alert_settings->id = 'ITSM-settings-setup';
$table_alert_settings->class = 'databox filters filter-table-adv';
$table_alert_settings->size[0] = '50%';
$table_alert_settings->size[1] = '50%';

// Alert incident title.
$table_alert_settings->data[0][0] = html_print_label_input_block(
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
$table_alert_settings->rowspan[0][1] = 3;
$table_alert_settings->data[0][1] = html_print_label_input_block(
    __('Ticket body'),
    html_print_textarea(
        'incident_content',
        9,
        25,
        $config['incident_content'],
        '',
        true
    )
);

// Alert default group.
$table_alert_settings->data[1][0] = html_print_label_input_block(
    __('Group'),
    html_print_select(
        $group_values,
        'default_group',
        $config['default_group'],
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false
    )
);

// Alert default owner.
$table_alert_settings->data[2][0] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_pandora_itsm(
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

// Alert default incident status.
$table_alert_settings->data[3][0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $status_values,
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

// Alert default criticity.
$table_alert_settings->data[3][1] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
        $priority_values,
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

// Alert default incident type.
$table_alert_settings->data[4][0] = html_print_label_input_block(
    __('Type'),
    html_print_select(
        $object_types_values,
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

// Custom response settings.
$table_cr_settings = new StdClass();
$table_cr_settings->data = [];
$table_cr_settings->width = '100%';
$table_cr_settings->styleTable = 'margin-bottom: 10px;';
$table_cr_settings->id = 'ITSM-cr-settings-setup';
$table_cr_settings->class = 'databox filters filter-table-adv';
$table_cr_settings->size[0] = '50%';
$table_cr_settings->size[1] = '50%';

// Custom response incident title.
$table_cr_settings->data[0][0] = html_print_label_input_block(
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
$table_cr_settings->rowspan[0][1] = 3;
$table_cr_settings->data[0][1] = html_print_label_input_block(
    __('Ticket body'),
    html_print_textarea(
        'cr_incident_content',
        9,
        25,
        $config['cr_incident_content'],
        '',
        true
    )
);

// Custom response default group.
$table_cr_settings->data[1][0] = html_print_label_input_block(
    __('Group'),
    html_print_select(
        $group_values,
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

// Custom response default owner.
$table_cr_settings->data[2][0] = html_print_label_input_block(
    __('Owner'),
    html_print_autocomplete_users_from_pandora_itsm(
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

// Custom response default incident status.
$row = [];
$table_cr_settings->data[3][0] = html_print_label_input_block(
    __('Status'),
    html_print_select(
        $status_values,
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

// Custom response default criticity.
$table_cr_settings->data[3][1] = html_print_label_input_block(
    __('Priority'),
    html_print_select(
        $priority_values,
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

// Custom response default incident type.
$table_cr_settings->data[4][0] = html_print_label_input_block(
    __('Type'),
    html_print_select(
        $object_types_values,
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

// Print.
echo '<div class="center pdd_b_10px mrgn_btn_20px white_box max_floating_element_size">';
echo '<a target="_blank" rel="noopener noreferrer" href="https://pandorafms.com/itsm/">';
html_print_image(
    'images/pandoraITSM_logo.png',
    false,
    ['class' => 'w600px mrgn_top_15px']
);
echo '</a>';
echo '<br />';
echo '<div class="ITSM_title">';
echo __('Pandora ITSM');
echo '</div>';
echo '<a target="_blank" rel="noopener noreferrer" href="https://pandorafms.com/itsm/">';
echo 'https://pandorafms.com/itsm/';
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
echo '<legend>'.__('Pandora ITSM API settings').'</legend>';

html_print_table($table_remote);

echo '</fieldset>';
echo '</div>';

if ($has_connection !== false) {
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

ui_require_javascript_file('ITSM');

?>

<script type="text/javascript">
    if($('input:checkbox[name="ITSM_user_level_conf"]').is(':checked'))
    {
        $('.ITSM-remote-setup-ITSM_token').hide()
    }

    var handleUserLevel = function(event) {
        var is_checked = $('input:checkbox[name="ITSM_enabled"]').is(':checked');
        var is_checked_userlevel = $('input:checkbox[name="ITSM_user_level_conf"]').is(':checked');

        if (event.target.value == '1' && is_checked && !is_checked_userlevel) {
            showUserPass();
            $('input:checkbox[name="ITSM_user_level_conf"]').attr('checked', true);
        }
        else {
            hideUserPass();
            $('input:checkbox[name="ITSM_user_level_conf"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="ITSM_enabled"]').change(handleEnable);
    $('input:checkbox[name="ITSM_user_level_conf"]').change(handleUserLevel);

    if(!$('input:checkbox[name="ITSM_enabled"]').is(':checked')) {
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
        $('.ITSM-remote-setup-ITSM_token').hide();
    }

    var showUserPass = function () {
        $('.ITSM-remote-setup-ITSM_token').show();
    }

    var handleEnable = function (event) {
        var is_checked = $('input:checkbox[name="ITSM_enabled"]').is(':checked');

        if (event.target.value == '1' && is_checked) {
            showFields();
            $('input:checkbox[name="ITSM_enabled"]').attr('checked', true);
        }
        else {
            hideFields();
            $('input:checkbox[name="ITSM_enabled"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="ITSM_enabled"]').change(handleEnable);

    $('#button-ITSM').click(function() {
        var pass = $('input#password-ITSM_token').val();
        var host = $('input#text-ITSM_hostname').val();
        testConectionApi(pass, host);
    });

    $('#button-ITSM-pandora').click(function() {
        var path = $('input#text-ITSM_public_url').val();
        testConectionApiItsmToPandora(path);
    });

</script>
