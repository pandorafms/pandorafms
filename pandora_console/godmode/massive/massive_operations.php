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

// Begin.
check_login();

global $config;

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive operation section'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_massive_operations.php';

enterprise_include('godmode/massive/massive_operations.php');

$tab = (string) get_parameter('tab', 'massive_agents');
$option = (string) get_parameter('option');

$url = 'index.php?sec=gmassive&sec2=godmode/massive/massive_operations';
if (is_metaconsole() === true) {
    $url = 'index.php?sec=advanced&sec2=advanced/massive_operations&tab=massive_agents&pure=0';
}

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
    $options_users['edit_users'] = __('Edit users in bulk');
    if (is_metaconsole() === false) {
        $options_users = [
            'add_profiles'    => __('Bulk profile add'),
            'delete_profiles' => __('Bulk profile delete'),
        ];
    }
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
if ($policies_options != ENTERPRISE_NOT_HOOK) {
    $policies_options = array_unique($policies_options);
    $options_policies = array_merge($options_policies, $policies_options);
}

$options_snmp = [];
$snmp_options = enterprise_hook('massive_snmp_options');
if ($snmp_options != ENTERPRISE_NOT_HOOK) {
    $snmp_options = array_reverse($snmp_options);
    $options_snmp = array_merge($options_snmp, $snmp_options);
}

$options_satellite = [];
$satellite_options = enterprise_hook('massive_satellite_options');

if ($satellite_options != ENTERPRISE_NOT_HOOK) {
    $options_satellite = array_merge($options_satellite, $satellite_options);
}


if (in_array($option, array_keys($options_alerts)) === true) {
    $tab = 'massive_alerts';
} else if (in_array($option, array_keys($options_agents)) === true) {
    $tab = 'massive_agents';
} else if (in_array($option, array_keys($options_users)) === true) {
    $tab = 'massive_users';
} else if (in_array($option, array_keys($options_modules)) === true) {
    $tab = 'massive_modules';
} else if (in_array($option, array_keys($options_policies)) === true) {
    $tab = 'massive_policies';
} else if (in_array($option, array_keys($options_snmp)) === true) {
    $tab = 'massive_snmp';
} else if (in_array($option, array_keys($options_satellite)) === true) {
    $tab = 'massive_satellite';
} else if (in_array($option, array_keys($options_plugins)) === true) {
    $tab = 'massive_plugins';
}

if ($tab === 'massive_agents' && empty($option) === true) {
    $option = 'edit_agents';
    if (is_metaconsole() === true) {
        $option = 'delete_agents';
    }
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

    default:
        // Default.
    break;
}

// Set the default option of the category.
if ($option == '') {
    $option = array_shift(array_keys($options));
}

