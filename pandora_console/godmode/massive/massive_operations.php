<?php
/**
 * Main view for Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Begin.
check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access massive operation section'
    );
    include 'general/noaccess.php';
    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
require_once 'include/functions_modules.php';
require_once 'include/functions_massive_operations.php';

enterprise_include('godmode/massive/massive_operations.php');

$tab = (string) get_parameter('tab', 'massive_agents');
$option = (string) get_parameter('option');


$options_alerts = [
    'add_alerts'            => __('Bulk alert add'),
    'delete_alerts'         => __('Bulk alert delete'),
    'add_action_alerts'     => __('Bulk alert actions add'),
    'delete_action_alerts'  => __('Bulk alert actions delete'),
    'enable_disable_alerts' => __('Bulk alert enable/disable'),
    'standby_alerts'        => __('Bulk alert setting standby'),
];

$options_agents = [
    'edit_agents'   => __('Bulk agent edit'),
    'delete_agents' => __('Bulk agent delete'),
];

if (check_acl($config['id_user'], 0, 'UM')) {
    $options_users = [
        'add_profiles'    => __('Bulk profile add'),
        'delete_profiles' => __('Bulk profile delete'),
    ];
} else {
    $options_users = [];
}

$options_modules = [
    'edit_modules'   => __('Bulk module edit'),
    'copy_modules'   => __('Bulk module copy'),
    'delete_modules' => __('Bulk module delete'),
];

$options_plugins = [
    'edit_plugins' => __('Bulk plugin edit'),
];

if (! check_acl($config['id_user'], 0, 'AW')) {
    unset($options_modules['edit_modules']);
}

$options_policies = [];
$policies_options = enterprise_hook('massive_policies_options');
$policies_options = array_unique($policies_options);

if ($policies_options != ENTERPRISE_NOT_HOOK) {
    $options_policies = array_merge($options_policies, $policies_options);
}

$options_snmp = [];
$snmp_options = enterprise_hook('massive_snmp_options');
$snmp_options = array_reverse($snmp_options);
if ($snmp_options != ENTERPRISE_NOT_HOOK) {
    $options_snmp = array_merge($options_snmp, $snmp_options);
}

$options_satellite = [];
$satellite_options = enterprise_hook('massive_satellite_options');

if ($satellite_options != ENTERPRISE_NOT_HOOK) {
    $options_satellite = array_merge($options_satellite, $satellite_options);
}

$options_services = enterprise_hook('massive_services_options');
if ($options_services === ENTERPRISE_NOT_HOOK) {
    $options_services = [];
}


if (in_array($option, array_keys($options_alerts))) {
    $tab = 'massive_alerts';
} else if (in_array($option, array_keys($options_agents))) {
    $tab = 'massive_agents';
} else if (in_array($option, array_keys($options_users))) {
    $tab = 'massive_users';
} else if (in_array($option, array_keys($options_modules))) {
    $tab = 'massive_modules';
} else if (in_array($option, array_keys($options_policies))) {
    $tab = 'massive_policies';
} else if (in_array($option, array_keys($options_snmp))) {
    $tab = 'massive_snmp';
} else if (in_array($option, array_keys($options_satellite))) {
    $tab = 'massive_satellite';
} else if (in_array($option, array_keys($options_plugins))) {
    $tab = 'massive_plugins';
} else if (in_array($option, array_keys($options_services))) {
    $tab = 'massive_services';
}

if ($tab == 'massive_agents' && $option == '') {
    $option = 'edit_agents';
}

if ($tab == 'massive_modules' && $option == '') {
    $option = 'edit_modules';
}

if ($tab == 'massive_policies' && $option == '') {
    $option = 'edit_policy_modules';
}

switch ($option) {
    case 'edit_agents':
        $help_header = 'massive_agents_tab';
    break;

    case 'edit_modules':
        $help_header = 'massive_modules_tab';
    break;

    case 'edit_policy_modules':
        $help_header = 'massive_policies_tab';
    break;

    default:
        $help_header = '';
    break;
}

switch ($tab) {
    case 'massive_alerts':
        $options = $options_alerts;
    break;

    case 'massive_agents':
        $options = $options_agents;
    break;

    case 'massive_modules':
        $options = $options_modules;
    break;

    case 'massive_users':
        $options = $options_users;
    break;

    case 'massive_policies':
        $options = $options_policies;
    break;

    case 'massive_snmp':
        $options = $options_snmp;
    break;

    case 'massive_satellite':
        $options = $options_satellite;
    break;

    case 'massive_plugins':
        $options = $options_plugins;
    break;

    case 'massive_services':
        $options = $options_services;
    break;

    default:
        // Default.
    break;
}

// Set the default option of the category.
if ($option == '') {
    $option = array_shift(array_keys($options));
}

$alertstab = [
    'text'   => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_alerts">'.html_print_image(
        'images/bell.png',
        true,
        [
            'title' => __('Alerts operations'),
            'class' => 'invert_filter',
        ]
    ).'</a>', 'active' => $tab == 'massive_alerts',
];

$userstab = [
    'text'   => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users">'.html_print_image(
        'images/user.png',
        true,
        [
            'title' => __('Users operations'),
            'class' => 'invert_filter',
        ]
    ).'</a>', 'active' => $tab == 'massive_users',
];

$agentstab = [
    'text'   => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_agents">'.html_print_image(
        'images/agent.png',
        true,
        [
            'title' => __('Agents operations'),
            'class' => 'invert_filter',
        ]
    ).'</a>', 'active' => $tab == 'massive_agents',
];

        $modulestab = [
            'text'   => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_modules">'.html_print_image(
                'images/module.png',
                true,
                [
                    'title' => __('Modules operations'),
                    'class' => 'invert_filter',
                ]
            ).'</a>', 'active' => $tab == 'massive_modules',
        ];

        $pluginstab = [
            'text'   => '<a href="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_plugins">'.html_print_image(
                'images/plugin.png',
                true,
                [
                    'title' => __('Plugins operations'),
                    'class' => 'invert_filter',
                ]
            ).'</a>', 'active' => $tab == 'massive_plugins',
        ];

        $policiestab = enterprise_hook('massive_policies_tab');

        if ($policiestab == ENTERPRISE_NOT_HOOK) {
            $policiestab = '';
        }

        $snmptab = enterprise_hook('massive_snmp_tab');

        if ($snmptab == ENTERPRISE_NOT_HOOK) {
            $snmptab = '';
        }

        $satellitetab = enterprise_hook('massive_satellite_tab');

        if ($satellitetab == ENTERPRISE_NOT_HOOK) {
            $satellitetab = '';
        }

        $servicestab = enterprise_hook('massive_services_tab');

        if ($servicestab == ENTERPRISE_NOT_HOOK) {
            $servicestab = '';
        }

        $onheader = [];
        $onheader['massive_agents'] = $agentstab;
        $onheader['massive_modules'] = $modulestab;
        $onheader['massive_plugins'] = $pluginstab;
        if (check_acl($config['id_user'], 0, 'UM')) {
            $onheader['user_agents'] = $userstab;
        }

        $onheader['massive_alerts'] = $alertstab;
        $onheader['policies'] = $policiestab;
        $onheader['snmp'] = $snmptab;
        $onheader['satellite'] = $satellitetab;
        $onheader['services'] = $servicestab;

        /*
            Hello there! :)

            We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(

            You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.

        */

        ui_print_page_header(
            __('Bulk operations').' &raquo; '.$options[$option],
            'images/gm_massive_operations.png',
            false,
            $help_header,
            true,
            $onheader,
            false,
            'massivemodal'
        );

        // Checks if the PHP configuration is correctly.
        if ((get_cfg_var('max_execution_time') != 0)
            || (get_cfg_var('max_input_time') != -1)
        ) {
            echo '<div id="notify_conf" class="notify">';
            echo __('In order to perform massive operations, PHP needs a correct configuration in timeout parameters. Please, open your PHP configuration file (php.ini) for example: <i>sudo vi /etc/php5/apache2/php.ini;</i><br> And set your timeout parameters to a correct value: <br><i> max_execution_time = 0</i> and <i>max_input_time = -1</i>');
            echo '</div>';
        }

        if ($tab == 'massive_policies' && is_central_policies_on_node()) {
            ui_print_warning_message(__('This node is configured with centralized mode. All policies information is read only. Go to metaconsole to manage it.'));
            return;
        }

        // Catch all submit operations in this view to display Wait banner.
        $submit_action = get_parameter('go');
        $submit_update = get_parameter('updbutton');
        $submit_del = get_parameter('del');
        $submit_template_disabled = get_parameter('id_alert_template_disabled');
        $submit_template_enabled = get_parameter('id_alert_template_enabled');
        $submit_template_not_standby = get_parameter('id_alert_template_not_standby');
        $submit_template_standby = get_parameter('id_alert_template_standby');
        $submit_add = get_parameter('crtbutton');
        // Waiting spinner.
        ui_print_spinner(__('Loading'));
        // Modal for show messages.
        html_print_div(
            [
                'id'      => 'massive_modal',
                'content' => '',
            ]
        );

        // Load common JS files.
        ui_require_javascript_file('massive_operations');

        ?>

