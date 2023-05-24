<?php
/**
 * User edit.
 *
 * @category   Users
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

$headerTitle = __('User detail editor');
// Load the header.
require $config['homedir'].'/operation/users/user_edit_header.php';
use PandoraFMS\Dashboard\Manager;

if (is_metaconsole() === false) {
    include 'include/javascript/timezonepicker/includes/parser.inc';

    // Read in options for map builder.
    $bases = [
        'gray'           => 'Gray',
        'blue-marble'    => 'Blue marble',
        'night-electric' => 'Night Electric',
        'living'         => 'Living Earth',
    ];

    $local_file = 'include/javascript/timezonepicker/images/gray-400.png';

    // Dimensions must always be exact since the imagemap does not scale.
    $array_size = getimagesize($local_file);

    $map_width = $array_size[0];
    $map_height = $array_size[1];

    $timezones = timezone_picker_parse_files(
        $map_width,
        $map_height,
        'include/javascript/timezonepicker/tz_world.txt',
        'include/javascript/timezonepicker/tz_islands.txt'
    );
}

// Update user info.
if (isset($_GET['modified']) && !$view_mode) {
    if (html_print_csrf_error()) {
        return;
    }

    $upd_info = [];
    $upd_info['fullname'] = get_parameter_post('fullname', $user_info['fullname']);
    $upd_info['firstname'] = get_parameter_post('firstname', $user_info['firstname']);
    $upd_info['lastname'] = get_parameter_post('lastname', $user_info['lastname']);
    $password_new = get_parameter_post('password_new', '');
    $password_confirm = get_parameter_post('password_conf', '');
    $current_password = get_parameter_post('current_password', '');
    $upd_info['email'] = get_parameter_post('email', '');
    $upd_info['phone'] = get_parameter_post('phone', '');
    $upd_info['comments'] = get_parameter_post('comments', '');
    $upd_info['allowed_ip_active'] = ((int) get_parameter_switch('allowed_ip_active', -1) === 0);
    $upd_info['allowed_ip_list'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('allowed_ip_list'))));
    $upd_info['comments'] = get_parameter_post('comments', '');
    $upd_info['language'] = get_parameter_post('language', $user_info['language']);
    $upd_info['timezone'] = get_parameter_post('timezone', '');
    $upd_info['id_skin'] = get_parameter('skin', $user_info['id_skin']);
    $upd_info['default_event_filter'] = get_parameter('event_filter', null);
    $upd_info['block_size'] = get_parameter('block_size', $config['block_size']);






    // API Token information.
    $apiTokenRenewed = (bool) get_parameter('renewAPIToken');
    $upd_info['api_token'] = ($apiTokenRenewed === true) ? api_token_generate() : users_get_API_token($config['id_user']);







    $default_block_size = get_parameter('default_block_size', 0);
    if ($default_block_size > 0) {
        $upd_info['block_size'] = 0;
    }

    $upd_info['section'] = get_parameter('section', $user_info['section']);
    $upd_info['data_section'] = get_parameter('data_section', '');
    $dashboard = get_parameter('dashboard', '');
    $visual_console = get_parameter('visual_console', '');






    // Save autorefresh list.
    $autorefresh_list = get_parameter_post('autorefresh_list');
    if (($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')) {
        $upd_info['autorefresh_white_list'] = '';
    } else {
        $upd_info['autorefresh_white_list'] = json_encode($autorefresh_list);
    }






    $upd_info['time_autorefresh'] = (int) get_parameter('time_autorefresh', 0);
    $upd_info['ehorus_user_level_user'] = get_parameter('ehorus_user_level_user');
    $upd_info['ehorus_user_level_pass'] = get_parameter('ehorus_user_level_pass');
    $upd_info['ehorus_user_level_enabled'] = get_parameter('ehorus_user_level_enabled', 0);

    $upd_info['integria_user_level_user'] = get_parameter('integria_user_level_user');
    $upd_info['integria_user_level_pass'] = get_parameter('integria_user_level_pass');

    $is_admin = db_get_value('is_admin', 'tusuario', 'id_user', $id);

    $section = io_safe_output($upd_info['section']);

    if (($section === 'Event list') || ($section === 'Group view')
        || ($section === 'Alert detail') || ($section === 'Tactical view')
        || ($section === 'Default')
    ) {
        $upd_info['data_section'] = '';
    } else if ($section === 'Dashboard') {
        $upd_info['data_section'] = $dashboard;
    } else if ($section === 'Visual console') {
        $upd_info['data_section'] = $visual_console;
    }

    if (empty($password_new) === false) {
        $correct_password = false;

        $user_credentials_check = process_user_login($config['id_user'], $current_password, true);

        if ($user_credentials_check !== false) {
            $correct_password = true;
        }

        if ($config['user_can_update_password'] && $password_confirm == $password_new) {
            if ($correct_password === true) {
                if ((!$is_admin || $config['enable_pass_policy_admin'])
                    && $config['enable_pass_policy']
                ) {
                    $pass_ok = login_validate_pass($password_new, $id, true);
                    if ($pass_ok != 1) {
                        ui_print_error_message($pass_ok);
                    } else {
                        $return = update_user_password($id, $password_new);
                        if ($return) {
                            $return2 = save_pass_history($id, $password_new);
                        }
                    }
                } else {
                    $return = update_user_password($id, $password_new);
                }
            } else {
                if ($current_password === '') {
                    $error_msg = __('Current password of user is required to perform password change');
                } else {
                    $error_msg = __('Current password of user is not correct');
                }
            }
        } else if ($password_new !== 'NON-INIT') {
            $error_msg = __('Passwords didn\'t match or other problem encountered while updating passwords');
        }
    } else if (empty($password_new) === true && empty($password_confirm) === true) {
        $return = true;
    } else if (empty($password_new) === true || empty($password_confirm) === true) {
        $return = false;
    }

    // No need to display "error" here, because when no update is needed
    // (no changes in data) SQL function returns 0 (FALSE), but is not an error,
    // just no change. Previous error message could be confussing to the user.
    if ($return !== false) {
        if (empty($password_new) === false && empty($password_confirm) === false) {
            $success_msg = __('Password successfully updated');
        }

        // If info is valid then proceed with update.
        if ((filter_var($upd_info['email'], FILTER_VALIDATE_EMAIL) || empty($upd_info['email']) === true)
            && (preg_match('/^[0-9- ]+$/D', $upd_info['phone']) || empty($upd_info['phone']) === true)
        ) {
            $return_update_user = update_user($id, $upd_info);

            if ($return_update_user === false) {
                $error_msg = __('Error updating user info');
            } else if ($return_update_user == true) {
                if ($apiTokenRenewed === true) {
                    $success_msg = __('You have generated a new API Token.');
                } else {
                    $success_msg = __('User info successfully updated');
                }
            } else {
                if (empty($password_new) === false && empty($password_confirm) === false) {
                    $success_msg = __('Password successfully updated');
                } else if ($upd_info['id_skin'] !== $user_info['id_skin']) {
                    $success_msg = __('Skin successfully updated');
                } else {
                    $return = false;
                    $error_msg = __('No changes have been made');
                }
            }

            ui_print_result_message(
                $return,
                $success_msg,
                $error_msg,
                $user_auth_error
            );
        } else if (!filter_var($upd_info['email'], FILTER_VALIDATE_EMAIL)) {
            ui_print_error_message(__('Please enter a valid email'));
        } else if (!preg_match('/^[0-9- ]+$/D', $upd_info['phone'])) {
            ui_print_error_message(__('Please enter a valid phone number'));
        }

        $user_info = $upd_info;
    } else {
        if (!$error_msg) {
            $error_msg = __('Error updating passwords: ').($config['auth_error'] ?? '');
        }

        $user_auth_error = $config['auth_error'];

        ui_print_result_message(
            $return,
            $success_msg,
            $error_msg,
            $user_auth_error
        );
    }
}

// Prints action status for current message.
if ((int) $status !== -1) {
    ui_print_result_message(
        $status,
        __('User info successfully updated'),
        __('Error updating user info')
    );
}

if (is_metaconsole() === true) {
    echo '<div class="user_form_title">'.__('Edit my User').'</div>';
}

$is_management_allowed = true;
if (is_metaconsole() === false && is_management_allowed() === false) {
    $is_management_allowed = false;
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/users_setup&tab=user&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All users information is read only. Go to %s to manage it.',
            $url
        )
    );
}


$user_id = '<div class="label_select_simple"><p class="edit_user_labels">'.__('User ID').': </p>';
$user_id .= '<span>'.$id.'</span></div>';






$user_id .= '<div class="label_select_simple"><p class="edit_user_labels">'.__('API Token').'</p>';
if (is_management_allowed()) {
    $user_id .= html_print_anchor(
        [
            'onClick' => sprintf(
                'javascript:renewAPIToken(\'%s\', \'%s\', \'%s\')',
                __('Warning'),
                __('The API token will be renewed. After this action, the last token you were using will not work. Are you sure?'),
                'user_profile_form',
            ),
            'content' => html_print_image(
                'images/icono-refrescar.png',
                true,
                [
                    'class' => 'renew_api_token_image clickable',
                    'title' => __('Renew API Token'),
                ]
            ),
            'class'   => 'renew_api_token_link',
        ],
        true
    );
}


// Check php conf for header auth.
$lines = file('/etc/httpd/conf.d/php.conf');
$http_authorization = false;

foreach ($lines as $l) {
    if (preg_match('/SetEnvIfNoCase \^Authorization\$ \"\(\.\+\)\" HTTP_AUTHORIZATION=\$1/', $l)) {
        $http_authorization = true;
    }
}

$user_id .= html_print_anchor(
    [
        'onClick' => sprintf(
            'javascript:showAPIToken(\'%s\', \'%s\')',
            __('API Token'),
            base64_encode(__('Your API Token is:').'<br><span class="font_12pt bolder">'.users_get_API_token($config['id_user']).'</span><br>'.__('Please, avoid share this string with others.')),
        ),
        'content' => html_print_image(
            'images/see-details@svg.svg',
            true,
            [
                'class' => 'main_menu_icon renew_api_token_image clickable',
                'title' => __('Show API Token'),
            ]
        ),
        'class'   => 'renew_api_token_link',
    ],
    true
);

if ($http_authorization === false) {
    $user_id .= ui_print_help_tip(
        __('Directive HTTP_AUTHORIZATION=$1 is not set. Please, add it to /etc/httpd/conf.d/php.conf'),
        true,
        'images/warn.png',
        false,
        '',
        true
    );
}








$user_id .= '</div>';
$full_name = ' <div class="label_select_simple">'.html_print_input_text_extended(
    'fullname',
    $user_info['fullname'],
    'fullname',
    '',
    20,
    100,
    $view_mode,
    '',
    [
        'class'       => 'input',
        'placeholder' => __('Full (display) name'),
    ],
    true
).'</div>';

// Show "Picture" (in future versions, why not, allow users to upload it's own avatar here.
if (is_user_admin($id) === true) {
    $avatar = html_print_image('images/people_1.png', true, ['class' => 'user_avatar']);
} else {
    $avatar = html_print_image('images/people_2.png', true, ['class' => 'user_avatar']);
}

if (isset($table) === true) {
    if ($view_mode === false) {
        $table->rowspan = [0 => [2 => 3]];
    } else {
        $table->rowspan = [0 => [2 => 2]];
    }
}

$email = '<div class="label_select_simple">'.html_print_input_text_extended('email', $user_info['email'], 'email', '', '25', '100', $view_mode, '', ['class' => 'input', 'placeholder' => __('E-mail')], true).'</div>';

$phone = '<div class="label_select_simple">'.html_print_input_text_extended('phone', $user_info['phone'], 'phone', '', '20', '30', $view_mode, '', ['class' => 'input', 'placeholder' => __('Phone number')], true).'</div>';

if ($view_mode === false) {
    if ($config['user_can_update_password']) {
        $new_pass = '<div class="label_select_simple"><span>'.html_print_input_text_extended('password_new', '', 'password_new', '', '25', '45', $view_mode, '', ['class' => 'input', 'placeholder' => __('New Password')], true, true).'</span></div>';
        $new_pass_confirm = '<div class="label_select_simple"><span>'.html_print_input_text_extended('password_conf', '', 'password_conf', '', '20', '45', $view_mode, '', ['class' => 'input', 'placeholder' => __('Password confirmation')], true, true).'</span></div>';
        $current_pass = '<div class="label_select_simple"><span>'.html_print_input_text_extended('current_password', '', 'current_password', '', '20', '45', $view_mode, '', ['class' => 'input', 'placeholder' => __('Current password')], true, true).'</span></div>';
    } else {
        $new_pass = '<i>'.__('You cannot change your password under the current authentication scheme').'</i>';
        $new_pass_confirm = '';
        $current_pass = '';
    }
}

$size_pagination = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Block size for pagination').'</p>';
if ($user_info['block_size'] == 0) {
    $block_size = $config['global_block_size'];
} else {
    $block_size = $user_info['block_size'];
}

$size_pagination .= html_print_input_text('block_size', $block_size, '', 5, 5, true);
$size_pagination .= html_print_checkbox_switch('default_block_size', 1, $user_info['block_size'] == 0, true);
$size_pagination .= '<span>'.__('Default').' ('.$config['global_block_size'].')</span>'.ui_print_help_tip(__('If checkbox is clicked then block size global configuration is used'), true).'</div>';

$values = [
    -1 => __('Default'),
    1  => __('Yes'),
    0  => __('No'),
];

$language = '<div class="label_select"><p class="edit_user_labels">'.__('Language').': </p>';
$language .= html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true,
    '',
    '',
    '',
    '',
    '',
    10
).'</div>';

$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

$usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
$id_usr = $config['id_user'];

$skin = '';

    $home_screen = '<div class="label_select"><p class="edit_user_labels">'.__('Home screen').ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1 to show agent detail view'), true).'</p>';
    $values = [
        'Default'        => __('Default'),
        'Visual console' => __('Visual console'),
        'Event list'     => __('Event list'),
        'Group view'     => __('Group view'),
        'Tactical view'  => __('Tactical view'),
        'Alert detail'   => __('Alert detail'),
        'Other'          => __('Other'),
        'Dashboard'      => __('Dashboard'),
    ];

    if (is_metaconsole() === true) {
        $home_screen .= html_print_select($values, 'metaconsole_section', io_safe_output($user_info['section']), 'show_data_section();', '', -1, true, false, false).'</div>';
    } else {
        $home_screen .= html_print_select($values, 'section', io_safe_output($user_info['section']), 'show_data_section();', '', -1, true, false, false).'</div>';
    }


    $dashboards = Manager::getDashboards(
        -1,
        -1,
        false,
        false,
        $config['id_user']
    );

    $dashboards_aux = [];
    if ($dashboards === false) {
        $dashboards = ['None' => 'None'];
    } else {
        foreach ($dashboards as $key => $dashboard) {
            $dashboards_aux[$dashboard['id']] = $dashboard['name'];
        }
    }

    $home_screen .= '<div id="show_db" style="display: none; width: 100%;">';
    $home_screen .= html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true, false, false, '');
    $home_screen .= '</div>';

    $layouts = visual_map_get_user_layouts($config['id_user'], true);
    $layouts_aux = [];
    if ($layouts === false) {
        $layouts_aux = ['None' => 'None'];
    } else {
        foreach ($layouts as $layout) {
            $layouts_aux[$layout] = $layout;
        }
    }

    $home_screen .= '<div id="show_vc" style="display: none; width: 100%;">';
    $home_screen .= html_print_select($layouts_aux, 'visual_console', $user_info['data_section'], '', '', '', true);
    $home_screen .= '</div>';
    $home_screen .= html_print_input_text('data_section', $user_info['data_section'], '', 60, 255, true, false);

    // User only can change skins if has more than one group.
    if (function_exists('skins_print_select')) {
        if (count($usr_groups) > 1) {
            $skin = '<div class="label_select"><p class="edit_user_labels">'.__('Theme').': </p>';
            $skin .= skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true).'</div>';
        }
    }


    $timezone = '<div class="label_select"><p class="edit_user_labels">'.__('Timezone').ui_print_help_tip(__('The timezone must be that of the associated server.'), true).'</p>';
    $timezone .= html_print_timezone_select('timezone', $user_info['timezone']).'</div>';

    // Double auth.
    $double_auth_enabled = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $config['id_user']);

    if ((bool) $config['double_auth_enabled'] === true) {
        $double_authentication = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Double authentication').'</p>';
        if (($config['2FA_all_users'] == '' && !$double_auth_enabled)
            || ($config['2FA_all_users'] != '' && !$double_auth_enabled)
            || ($config['double_auth_enabled'] == '' && $double_auth_enabled)
            || check_acl($config['id_user'], 0, 'PM')
        ) {
            $double_authentication .= html_print_checkbox_switch('double_auth', 1, $double_auth_enabled, true);
        }

        // Dialog.
        $double_authentication .= '<div id="dialog-double_auth"class="invisible"><div id="dialog-double_auth-container"></div></div>';
    }

    if ($double_auth_enabled && $config['double_auth_enabled']) {
        $double_authentication .= html_print_button(
            __('Show information'),
            'show_info',
            false,
            'show_double_auth_info();',
            [ 'icon' => 'camera' ],
            '',
            true
        );
    }

    if (isset($double_authentication) === true) {
        $double_authentication .= '</div>';
    }

    if (is_metaconsole() === true && empty($user_info['metaconsole_default_event_filter']) !== true) {
        $user_info['default_event_filter'] = $user_info['metaconsole_default_event_filter'];
    }

    if ((bool) check_acl($config['id_user'], 0, 'ER') === true) {
        $event_filter = '<div class="label_select"><p class="edit_user_labels">'.__('Event filter').'</p>';
        $user_groups = implode(',', array_keys((users_get_groups($config['id_user'], 'AR', true))));
        $event_filter .= html_print_select_from_sql(
            'SELECT id_filter, id_name FROM tevent_filter WHERE id_group_filter IN ('.$user_groups.')',
            'event_filter',
            $user_info['default_event_filter'],
            '',
            __('None'),
            null,
            true
        ).'</div>';
    }

    $autorefresh_list_out = [];
    if (is_metaconsole() === false || is_centralized() === true) {
        $autorefresh_list_out['operation/agentes/estado_agente'] = 'Agent detail';
        $autorefresh_list_out['operation/agentes/alerts_status'] = 'Alert detail';
        $autorefresh_list_out['enterprise/operation/cluster/cluster'] = 'Cluster view';
        $autorefresh_list_out['operation/gis_maps/render_view'] = 'Gis Map';
        $autorefresh_list_out['operation/reporting/graph_viewer'] = 'Graph Viewer';
        $autorefresh_list_out['operation/snmpconsole/snmp_view'] = 'SNMP console';

        if (enterprise_installed()) {
            $autorefresh_list_out['general/sap_view'] = 'SAP view';
        }
    }

    $autorefresh_list_out['operation/agentes/tactical'] = 'Tactical view';
    $autorefresh_list_out['operation/agentes/group_view'] = 'Group view';
    $autorefresh_list_out['operation/agentes/status_monitor'] = 'Monitor detail';
    $autorefresh_list_out['enterprise/operation/services/services'] = 'Services';
    $autorefresh_list_out['operation/dashboard/dashboard'] = 'Dashboard';

    $autorefresh_list_out['operation/agentes/pandora_networkmap'] = 'Network map';
    $autorefresh_list_out['operation/visual_console/render_view'] = 'Visual console';
    $autorefresh_list_out['operation/events/events'] = 'Events';


    if (!isset($autorefresh_list)) {
        $select = db_process_sql("SELECT autorefresh_white_list FROM tusuario WHERE id_user = '".$config['id_user']."'");
        $autorefresh_list = json_decode($select[0]['autorefresh_white_list']);
        if ($autorefresh_list === null) {
            $autorefresh_list[0] = __('None');
        } else {
            $aux = [];
            $count_autorefresh_list = count($autorefresh_list);
            for ($i = 0; $i < $count_autorefresh_list; $i++) {
                $aux[$autorefresh_list[$i]] = $autorefresh_list_out[$autorefresh_list[$i]];
                unset($autorefresh_list_out[$autorefresh_list[$i]]);
                $autorefresh_list[$i] = $aux;
            }

            $autorefresh_list = $aux;
        }
    } else {
        if (is_array($autorefresh_list) === false || empty($autorefresh_list[0]) === true || $autorefresh_list[0] === '0') {
            $autorefresh_list = [];
            $autorefresh_list[0] = __('None');
        } else {
            $aux = [];
            $count_autorefresh_list = count($autorefresh_list);
            for ($i = 0; $i < $count_autorefresh_list; $i++) {
                $aux[$autorefresh_list[$i]] = $autorefresh_list_out[$autorefresh_list[$i]];
                unset($autorefresh_list_out[$autorefresh_list[$i]]);
                $autorefresh_list[$i] = $aux;
            }

            $autorefresh_list = $aux;
        }
    }

    $autorefresh_show = '<p class="edit_user_labels">'._('Autorefresh').ui_print_help_tip(
        __('This will activate autorefresh in selected pages'),
        true
    ).'</p>';
    $select_out = html_print_select(
        $autorefresh_list_out,
        'autorefresh_list_out[]',
        '',
        '',
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'width:100%'
    );
    $arrows = ' ';
    $select_in = html_print_select(
        $autorefresh_list,
        'autorefresh_list[]',
        '',
        '',
        '',
        '',
        true,
        true,
        true,
        '',
        false,
        'width:100%'
    );

    $table_ichanges = '<div class="autorefresh_select">
                        <div class="autorefresh_select_list_out">
                            <p class="autorefresh_select_text">'.__('Full list of pages').': </p>
                            <div>'.$select_out.'</div>
                        </div>
                        <div class="autorefresh_select_arrows" style="display:grid">
                            <a href="javascript:">'.html_print_image(
                                'images/darrowright_green.png',
                                true,
                                [
                                    'id'    => 'right_autorefreshlist',
                                    'alt'   => __('Push selected pages into autorefresh list'),
                                    'title' => __('Push selected pages into autorefresh list'),
                                ]
).'</a>
                            <a href="javascript:">'.html_print_image(
    'images/darrowleft_green.png',
    true,
    [
        'id'    => 'left_autorefreshlist',
        'alt'   => __('Pop selected pages out of autorefresh list'),
        'title' => __('Pop selected pages out of autorefresh list'),
    ]
).'</a>
                        </div>    
                        <div class="autorefresh_select_list">    
                            <p class="autorefresh_select_text">'.__('List of pages with autorefresh').': </p>   
                            <div>'.$select_in.'</div>
                        </div>
                    </div>';

    $autorefresh_show .= $table_ichanges;

    // Time autorefresh.
    $times = get_refresh_time_array();
    $time_autorefresh = '<div class="label_select"><p class="edit_user_labels">'.__('Time autorefresh');
    $time_autorefresh .= ui_print_help_tip(
        __('Interval of autorefresh of the elements, by default they are 30 seconds, needing to enable the autorefresh first'),
        true
    ).'</p>';
    $time_autorefresh .= html_print_select(
        $times,
        'time_autorefresh',
        $user_info['time_autorefresh'],
        '',
        '',
        '',
        true,
        false,
        false
    ).'</div>';













    $comments = '<p class="edit_user_labels">'.__('Comments').': </p>';
    $comments .= html_print_textarea(
        'comments',
        2,
        60,
        $user_info['comments'],
        (($view_mode) ? 'readonly="readonly"' : ''),
        true
    );
    $comments .= html_print_input_hidden('quick_language_change', 1, true);

    $allowedIP = '<p class="edit_user_labels">';
    $allowedIP .= __('Login allowed IP list').'&nbsp;';
    $allowedIP .= ui_print_help_tip(__('Add the source IPs that will allow console access. Each IP must be separated only by comma. * allows all.'), true).'&nbsp;';
    $allowedIP .= html_print_checkbox_switch(
        'allowed_ip_active',
        0,
        $user_info['allowed_ip_active'],
        true
    );
    $allowedIP .= '</p>';
    $allowedIP .= html_print_textarea(
        'allowed_ip_list',
        2,
        65,
        $user_info['allowed_ip_list'],
        ($view_mode ? 'readonly="readonly"' : ''),
        true
    );



    foreach ($timezones as $timezone_name => $tz) {
        if ($timezone_name == 'America/Montreal') {
            $timezone_name = 'America/Toronto';
        } else if ($timezone_name == 'Asia/Chongqing') {
            $timezone_name = 'Asia/Shanghai';
        }

        $area_data_timezone_polys .= '';
        foreach ($tz['polys'] as $coords) {
            $area_data_timezone_polys .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="poly" coords="'.implode(',', $coords).'" />';
        }

        $area_data_timezone_rects .= '';
        foreach ($tz['rects'] as $coords) {
            $area_data_timezone_rects .= '<area data-timezone="'.$timezone_name.'" data-country="'.$tz['country'].'" data-pin="'.implode(',', $tz['pin']).'" data-offset="'.$tz['offset'].'" shape="rect" coords="'.implode(',', $coords).'" />';
        }
    }

    if (is_metaconsole() === true) {
        echo '<form id="user_profile_form" name="user_mod" method="post" action="'.ui_get_full_url('index.php?sec=advanced&sec2=advanced/users_setup').'&amp;tab=user_edit&amp;modified=1&amp;pure='.$config['pure'].'">';
    } else {
        echo '<form id="user_profile_form" name="user_mod" method="post" action="'.ui_get_full_url('index.php?sec=workspace&sec2=operation/users/user_edit').'&amp;modified=1&amp;pure='.$config['pure'].'">';
    }

    html_print_input_hidden('id', $id, false, false, false, 'id');

    echo '<div id="user_form">
            <div class="user_edit_first_row">
                <div class="edit_user_info white_box">
                    <div class="edit_user_info_left">'.$avatar.$user_id.'</div>
                    <div class="edit_user_info_right">'.$full_name.$email.$phone.$new_pass.$new_pass_confirm.$current_pass.'</div>
                </div>  
                <div class="edit_user_autorefresh white_box">'.$autorefresh_show.$time_autorefresh.'</div>
            </div> 
            <div class="user_edit_second_row white_box">
                <div class="edit_user_options">'.$language.$size_pagination.$skin.$home_screen.$event_filter.$double_authentication.'</div>
                <div class="edit_user_timezone">'.$timezone;



    if (is_metaconsole() === false) {
        echo '<div id="timezone-picker">
                        <img id="timezone-image" src="'.$local_file.'" width="'.$map_width.'" height="'.$map_height.'" usemap="#timezone-map" />
                        <img class="timezone-pin pdd_t_4px" src="include/javascript/timezonepicker/images/pin.png" />
                        <map name="timezone-map" id="timezone-map">'.$area_data_timezone_polys.$area_data_timezone_rects.'</map>
                    </div>';
    }

                echo '</div>
            </div>
            <div class="user_edit_third_row white_box">
                <div class="edit_user_comments">'.$comments.'</div>
            </div>

            <div class="user_edit_third_row white_box">
                <div class="edit_user_allowed_ip">'.$allowedIP.'</div>
            </div>
        </div>';

    if ($config['ehorus_enabled'] && $config['ehorus_user_level_conf']) {
        // EHorus user remote login.
        $table_remote = new StdClass();
        $table_remote->data = [];
        $table_remote->width = '100%';
        $table_remote->id = 'ehorus-remote-setup';
        $table_remote->class = 'white_box';
        $table_remote->size['name'] = '30%';
        $table_remote->style['name'] = 'font-weight: bold';

        // Title.
        $row = [];
        $row['control'] = '<p class="edit_user_labels">'.__('eHorus user configuration').': </p>';
        $table_remote->data['ehorus_user_level_conf'] = $row;

        // Enable/disable eHorus for this user.
        $row = [];
        $row['name'] = __('eHorus user acces enabled');
        $row['control'] = html_print_checkbox_switch('ehorus_user_level_enabled', 1, $user_info['ehorus_user_level_enabled'], true);
        $table_remote->data['ehorus_user_level_enabled'] = $row;

        // User.
        $row = [];
        $row['name'] = __('User');
        $row['control'] = html_print_input_text('ehorus_user_level_user', $user_info['ehorus_user_level_user'], '', 30, 100, true);
        $table_remote->data['ehorus_user_level_user'] = $row;

        // Pass.
        $row = [];
        $row['name'] = __('Password');
        $row['control'] = html_print_input_password('ehorus_user_level_pass', io_output_password($user_info['ehorus_user_level_pass']), '', 30, 100, true);
        $table_remote->data['ehorus_user_level_pass'] = $row;

        // Test.
        $ehorus_port = db_get_value('value', 'tconfig', 'token', 'ehorus_port');
        $ehorus_host = db_get_value('value', 'tconfig', 'token', 'ehorus_hostname');

        $row = [];
        $row['name'] = __('Test');
        $row['control'] = html_print_button(
            __('Start'),
            'test-ehorus',
            false,
            'ehorus_connection_test(&quot;'.$ehorus_host.'&quot;,'.$ehorus_port.')',
            [ 'icon' => 'next' ],
            true
        );
        $row['control'] .= '&nbsp;<span id="test-ehorus-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
        $row['control'] .= '&nbsp;<span id="test-ehorus-success" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
        $row['control'] .= '&nbsp;<span id="test-ehorus-failure" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
        $row['control'] .= '<span id="test-ehorus-message" class="invisible"></span>';
        $table_remote->data['ehorus_test'] = $row;

        echo '<div class="ehorus_user_conf user_edit_fourth_row">';
        html_print_table($table_remote);
         echo '</div>';
    }

    if ($config['integria_enabled'] && $config['integria_user_level_conf']) {
        // Integria IMS user remote login.
        $table_remote = new StdClass();
        $table_remote->data = [];
        $table_remote->width = '100%';
        $table_remote->id = 'integria-remote-setup';
        $table_remote->class = 'white_box';
        $table_remote->size['name'] = '30%';
        $table_remote->style['name'] = 'font-weight: bold';

        // Integria IMS user level authentication.
        // Title.
        $row = [];
        $row['control'] = '<p class="edit_user_labels">'.__('Integria user configuration').': </p>';
        $table_remote->data['integria_user_level_conf'] = $row;

        // Integria IMS user.
        $row = [];
        $row['name'] = __('User');
        $row['control'] = html_print_input_text('integria_user_level_user', $user_info['integria_user_level_user'], '', 30, 100, true);
        $table_remote->data['integria_user_level_user'] = $row;

        // Integria IMS pass.
        $row = [];
        $row['name'] = __('Password');
        $row['control'] = html_print_input_password('integria_user_level_pass', io_output_password($user_info['integria_user_level_pass']), '', 30, 100, true);
        $table_remote->data['integria_user_level_pass'] = $row;

        // Test.
        $integria_host = db_get_value('value', 'tconfig', 'token', 'integria_hostname');
        $integria_api_pass = db_get_value('value', 'tconfig', 'token', 'integria_api_pass');

        $row = [];
        $row['name'] = __('Test');
        $row['control'] = html_print_button(
            __('Start'),
            'test-integria',
            false,
            'integria_connection_test(&quot;'.$integria_host.'&quot;,'.$integria_api_pass.')',
            [ 'icon' => 'next' ],
            true
        );
        $row['control'] .= '&nbsp;<span id="test-integria-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
        $row['control'] .= '&nbsp;<span id="test-integria-success" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
        $row['control'] .= '&nbsp;<span id="test-integria-failure" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
        $row['control'] .= '<span id="test-integria-message" class="invisible"></span>';
        $table_remote->data['integria_test'] = $row;

        echo '<div class="integria_user_conf">';
        html_print_table($table_remote);
        echo '</div>';
    }


    if ($is_management_allowed === true) {
        if ((bool) $config['user_can_update_info'] === false) {
            $outputButton = '<i>'.__('You can not change your user info under the current authentication scheme').'</i>';
        } else {
            $outputButton = html_print_submit_button(
                __('Update'),
                'uptbutton',
                $view_mode,
                [ 'icon' => 'update' ],
                true
            );
            $outputButton .= html_print_csrf_hidden(true);
        }

        html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => $outputButton,
            ]
        );
    }

    echo '</form>';

    echo '<div id="edit_user_profiles" class="white_box">';
    if (is_metaconsole() === false) {
        echo '<p class="edit_user_labels">'.__('Profiles/Groups assigned to this user').'</p>';
    }

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    if (is_metaconsole() === true) {
        $table->width = '100%';
        $table->class = 'databox data';
        $table->title = __('Profiles/Groups assigned to this user');
        $table->head_colspan[0] = 0;
        $table->headstyle[] = 'background-color: #82B93C';
        $table->headstyle[] = 'background-color: #82B93C';
        $table->headstyle[] = 'background-color: #82B93C';
    }

    $table->data = [];
    $table->head = [];
    $table->align = [];
    $table->style = [];

    if (is_metaconsole() === false) {
        $table->style[0] = 'font-weight: bold';
        $table->style[1] = 'font-weight: bold';
    }

    $table->head[0] = __('Profile name');
    $table->head[1] = __('Group');
    $table->head[2] = __('Tags');
    $table->align = [];
    $table->align[1] = 'left';

    $table->data = [];

    $result = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $id);
    if ($result === false) {
        $result = [];
    }

    foreach ($result as $profile) {
        $data[0] = '<b>'.profile_get_name($profile['id_perfil']).'</b>';
        if ($config['show_group_name']) {
            $data[1] = ui_print_group_icon(
                $profile['id_grupo'],
                true
            ).'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">&nbsp;</a>';
        } else {
            $data[1] = ui_print_group_icon(
                $profile['id_grupo'],
                true
            ).'<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'">&nbsp;'.ui_print_truncate_text(groups_get_name($profile['id_grupo'], true), GENERIC_SIZE_TEXT).'</a>';
        }

        $tags_ids = explode(',', $profile['tags']);
        $tags = tags_get_tags($tags_ids);

        $data[2] = tags_get_tags_formatted($tags);

        array_push($table->data, $data);
    }

    if (!empty($table->data)) {
        html_print_table($table);
    } else {
        ui_print_info_message(['no_close' => true, 'message' => __('This user doesn\'t have any assigned profile/group.') ]);
    }

    // Close edit_user_profiles.
    echo '</div>';

    if (is_metaconsole() === false) {
        ?>

    <style>
        /* Styles for timezone map */
        #timezone-picker div.timezone-picker {
            margin: 0 auto;
        }
    </style>

    <script language="javascript" type="text/javascript">
        $(document).ready (function () {
            // Set up the picker to update target timezone and country select lists.
            $('#timezone-image').timezonePicker({
                target: '#timezone',
            });

            // Optionally an auto-detect button to trigger JavaScript geolocation.
            $('#timezone-detect').click(function() {
                $('#timezone-image').timezonePicker('detectLocation');
            });
        });
    </script>
        <?php
        // Include OpenLayers and timezone user map library.
        echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/timezonepicker/lib/jquery.timezone-picker.min.js').'"></script>'."\n\t";
        echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/timezonepicker/lib/jquery.maphilight.min.js').'"></script>'."\n\t";
        // Closes no meta condition.
    }

    ?>