$alertstab = [
    'text'   => '<a href="'.$url.'&tab=massive_alerts">'.html_print_image(
        'images/alert@svg.svg',
        true,
        [
            'title' => __('Alerts operations'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
    'active' => $tab == 'massive_alerts',
];

$userstab = [
    'text'   => '<a href="'.$url.'&tab=massive_users">'.html_print_image(
        'images/user.svg',
        true,
        [
            'title' => __('Users operations'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
    'active' => $tab == 'massive_users',
];

$agentstab = [
    'text'   => '<a href="'.$url.'&tab=massive_agents">'.html_print_image(
        'images/agents@svg.svg',
        true,
        [
            'title' => __('Agents operations'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
    'active' => $tab == 'massive_agents',
];

$modulestab = [
    'text'   => '<a href="'.$url.'&tab=massive_modules">'.html_print_image(
        'images/modules@svg.svg',
        true,
        [
            'title' => __('Modules operations'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
    'active' => $tab == 'massive_modules',
];

$pluginstab = [
    'text'   => '<a href="'.$url.'&tab=massive_plugins">'.html_print_image(
        'images/plugins@svg.svg',
        true,
        [
            'title' => __('Plugins operations'),
            'class' => 'invert_filter main_menu_icon',
        ]
    ).'</a>',
    'active' => $tab == 'massive_plugins',
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


// Header.
if (is_metaconsole() === false) {
    ui_print_standard_header(
        __('Bulk operations').' - '.$options[$option],
        'images/gm_massive_operations.png',
        false,
        $help_header,
        false,
        [
            $agentstab,
            $modulestab,
            $pluginstab,
            $userstab,
            $alertstab,
            $policiestab,
            $snmptab,
            $satellitetab,
            $servicestab,
        ],
        [
            [
                'link'  => '',
                'label' => __('Configuration'),
            ],
            [
                'link'  => '',
                'label' => __('Bulk operations'),
            ],
        ]
    );
} else {
    ui_print_standard_header(
        __('Bulk operations').' - '.$options[$option],
        'images/gm_massive_operations.png',
        false,
        $help_header,
        false,
        [
            $userstab,
            $agentstab,
        ],
        [
            [
                'link'  => '',
                'label' => __('Configuration'),
            ],
            [
                'link'  => '',
                'label' => __('Bulk operations'),
            ],
        ]
    );
}


// Checks if the PHP configuration is correctly.
if ((get_cfg_var('max_execution_time') != 0)
    || (get_cfg_var('max_input_time') != -1)
) {
    echo '<div id="notify_conf" class="notify">';
    echo __('In order to perform massive operations, PHP needs a correct configuration in timeout parameters. Please, open your PHP configuration file (php.ini) for example: <i>sudo vi /etc/php5/apache2/php.ini;</i><br> And set your timeout parameters to a correct value: <br><i> max_execution_time = 0</i> and <i>max_input_time = -1</i>');
    echo '</div>';
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
if (is_management_allowed() === false) {
    if (is_metaconsole() === false) {
        $text_warning = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=monitoring&sec2=monitoring/wizard/wizard'
        ).'">'.__('metaconsole').'</a>';
    } else {
        $text_warning = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. To delete agents go to %s',
            $text_warning
        )
    );
}

$tip = '';
if ($option === 'edit_agents' || $option === 'edit_modules') {
    $tip = ui_print_help_tip(__('The blank fields will not be updated'), true);
}

global $SelectAction;

$SelectAction = '<form id="form_necesario" method="post" id="form_options" action="'.$url.'">';
$SelectAction .= '<span class="mrgn_lft_10px mrgn_right_10px">'._('Action').'</span>';
$SelectAction .= html_print_select(
    $options,
    'option',
    $option,
    'this.form.submit()',
    '',
    0,
    true,
    false,
    false
).$tip;

$SelectAction .= '</form>';

switch ($option) {
    case 'delete_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_delete_alerts.php';
    break;

    case 'add_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_add_alerts.php';
    break;

    case 'delete_action_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_delete_action_alerts.php';
    break;

    case 'add_action_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_add_action_alerts.php';
    break;

    case 'enable_disable_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_enable_disable_alerts.php';
    break;

    case 'standby_alerts':
        include_once $config['homedir'].'/godmode/massive/massive_standby_alerts.php';
    break;

    case 'add_profiles':
        include_once $config['homedir'].'/godmode/massive/massive_add_profiles.php';
    break;

    case 'delete_profiles':
        include_once $config['homedir'].'/godmode/massive/massive_delete_profiles.php';
    break;

    case 'delete_agents':
        include_once $config['homedir'].'/godmode/massive/massive_delete_agents.php';
    break;

    case 'edit_agents':
        include_once $config['homedir'].'/godmode/massive/massive_edit_agents.php';
    break;

    case 'delete_modules':
        include_once $config['homedir'].'/godmode/massive/massive_delete_modules.php';
    break;

    case 'edit_modules':
        include_once $config['homedir'].'/godmode/massive/massive_edit_modules.php';
    break;

    case 'copy_modules':
        include_once $config['homedir'].'/godmode/massive/massive_copy_modules.php';
    break;

    case 'edit_plugins':
        include_once $config['homedir'].'/godmode/massive/massive_edit_plugins.php';
    break;

    case 'edit_users':
        include_once $config['homedir'].'/godmode/massive/massive_edit_users.php';
    break;

    default:
        if (!enterprise_hook('massive_operations', [$option])) {
            include_once $config['homedir'].'/godmode/massive/massive_config.php';
        }
    break;
}
