<?php
/**
 * General setup.
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

use function PHPSTORM_META\map;

// File begin.
global $config;


check_login();

if (is_ajax()) {
    $test_address = get_parameter('test_address', '');
    $params = io_safe_output(get_parameter('params', ''));

    $res = send_test_email(
        $test_address,
        $params
    );

    echo $res;

    // Exit after ajax response.
    exit();
}

echo "<div id='dialog' title='".__('Enforce https Information')."' class='invisible'>";
echo "<p class='center'>".__('If SSL is not properly configured you will lose access to ').get_product_name().__(' Console').'</p>';
echo '</div>';

$performance_variables_control = (array) json_decode(io_safe_output($config['performance_variables_control']));
$sources = [];
$sources['system'] = __('System');
$sources['sql'] = __('Database');

// ACL Ips for API.
if (isset($_POST['list_ACL_IPs_for_API']) === true) {
    $list_ACL_IPs_for_API = get_parameter_post('list_ACL_IPs_for_API');
} else {
    $list_ACL_IPs_for_API = get_parameter_get(
        'list_ACL_IPs_for_API',
        implode("\n", $config['list_ACL_IPs_for_API'])
    );
}

// Enable Netflow.
if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
    $rbt_disabled = true;
} else {
    $rbt_disabled = false;
}

// Zone names.
$zone_name = [
    'Africa'     => __('Africa'),
    'America'    => __('America'),
    'Antarctica' => __('Antarctica'),
    'Arctic'     => __('Arctic'),
    'Asia'       => __('Asia'),
    'Atlantic'   => __('Atlantic'),
    'Australia'  => __('Australia'),
    'Europe'     => __('Europe'),
    'Indian'     => __('Indian'),
    'Pacific'    => __('Pacific'),
    'UTC'        => __('UTC'),
];

$zone_selected = get_parameter('zone');
if ($zone_selected == '') {
    if ($config['timezone'] != '') {
        $zone_array = explode('/', $config['timezone']);
        $zone_selected = $zone_array[0];
    } else {
        $zone_selected = 'Europe';
    }
}

$timezones = timezone_identifiers_list();
foreach ($timezones as $timezone) {
    if (strpos($timezone, $zone_selected) !== false) {
        $timezone_n[$timezone] = $timezone;
    }
}

// Force Public URL Dialog.
html_print_div(
    [
        'id'      => 'force_public_url_dialog',
        'class'   => 'invisible',
        'content' => __('If public URL is not properly configured you will lose access to ').get_product_name().__(' Console'),
    ]
);

// Inventory blacklist.
$inventory_changes_blacklist_id = get_parameter(
    'inventory_changes_blacklist',
    $config['inventory_changes_blacklist']
);

if (!is_array($inventory_changes_blacklist_id)) {
    $inventory_changes_blacklist_id = explode(
        ',',
        $inventory_changes_blacklist_id
    );
}

$inventory_modules = db_get_all_rows_sql(
    'SELECT mi.id_module_inventory, mi.name module_inventory_name, os.name os_name
    FROM tmodule_inventory mi, tconfig_os os
    WHERE os.id_os = mi.id_os'
);

$inventory_changes_blacklist = [];
$inventory_changes_blacklist_out = [];

foreach ($inventory_modules as $inventory_module) {
    if (in_array($inventory_module['id_module_inventory'], $inventory_changes_blacklist_id)) {
        $inventory_changes_blacklist[$inventory_module['id_module_inventory']] = $inventory_module['module_inventory_name'].' ('.$inventory_module['os_name'].')';
    } else {
        $inventory_changes_blacklist_out[$inventory_module['id_module_inventory']] = $inventory_module['module_inventory_name'].' ('.$inventory_module['os_name'].')';
    }
}

$select_out = html_print_select(
    $inventory_changes_blacklist_out,
    'inventory_changes_blacklist_out[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:200px'
);
$arrows = ' ';
$select_in = html_print_select(
    $inventory_changes_blacklist,
    'inventory_changes_blacklist[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width:200px'
);

$table_ichanges = '<table>
        <tr>
            <td style="width: 45%">'.__('Out of black list').'</td>
            <td style="width: 10%"></td>
            <td style="width: 45%">'.__('In black list').'</td>
        </tr>
        <tr>
            <td style="width: 45%">'.$select_out.'</td>
            <td style="width: 10%">
                <a href="javascript:">'.html_print_image('images/arrow@svg.svg', true, ['style' => 'rotate: 180deg;', 'id' => 'right_iblacklist', 'alt' => __('Push selected modules into blacklist'), 'title' => __('Push selected modules into blacklist'), 'class' => 'main_menu_icon invert_filter']).'</a>
                <br><br>
                <a href="javascript:">'.html_print_image('images/arrow@svg.svg', true, ['style' => 'rotate: 0', 'id' => 'left_iblacklist', 'alt' => __('Pop selected modules out of blacklist'), 'title' => __('Pop selected modules out of blacklist'), 'class' => 'main_menu_icon invert_filter']).'</a>
            </td>
            <td style="width: 45%">'.$select_in.'</td>
        </tr>
    </table>';

$modes_tutorial = [
    'full'      => __('Full mode'),
    'on_demand' => __('On demand'),
    'expert'    => __('Expert'),
];

$config['past_planned_downtimes'] = isset(
    $config['past_planned_downtimes']
) ? $config['past_planned_downtimes'] : 1;

$table = new stdClass();
$table->class = 'filter-table-adv';
$table->id = 'setup_general';
$table->width = '100%';
$table->data = [];
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

// Current config["language"] could be set by user, not taken from global setup !
$current_system_lang = db_get_sql(
    'SELECT `value` FROM tconfig WHERE `token` = "language"'
);

if ($current_system_lang === '') {
    $current_system_lang = 'en';
}

$i = 0;

$table->data[$i][] = html_print_label_input_block(
    __('Language code'),
    html_print_select_from_sql(
        'SELECT id_language, name FROM tlanguage',
        'language',
        $current_system_lang,
        '',
        '',
        '',
        true,
        false,
        true,
        false,
        'width:100%'
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Remote config directory'),
    html_print_input_text(
        'remote_config',
        io_safe_output($config['remote_config']),
        '',
        30,
        100,
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Chromium path'),
    html_print_input_text(
        'chromium_path',
        io_safe_output(
            $config['chromium_path']
        ),
        '',
        30,
        100,
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Auto login (hash) password'),
    html_print_input_password(
        'loginhash_pwd',
        io_output_password($config['loginhash_pwd']),
        '',
        15,
        15,
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Time source'),
    html_print_select(
        $sources,
        'timesource',
        $config['timesource'],
        '',
        '',
        '',
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Attachment store'),
    html_print_input_text(
        'attachment_store',
        io_safe_output($config['attachment_store']),
        '',
        50,
        255,
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Enforce https'),
    html_print_checkbox_switch_extended(
        'https',
        1,
        $config['https'],
        false,
        '',
        '',
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Automatic check for updates'),
    html_print_checkbox_switch(
        'autoupdate',
        1,
        $config['autoupdate'],
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Use cert of SSL'),
    html_print_checkbox_switch_extended(
        'use_cert',
        1,
        $config['use_cert'],
        false,
        '',
        '',
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Path of SSL Cert.'),
    html_print_input_text(
        'cert_path',
        io_safe_output($config['cert_path']),
        '',
        50,
        255,
        true
    ),
    [
        'div_id'    => 'ssl-path-tr',
        'div_style' => 'display: none',
    ]
);

$table->data[$i][] = html_print_label_input_block(
    __('API password'),
    html_print_input_password(
        'api_password',
        io_output_password($config['api_password']),
        '',
        25,
        255,
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('IP list with API access'),
    html_print_textarea(
        'list_ACL_IPs_for_API',
        2,
        25,
        $list_ACL_IPs_for_API,
        'class="height_130px"',
        true
    )
);


$table->data[$i][] = html_print_label_input_block(
    __('Enable GIS features'),
    html_print_checkbox_switch(
        'activate_gis',
        1,
        $config['activate_gis'],
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Enable Netflow'),
    html_print_checkbox_switch_extended(
        'activate_netflow',
        1,
        $config['activate_netflow'],
        $rbt_disabled,
        '',
        '',
        true
    )
);


$table->data[$i][] = html_print_label_input_block(
    __('General network path').ui_print_help_tip(__('Base directory where the netflow and sflow subdirectories will be located to store the corresponding data.'), true),
    html_print_input_text(
        'general_network_path',
        $config['general_network_path'],
        '',
        40,
        255,
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Enable Feedback').ui_print_help_tip(__(' It enables the \'give feedback\' window in the help menu at the top right.'), true),
    html_print_checkbox_switch_extended(
        'activate_feedback',
        true,
        $config['activate_feedback'],
        false,
        '',
        '',
        true
    )
);

$table->colspan[$i][] = 2;
$table->data[$i++][] = html_print_label_input_block(
    __('Server timezone setup'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_input_text_extended(
                'timezone_text',
                $config['timezone'],
                'text-timezone_text',
                '',
                25,
                25,
                false,
                '',
                'readonly',
                true
            ).html_print_image(
                'images/edit.svg',
                true,
                [
                    'id'    => 'change_timezone',
                    'title' => __('Change timezone'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            ).html_print_select(
                $zone_name,
                'zone',
                $zone_selected,
                'show_timezone();',
                '',
                '',
                true
            ).html_print_select(
                $timezone_n,
                'timezone',
                $config['timezone'],
                '',
                '',
                '',
                true
            ),
        ],
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Public URL').ui_print_help_tip(__('It is useful to complete this field when you have a reverse proxy, for example, with the mod_proxy mode of the Apache web server.'), true),
    html_print_input_text(
        'public_url',
        $config['public_url'],
        '',
        40,
        255,
        true
    )
);

if (isset($config['force_public_url']) === false) {
    $config['force_public_url'] = '';
}

$table->data[$i++][] = html_print_label_input_block(
    __('Force use Public URL'),
    html_print_switch(
        [
            'name'  => 'force_public_url',
            'value' => $config['force_public_url'],
        ]
    )
);

if (isset($config['public_url_exclusions']) === false) {
    $config['public_url_exclusions'] = '';
}

$table->data[$i++][] = html_print_label_input_block(
    __('Public URL host exclusions'),
    html_print_textarea(
        'public_url_exclusions',
        2,
        25,
        $config['public_url_exclusions'],
        'class="height_50px w300px"',
        true
    )
);

// Inventory changes blacklist.
$table->data[$i][] = html_print_label_input_block(
    __('Inventory changes blacklist').ui_print_help_tip(__('Inventory modules included within the denied list will not generate events when they change.'), true),
    $table_ichanges
);

$table->data[$i++][] = html_print_label_input_block(
    __('Server logs directory'),
    html_print_input_text(
        'server_log_dir',
        $config['server_log_dir'],
        '',
        50,
        255,
        true
    )
);
$help_tip = ui_print_help_tip(
    __('While this option is enabled, no events or alerts will be generated, but data will continue to be received.'),
    true
);
$table->data[$i][] = html_print_label_input_block(
    __('Event storm protection').$help_tip,
    html_print_checkbox_switch(
        'event_storm_protection',
        1,
        $config['event_storm_protection'],
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Command Snapshot').ui_print_help_tip(__('String modules that return more than one line will display their content as formatted text in the form of a command console.'), true),
    html_print_checkbox_switch(
        'command_snapshot',
        1,
        $config['command_snapshot'],
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Change remote config encoding').ui_print_help_tip(__('Enabling this parameter uses encoding of the configuration files generated by the agents instead of converting everything to UTF-8.'), true),
    html_print_checkbox_switch(
        'use_custom_encoding',
        1,
        $config['use_custom_encoding'],
        true
    )
);
$table->data[$i++][] = html_print_label_input_block(
    __('Referer security').ui_print_help_tip(__('When it is active, the source of the requests is checked. If the user comes from a URL external to Pandora FMS, the source of the activity will be considered suspicious.'), true),
    html_print_checkbox_switch(
        'referer_security',
        1,
        $config['referer_security'],
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Log size limit in system logs viewer extension'),
    html_print_div(
        [
            'class'   => '',
            'content' => html_print_input_text(
                'max_log_size',
                $config['max_log_size'],
                '',
                20,
                255,
                true
            ).html_print_label(
                ' x1000',
                'max_log_size',
                true
            ),
        ],
        true
    )
);
$table->data[$i++][] = html_print_label_input_block(
    __('Tutorial mode'),
    html_print_select(
        $modes_tutorial,
        'tutorial_mode',
        $config['tutorial_mode'],
        '',
        '',
        0,
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Allow create scheduled downtimes in the past').ui_print_help_tip(__('It allows the possibility to create scheduled downtimes on past dates and thus modify SLA reports retroactively.'), true),
    html_print_checkbox_switch(
        'past_planned_downtimes',
        1,
        $config['past_planned_downtimes'],
        true
    )
);
$table->data[$i++][] = html_print_label_input_block(
    __('Limit for bulk operations').ui_print_help_tip(__('Limit of elements that can be modified by one-time bulk operations. The limit prevents the operation from failing due to lack of memory.'), true),
    html_print_input(
        [
            'type'   => 'number',
            'size'   => 5,
            'max'    => $performance_variables_control['limit_parameters_massive']->max,
            'name'   => 'limit_parameters_massive',
            'value'  => $config['limit_parameters_massive'],
            'return' => true,
            'min'    => $performance_variables_control['limit_parameters_massive']->min,
            'style'  => 'width:50%',
        ]
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Include agents manually disabled').ui_print_help_tip(__('It enables the display of manually disabled agents in certain Console views.'), true),
    html_print_checkbox_switch(
        'include_agents',
        1,
        $config['include_agents'],
        true
    )
);
$table->data[$i++][] = html_print_label_input_block(
    __('Set alias as name by default in agent creation').ui_print_help_tip(__('When this parameter is activated, the selection box of the agent creation menu collects the alias entered in the form and also saves it as the name of the agent (unique identifier).'), true),
    html_print_checkbox_switch(
        'alias_as_name',
        1,
        $config['alias_as_name'],
        true
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Unique IP').ui_print_help_tip(__('By activating this parameter, the console will prevent users from creating an agent with the same IP address as another one.'), true),
    html_print_checkbox_switch(
        'unique_ip',
        1,
        $config['unique_ip'],
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Module custom ID readonly').ui_print_help_tip(__('It blocks the editing of the module custom ID from the Console, but editing from CLI and API is allowed. This is useful for integrations with third-party tools that manage the value of this field automatically.'), true),
    html_print_checkbox_switch(
        'module_custom_id_ro',
        1,
        $config['module_custom_id_ro'],
        true
    ).ui_print_input_placeholder(
        __('Useful for integrations'),
        true
    )
);

$help_tip = ui_print_help_tip(
    __('This log is recommended to be DISABLED by default due to the large amount of debug data it generates.'),
    true
);
$table->data[$i][] = html_print_label_input_block(
    __('Enable console log').$help_tip,
    html_print_checkbox_switch(
        'console_log_enabled',
        1,
        $config['console_log_enabled'],
        true
    ).ui_print_input_placeholder(
        __('Log location').': /var/log/php-fpm/error.log',
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Enable audit log'),
    html_print_checkbox_switch(
        'audit_log_enabled',
        1,
        $config['audit_log_enabled'],
        true
    ).ui_print_input_placeholder(
        __('Log location').': pandora_console/log/audit.log',
        true
    )
);

$url = 'https://pandorafms.com/manual/!772/en/documentation/04_using/12_console_setup#dedicated_console_for_reports';
$table->data[$i][] = html_print_label_input_block(
    __('Console dedicated to report generation').ui_print_help_tip(__('It allows you to enable the Web Console in dedicated reporting mode, see section \'Dedicated Console for Reports\' for more information.'), true),
    html_print_checkbox_switch(
        'reporting_console_enable',
        1,
        $config['reporting_console_enable'],
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Check conexion interval').ui_print_help_tip(__('Time interval (in seconds) to check the connection to the database server. Default 180, minimum value 60.'), true),
    html_print_input_number(
        [
            'name'  => 'check_conexion_interval',
            'min'   => 90,
            'value' => $config['check_conexion_interval'],
        ]
    )
);

$help_tip = ui_print_help_tip(
    __('If there is any event “In process” with a specific additional ID and a “New” event with that additional ID is received, it will be created as “In process.” New events will also inherit the Event Custom ID from the old event.'),
    true
);

$table->data[$i][] = html_print_label_input_block(
    __('Keep In process status for new events with extra ID').$help_tip,
    html_print_checkbox_switch(
        'keep_in_process_status_extra_id',
        1,
        $config['keep_in_process_status_extra_id'],
        true
    )
);

$table->data[$i++][] = html_print_label_input_block(
    __('Max. hours old events comments').ui_print_help_tip(__('When the grouped events are displayed, the comments of all the grouped identical events are displayed, but limiting it to the last N hours.'), true),
    html_print_input_number(
        [
            'name'  => 'max_hours_old_event_comment',
            'min'   => 0,
            'value' => $config['max_hours_old_event_comment'],
        ]
    )
);
$table->data[$i][] = html_print_label_input_block(
    __('Show experimental features'),
    html_print_checkbox_switch(
        'show_experimental_features',
        1,
        $config['show_experimental_features'],
        true
    )
);
$table->data[$i++][] = html_print_label_input_block(
    __('Number of modules in queue'),
    html_print_input_number(
        [
            'name'  => 'number_modules_queue',
            'min'   => 0,
            'value' => $config['number_modules_queue'],
        ]
    )
);

$table->data[$i][] = html_print_label_input_block(
    __('Easter eggs'),
    html_print_checkbox_switch(
        'eastern_eggs_disabled',
        1,
        $config['eastern_eggs_disabled'],
        true
    )
);


echo '<form class="max_floating_element_size" id="form_setup" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&amp;section=general&amp;pure='.$config['pure'].'">';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('General options').'</legend>';

    html_print_input_hidden('update_config', 1);
    html_print_table($table);

$encryption = [
    'ssl'   => 'SSL',
    'sslv2' => 'SSLv2',
    'sslv3' => 'SSLv3',
    'tls'   => 'STARTTLS',
];

echo '</fieldset>';

echo '<fieldset class="margin-bottom-10">';
echo '<legend>'.__('Mail configuration').'</legend>';

    ui_print_warning_message(
        __(
            'Please notice that some providers like Gmail or Office365 need to setup/enable manually external connections using SMTP and you need to use STARTTLS on port 587.

    If you have manual settings in your pandora_server.conf, please note these settings will ignore this console setup.'
        )
    );

    $table_mail_conf = new stdClass();
    $table_mail_conf->width = '100%';
    $table_mail_conf->class = 'databox filter-table-adv';
    $table_mail_conf->size = [];
    $table_mail_conf->size[0] = '50%';
    $table_mail_conf->size[1] = '50%';
    $table_mail_conf->data = [];

    $table_mail_conf->data[0][] = html_print_label_input_block(
        __('From address'),
        html_print_input_text(
            'email_from_dir',
            $config['email_from_dir'],
            '',
            30,
            100,
            true
        )
    );

    $table_mail_conf->data[0][] = html_print_label_input_block(
        __('From name'),
        html_print_input_text(
            'email_from_name',
            $config['email_from_name'],
            '',
            30,
            100,
            true
        )
    );

    $table_mail_conf->data[1][] = html_print_label_input_block(
        __('SMTP Server'),
        html_print_input_text(
            'email_smtpServer',
            $config['email_smtpServer'],
            '',
            30,
            100,
            true
        )
    );

    $table_mail_conf->data[1][] = html_print_label_input_block(
        __('SMTP Port'),
        html_print_input_text(
            'email_smtpPort',
            $config['email_smtpPort'],
            '',
            30,
            100,
            true
        )
    );

    $table_mail_conf->data[2][] = html_print_label_input_block(
        __('Email user'),
        html_print_input_text(
            'email_username',
            $config['email_username'],
            '',
            30,
            100,
            true
        )
    );
    $table_mail_conf->data[2][] = html_print_label_input_block(
        __('Email password'),
        html_print_input_password(
            'email_password',
            io_output_password(
                $config['email_password']
            ),
            '',
            30,
            100,
            true
        )
    );

    $table_mail_conf->data[3][] = html_print_label_input_block(
        __('Encryption'),
        html_print_select(
            $encryption,
            'email_encryption',
            $config['email_encryption'],
            '',
            __('none'),
            0,
            true
        )
    );

    $uniqid = uniqid();

    print_email_test_modal_window($uniqid);

    html_print_input_hidden('update_config', 1);
    html_print_table($table_mail_conf);

    echo '</fieldset>';

    echo '<fieldset class="margin-bottom-10">';
    echo '<legend>'.__('NCM Configuration').'</legend>';

    $table_ncm_config = new stdClass();
    $table_ncm_config->width = '100%';
    $table_ncm_config->class = 'databox filter-table-adv';
    $table_ncm_config->size = [];
    $table_ncm_config->size[0] = '50%';
    $table_ncm_config->data = [];

    if (isset($config['tftp_server_ip']) === false) {
        $config['tftp_server_ip'] = '';
    }

    $table_ncm_config->data[0][] = html_print_label_input_block(
        __('FTP server IP').ui_print_help_tip(__('This value will be used by TFTP_SERVER_IP macro in NCM scripts.'), true),
        html_print_input_text(
            'tftp_server_ip',
            $config['tftp_server_ip'],
            '',
            false,
            255,
            true,
            false,
            false,
            '',
            'w50p'
        )
    );

    html_print_table($table_ncm_config);

    echo '</fieldset>';

    html_print_action_buttons(
        html_print_submit_button(
            __('Update'),
            'update_button',
            false,
            ['icon' => 'update'],
            true
        ).html_print_button(
            __('Email test'),
            'email_test_dialog',
            false,
            'show_email_test("'.$uniqid.'");',
            [
                'icon' => 'mail',
                'mode' => 'secondary',
            ],
            true
        )
    );

    echo '</form>';
    ?>
<script type="text/javascript">
function show_timezone () {
    zone = $("#zone").val();
    $.ajax({
        type: "POST",
        url: "ajax.php",
        data: "page=<?php echo $_GET['sec2']; ?>&select_timezone=1&zone=" + zone,
        dataType: "json",
        success: function(data) {
            $("#timezone").empty();
            jQuery.each (data, function (id, value) {
                timezone = value;
                $("select[name='timezone']").append($("<option>").val(timezone).html(timezone));
            });
        }
    });
}

$(document).ready (function () {

    $("#zone").attr("hidden", true);
    $("#timezone").attr("hidden", true);

    $("#change_timezone").click(function () {
        $("#zone").attr("hidden", false);
        $("#timezone").attr("hidden", false);
    });

    if ($("input[name=use_cert]").is(':checked')) {
        $('#ssl-path-tr').show();
    }

    $("input[name=use_cert]").change(function () {
        if( $(this).is(":checked") )
                $('#ssl-path-tr').show();
            else
                $('#ssl-path-tr').hide();
        
    });
    $("input[name=https]").change(function (){
        if($("input[name=https]").prop('checked')) {
            $("#dialog").dialog({
            modal: true,
            width: 500,
            buttons:[
                {
                    class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-next',
                    text: "<?php echo __('OK'); ?>",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });
        }
    })

    $("input[name=force_public_url]").change(function (){
        if($("input[name=force_public_url]").prop('checked')) {
            $("#force_public_url_dialog").dialog({
            modal: true,
            width: 500,
            buttons: [
                {
                    class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-next',
                    text: "<?php echo __('OK'); ?>",
                    click: function(){
                        $(this).dialog("close");
                    }
                }
            ]
        });
        }
    })

    $("#right_iblacklist").click (function () {
        jQuery.each($("select[name='inventory_changes_blacklist_out[]'] option:selected"), function (key, value) {
            imodule_name = $(value).html();
            if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                id_imodule = $(value).attr('value');
                $("select[name='inventory_changes_blacklist[]']")
                    .append(
                        $("<option selected='selected'></option>")
                            .val(id_imodule)
                            .text(imodule_name)
                    );
                $("#inventory_changes_blacklist_out")
                    .find("option[value='" + id_imodule + "']").remove();
                $("#inventory_changes_blacklist")
                    .find("option[value='']").remove();
                if($("#inventory_changes_blacklist_out option").length == 0) {
                    $("select[name='inventory_changes_blacklist_out[]']")
                        .append(
                            $("<option></option>")
                                .val('')
                                .html('<i><?php echo __('None'); ?></i>')
                        );
                }
            }
        });
    });
    $("#left_iblacklist").click (function () {
        jQuery.each($("select[name='inventory_changes_blacklist[]'] option:selected"), function (key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("select[name='inventory_changes_blacklist_out[]']")
                        .append(
                            $("<option></option>")
                                .val(id_imodule)
                                .text(imodule_name)
                        );
                    $("#inventory_changes_blacklist")
                        .find("option[value='" + id_imodule + "']").remove();
                    $("#inventory_changes_blacklist_out")
                        .find("option[value='']").remove();
                    if($("#inventory_changes_blacklist option").length == 0) {
                        $("select[name='inventory_changes_blacklist[]']")
                            .append(
                                $("<option></option>")
                                    .val('')
                                    .html('<i><?php echo __('None'); ?></i>')
                            );
                    }
                }
        });

        $("#inventory_changes_blacklist > option").each(function(key, value) {
            $(value).prop('selected',true).trigger('change');
        });

    });

    $("#inventory_changes_blacklist > option").each(function(key, value) {
        $(value).prop('selected',true).trigger('change');
    });
});
</script>