<script language="javascript" type="text/javascript">
/* <![CDATA[ */
    $(document).ready (function () {
        $('#button-go').click( function(e) {
            var limitParametersMassive = <?php echo $config['limit_parameters_massive']; ?>;
            var thisForm = e.target.form.id;

            var get_parameters_count = window.location.href.slice(
                window.location.href.indexOf('?') + 1).split('&').length;
            var post_parameters_count = $('#'+thisForm).serializeArray().length;
            var totalCount = get_parameters_count + post_parameters_count;

            var contents = {};

            contents.html = '<?php echo __('No changes have been made because they exceed the maximum allowed (%d). Make fewer changes or contact the administrator.', $config['limit_parameters_massive']); ?>';
            contents.title = '<?php echo __('Massive operations'); ?>';
            contents.question = '<?php echo __('Are you sure?'); ?>';
            contents.ok = '<?php echo __('OK'); ?>';
            contents.cancel = '<?php echo __('Cancel'); ?>';

            var operation = massiveOperationValidation(contents, totalCount, limitParametersMassive, thisForm);

            if (operation == false) {
                return false;
            }
        });
    });
/* ]]> */
</script>

<?php
if (is_central_policies_on_node() && $option == 'delete_agents') {
    ui_print_warning_message(__('This node is configured with centralized mode. To delete an agent go to metaconsole.'));
}