<script language="javascript" type="text/javascript">

$(document).ready (function () {

    $("#right_autorefreshlist").click (function () {
        jQuery.each($("select[name='autorefresh_list_out[]'] option:selected"), function (key, value) {
            imodule_name = $(value).html();
            if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                id_imodule = $(value).attr('value');
                $("select[name='autorefresh_list[]']").append($("<option></option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                $("#autorefresh_list_out").find("option[value='" + id_imodule + "']").remove();
                $("#autorefresh_list").find("option[value='']").remove();
                $("#autorefresh_list").find("option[value='0']").remove();
                if($("#autorefresh_list_out option").length == 0) {
                    $("select[name='autorefresh_list_out[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                }
            }
        });
    });

    $("#left_autorefreshlist").click (function () {
        jQuery.each($("select[name='autorefresh_list[]'] option:selected"), function (key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("#autorefresh_list").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list_out").find("option[value='']").remove();
                    $("select[name='autorefresh_list_out[]']").append($("<option><option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                    $("#autorefresh_list_out option").last().remove();
                    if($("#autorefresh_list option").length == 0) {
                        $("select[name='autorefresh_list[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
        });
    });

    check_default_block_size()
    $("#checkbox-default_block_size").change(function() {
        check_default_block_size();
    });
    
    function check_default_block_size() {
        if ($("#checkbox-default_block_size").is(':checked')) {
            $("#text-block_size").attr('disabled', true);
        }
        else {
            $("#text-block_size").removeAttr('disabled');
        }
    }

    $("input#checkbox-double_auth").change(function (e) {
        e.preventDefault();
            if (this.checked) {
                show_double_auth_activation();
            } else {
                show_double_auth_deactivation();
            }
    }); 

    
    show_data_section();
});

function show_data_section () {
    section = $("#section").val();
    
    switch (section) {
        case <?php echo "'".'Dashboard'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "inline-grid");

            break;
        case <?php echo "'".'Visual console'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "");
            $("#show_vc").css("display", "inline-grid");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Event list'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Group view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Tactical view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Alert detail'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Other'."'"; ?>:
            $("#text-data_section").css("display", "");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
        case <?php echo "'".'Default'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            $("#show_vc").css("display", "none");
            $("#show_db").css("display", "none");

            break;
    }
}

function show_double_auth_info () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    $dialogContainer.html($loadingSpinner);

    // Load the info page
    var request = $.ajax({
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        type: 'POST',
        dataType: 'html',
        data: {
            page: 'include/ajax/double_auth.ajax',
            id_user: userID,
            id_user_auth: userID,
            get_double_auth_data_page: 1,
            containerID: $dialogContainer.prop('id')
        },
        complete: function(xhr, textStatus) {
            
        },
        success: function(data, textStatus, xhr) {
            // isNaN = is not a number
            if (isNaN(data)) {
                $dialogContainer.html(data);
            }
            // data is a number, convert it to integer to do the compare
            else if (Number(data) === -1) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
            }
            else {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
        }
    });

    $("div#dialog-double_auth")
        .css('display','block')
        .append($dialogContainer)
        .dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double autentication information'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 400,
            height: 375,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();
            }
        })
        .show();

}

function show_double_auth_activation () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    $dialogContainer.html($loadingSpinner);

    // Load the info page
    var request = $.ajax({
        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
        type: 'POST',
        dataType: 'html',
        data: {
            page: 'include/ajax/double_auth.ajax',
            id_user: userID,
            get_double_auth_info_page: 1,
            containerID: $dialogContainer.prop('id')
        },
        complete: function(xhr, textStatus) {
            
        },
        success: function(data, textStatus, xhr) {
            // isNaN = is not a number
            if (isNaN(data)) {
                $dialogContainer.html(data);
            }
            // data is a number, convert it to integer to do the compare
            else if (Number(data) === -1) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
            }
            else {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
            }
        },
        error: function(xhr, textStatus, errorThrown) {
            $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
        }
    });

    $("div#dialog-double_auth").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double authentication activation'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 500,
            height: 400,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();

                document.location.reload();
            }
        })
        .show();
}

function show_double_auth_deactivation () {
    var userID = "<?php echo $config['id_user']; ?>";

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    var message = "<p><?php echo __('Are you sure?').'<br>'.__('The double authentication will be deactivated'); ?></p>";
    var $button = $("<input type=\"button\" value=\"<?php echo __('Deactivate'); ?>\" />");

    $dialogContainer
        .empty()
        .append(message)
        .append($button);

    var request;

    $button.click(function(e) {
        e.preventDefault();

        $dialogContainer.html($loadingSpinner);

        // Deactivate the double auth
        request = $.ajax({
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            type: 'POST',
            dataType: 'json',
            data: {
                page: 'include/ajax/double_auth.ajax',
                id_user: userID,
                deactivate_double_auth: 1
            },
            complete: function(xhr, textStatus) {
                
            },
            success: function(data, textStatus, xhr) {
                if (data === -1) {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else if (data) {
                    $dialogContainer.html("<?php echo '<b><div class=\"green\">'.__('The double autentication was deactivated successfully').'</div></b>'; ?>");
                }
                else {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error deactivating the double autentication').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error deactivating the double autentication').'</div></b>'; ?>");
            }
        });
    });
    

    $("div#dialog-double_auth").dialog({
            resizable: true,
            draggable: true,
            modal: true,
            title: "<?php echo __('Double authentication activation'); ?>",
            overlay: {
                opacity: 0.5,
                background: "black"
            },
            width: 300,
            height: 150,
            close: function(event, ui) {
                // Abort the ajax request
                if (typeof request != 'undefined')
                    request.abort();
                // Remove the contained html
                $dialogContainer.empty();

                document.location.reload();
            }
        })
        .show();
}

function ehorus_connection_test(host, port) {
        var user = $('input#text-ehorus_user_level_user').val();
        var pass = $('input#password-ehorus_user_level_pass').val();
    
        
        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
        
        var hideLoadingImage = function () {
            $('#test-ehorus-spinner').hide();
        }
        var showLoadingImage = function () {
            $('#test-ehorus-spinner').show();
        }
        var hideSuccessImage = function () {
            $('#test-ehorus-success').hide();
        }
        var showSuccessImage = function () {
            $('#test-ehorus-success').show();
        }
        var hideFailureImage = function () {
            $('#test-ehorus-failure').hide();
        }
        var showFailureImage = function () {
            $('#test-ehorus-failure').show();
        }
        var hideMessage = function () {
            $('#test-ehorus-message').hide();
        }
        var showMessage = function () {
            $('#test-ehorus-message').show();
        }
        var changeTestMessage = function (message) {
            $('#test-ehorus-message').text(message);
        }
        
        hideSuccessImage();
        hideFailureImage();
        hideMessage();
        showLoadingImage();

        $.ajax({
            url: 'https://' + host + ':' + port + '/login',
            type: 'POST',
            dataType: 'json',
            data: {
                user: user,
                pass: pass
            }
        })
        .done(function(data, textStatus, xhr) {
            showSuccessImage();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            showFailureImage();
            
            if (xhr.status === 400) {
                changeTestMessage(badRequestMessage);
            }
            else if (xhr.status === 401 || xhr.status === 403) {
                changeTestMessage(invalidPassMessage);
            }
            else if (xhr.status === 404) {
                changeTestMessage(notFoundMessage);
            }
            else if (errorThrown === 'timeout') {
                changeTestMessage(timeoutMessage);
            }
            else {
                changeTestMessage(errorThrown);
            }
            showMessage();
        })
        .always(function(xhr, textStatus) {
            hideLoadingImage();
        });
    }

function integria_connection_test(api_hostname, api_pass) {
        var user = $('input#text-integria_user_level_user').val();
        var pass = $('input#password-integria_user_level_pass').val();

        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
        
        var hideLoadingImage = function () {
            $('#test-integria-spinner').hide();
        }
        var showLoadingImage = function () {
            $('#test-integria-spinner').show();
        }
        var hideSuccessImage = function () {
            $('#test-integria-success').hide();
        }
        var showSuccessImage = function () {
            $('#test-integria-success').show();
        }
        var hideFailureImage = function () {
            $('#test-integria-failure').hide();
        }
        var showFailureImage = function () {
            $('#test-integria-failure').show();
        }
        var hideMessage = function () {
            $('#test-integria-message').hide();
        }
        var showMessage = function () {
            $('#test-integria-message').show();
        }
        var changeTestMessage = function (message) {
            $('#test-integria-message').text(message);
        }
        
        hideSuccessImage();
        hideFailureImage();
        hideMessage();
        showLoadingImage();

        $.ajax({
            url: "ajax.php",
            type: 'POST',
            dataType: 'json',
            data: {
                page: 'godmode/setup/setup_integria',
                operation: 'check_api_access',
                integria_user: user,
                integria_pass: pass,
                api_hostname: api_hostname,
                api_pass: api_pass,
            }
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
</script>