echo '<br />';
echo '<form method="post" id="form_options" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations">';
echo '<table border="0"><tr><td>';
echo __('Action');
echo '</td><td>';
html_print_select(
    $options,
    'option',
    $option,
    'this.form.submit()',
    '',
    0,
    false,
    false,
    false
);
if ($option == 'edit_agents' || $option == 'edit_modules') {
    ui_print_help_tip(__('The blank fields will not be updated'));
}

echo '</td></tr></table>';
echo '</form>';
echo '<br />';

switch ($option) {
    case 'delete_alerts':
        include_once 'godmode/massive/massive_delete_alerts.php';
    break;

    case 'add_alerts':
        include_once 'godmode/massive/massive_add_alerts.php';
    break;

    case 'delete_action_alerts':
        include_once 'godmode/massive/massive_delete_action_alerts.php';
    break;

    case 'add_action_alerts':
        include_once 'godmode/massive/massive_add_action_alerts.php';
    break;

    case 'enable_disable_alerts':
        include_once 'godmode/massive/massive_enable_disable_alerts.php';
    break;

    case 'standby_alerts':
        include_once 'godmode/massive/massive_standby_alerts.php';
    break;

    case 'add_profiles':
        include_once 'godmode/massive/massive_add_profiles.php';
    break;

    case 'delete_profiles':
        include_once 'godmode/massive/massive_delete_profiles.php';
    break;

    case 'delete_agents':
        include_once 'godmode/massive/massive_delete_agents.php';
    break;

    case 'edit_agents':
        include_once 'godmode/massive/massive_edit_agents.php';
    break;

    case 'delete_modules':
        include_once 'godmode/massive/massive_delete_modules.php';
    break;

    case 'edit_modules':
        include_once 'godmode/massive/massive_edit_modules.php';
    break;

    case 'copy_modules':
        include_once 'godmode/massive/massive_copy_modules.php';
    break;

    case 'edit_plugins':
        include_once 'godmode/massive/massive_edit_plugins.php';
    break;

    default:
        if (!enterprise_hook('massive_operations', [$option])) {
            include_once 'godmode/massive/massive_config.php';
        }
    break;
}
