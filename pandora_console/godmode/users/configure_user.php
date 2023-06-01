<?php
/**
 * User creation / update.
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

// Load global vars.
global $config;

check_login();

require_once $config['homedir'].'/vendor/autoload.php';

use PandoraFMS\Dashboard\Manager;

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_visual_map.php';
require_once $config['homedir'].'/include/functions_custom_fields.php';
enterprise_include_once('include/functions_profile.php');

$isFunctionSkins = enterprise_include_once('include/functions_skins.php');

// Add the columns for the enterprise Pandora edition.
$enterprise_include = false;
if (ENTERPRISE_NOT_HOOK !== enterprise_include('include/functions_policies.php')) {
    $enterprise_include = true;
}

if ($enterprise_include === true) {
    enterprise_include_once('meta/include/functions_users_meta.php');
}

$homeScreenValues = [
    HOME_SCREEN_DEFAULT        => __('Default'),
    HOME_SCREEN_VISUAL_CONSOLE => __('Visual console'),
    HOME_SCREEN_EVENT_LIST     => __('Event list'),
    HOME_SCREEN_GROUP_VIEW     => __('Group view'),
    HOME_SCREEN_TACTICAL_VIEW  => __('Tactical view'),
    HOME_SCREEN_ALERT_DETAIL   => __('Alert detail'),
    HOME_SCREEN_EXTERNAL_LINK  => __('External link'),
    HOME_SCREEN_OTHER          => __('Other'),
    HOME_SCREEN_DASHBOARD      => __('Dashboard'),
];

// This defines the working user. Beware with this, old code get confusses
// and operates with current logged user (dangerous).
$id = get_parameter('id', get_parameter('id_user', ''));

if (empty($id) === true) {
    $id = $config['id_user'];
}

// Check if we are the same user for edit or we have a proper profile for edit users.
if ($id !== $config['id_user']) {
    if ((bool) check_acl($config['id_user'], 0, 'UM') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access User Management'
        );
        include 'general/noaccess.php';

        return;
    }
}

// ID given as parameter.
$pure = get_parameter('pure', 0);
$user_info = get_user_info($id);

if (is_metaconsole() === true) {
    $user_info['section'] = $user_info['metaconsole_section'];
    $user_info['data_section'] = $user_info['metaconsole_data_section'];
    $user_info['default_event_filter'] = $user_info['metaconsole_default_event_filter'];
}

$is_err = false;

if (is_ajax() === true) {
    $delete_profile = (bool) get_parameter('delete_profile');
    $get_user_profile = (bool) get_parameter('get_user_profile');

    if ($get_user_profile === true) {
        $profile_id = (int) get_parameter('profile_id');
        $group_id = (int) get_parameter('group_id', -1);
        $user_id = (string) get_parameter('user_id', '');
        $no_hierarchy = (int) get_parameter('no_hierarchy', -1);
        $assigned_by = (string) get_parameter('assigned_by', '');
        $id_policy = (int) get_parameter('id_policy', -1);
        $tags = (string) get_parameter('id_policy', '');

        $filter = [];

        if ($group_id > -1) {
            $filter['id_perfil'] = $profile_id;
        }

        if ($group_id > -1) {
            $filter['id_grupo'] = $group_id;
        }

        if ($user_id !== '') {
            $filter['id_usuario'] = $user_id;
        }

        if ($no_hierarchy > -1) {
            $filter['no_hierarchy'] = $no_hierarchy;
        }

        if ($assigned_by !== '') {
            $filter['assigned_by'] = $assigned_by;
        }

        if ($id_policy > -1) {
            $filter['id_policy'] = $id_policy;
        }

        if ($tags !== '') {
            $filter['tags'] = $tags;
        }

        $profile = db_get_all_rows_filter(
            'tusuario_perfil',
            $filter
        );

        if ($profile !== false && count($profile) > 0) {
            echo json_encode($profile);

            return;
        } else {
            echo json_encode('');
        }

        return;
    }
}

$tab = get_parameter('tab', 'user');

// Save autorefresh list.
$autorefresh_list = (array) get_parameter_post('autorefresh_list');
$autorefresh_white_list = (($autorefresh_list[0] === '') || ($autorefresh_list[0] === '0')) ? '' : json_encode($autorefresh_list);

// Header.
if (is_metaconsole() === true) {
    user_meta_print_header();
    $sec = 'advanced';
} else {
    if ((bool) check_acl($config['id_user'], 0, 'UM') === false) {
        $buttons = [];
    } else {
        $buttons = [
            'user'    => [
                'active' => false,
                'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image(
                    'images/user.svg',
                    true,
                    [
                        'title' => __('User management'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
            ],
            'profile' => [
                'active' => false,
                'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">'.html_print_image(
                    'images/suitcase@svg.svg',
                    true,
                    [
                        'title' => __('Profile management'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                ).'</a>',
            ],
        ];

        $buttons[$tab]['active'] = true;
    }

    ui_print_standard_header(
        (empty($id) === false) ? sprintf('%s [ %s ]', __('Update User'), $id) : __('Create User'),
        'images/gm_users.png',
        false,
        '',
        true,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Profiles'),
            ],
            [
                'link'  => ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/user_list'),
                'label' => __('Manage users'),
            ],
            [
                'link'  => '',
                'label' => __('User Detail Editor'),
            ],
        ]
    );

    $sec = 'gusuarios';
}


if ((bool) $config['user_can_update_info'] === true) {
    $view_mode = false;
} else {
    $view_mode = true;
}

$delete_profile = (bool) get_parameter('delete_profile');
$new_user = (bool) get_parameter('new_user');
$create_user = (bool) get_parameter('create_user');
$add_profile = (bool) get_parameter('add_profile');
$update_user = (bool) get_parameter('update_user');
$renewAPIToken = (bool) get_parameter('renewAPIToken');
$status = get_parameter('status', -1);
$json_profile = get_parameter('json_profile', '');

// Reset status var if current action is not update_user.
if ($new_user === true || $create_user === true || $add_profile === true
    || $delete_profile === true || $update_user === true
) {
    $status = -1;
}

if ($new_user === true && (bool) $config['admin_can_add_user'] === true) {
    $user_info = [];
    $id = '';
    $user_info['fullname'] = '';
    $user_info['firstname'] = '';
    $user_info['lastname'] = '';
    $user_info['email'] = '';
    $user_info['phone'] = '';
    $user_info['comments'] = '';
    $user_info['is_admin'] = 0;
    $user_info['language'] = 'default';
    $user_info['timezone'] = '';
    $user_info['not_login'] = false;
    $user_info['local_user'] = false;
    $user_info['strict_acl'] = false;
    $user_info['session_time'] = 0;
    $user_info['middlename'] = 0;

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $user_info['id_skin'] = '';
    }

    $user_info['section'] = '';
    $user_info['data_section'] = '';
    // This attributes are inherited from global configuration.
    $user_info['block_size'] = $config['block_size'];

    if (enterprise_installed() === true && is_metaconsole() === true) {
        $user_info['metaconsole_agents_manager'] = 0;
        $user_info['metaconsole_access_node'] = 0;
    }

    if (isset($config['ehorus_user_level_conf']) === true && (bool) $config['ehorus_user_level_conf'] === true) {
        $user_info['ehorus_user_level_user'] = '';
        $user_info['ehorus_user_level_pass'] = '';
        $user_info['ehorus_user_level_enabled'] = true;
    }
}

if ($create_user === true) {
    if ((bool) $config['admin_can_add_user'] === false) {
        ui_print_error_message(
            __('The current authentication scheme doesn\'t support creating users on %s', get_product_name())
        );
        return;
    }

    if (html_print_csrf_error() === true) {
        return;
    }

    $user_is_admin = (get_parameter('is_admin', 0) === 0) ? 0 : 1;

    if (users_is_admin() === false && $user_is_admin !== 0) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to create with administrator privileges to user by non administrator user '.$config['id_user']
        );

        include 'general/noaccess.php';
        exit;
    }

    $values = [];
    $values['id_user'] = (string) get_parameter('id_user');
    $values['fullname'] = (string) get_parameter('fullname');
    $values['firstname'] = (string) get_parameter('firstname');
    $values['lastname'] = (string) get_parameter('lastname');
    $password_new = (string) get_parameter('password_new', '');
    $password_confirm = (string) get_parameter('password_confirm', '');
    $values['email'] = (string) get_parameter('email');
    $values['phone'] = (string) get_parameter('phone');
    $values['comments'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('comments'))));
    $values['allowed_ip_active'] = ((int) get_parameter_switch('allowed_ip_active', -1) === 0);
    $values['allowed_ip_list'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('allowed_ip_list'))));
    $values['is_admin'] = $user_is_admin;
    $values['language'] = get_parameter('language', 'default');
    $values['timezone'] = (string) get_parameter('timezone');
    $values['default_event_filter'] = (int) get_parameter('default_event_filter');
    $values['default_custom_view'] = (int) get_parameter('default_custom_view');
    $values['time_autorefresh'] = (int) get_parameter('time_autorefresh', 0);
    $values['show_tips_startup'] = (int) get_parameter_switch('show_tips_startup');
    $dashboard = get_parameter('dashboard', '');
    $visual_console = get_parameter('visual_console', '');

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $values['id_skin'] = (int) get_parameter('skin', 0);
    }

    $values['block_size'] = (int) get_parameter('block_size', $config['block_size']);

    $values['section'] = get_parameter('section');
    if (($values['section'] === HOME_SCREEN_EVENT_LIST) || ($values['section'] === HOME_SCREEN_GROUP_VIEW) || ($values['section'] === HOME_SCREEN_ALERT_DETAIL) || ($values['section'] === HOME_SCREEN_TACTICAL_VIEW) || ($values['section'] === HOME_SCREEN_DEFAULT)) {
        $values['data_section'] = '';
    } else if ($values['section'] === HOME_SCREEN_DASHBOARD) {
        $values['data_section'] = $dashboard;
    } else if (io_safe_output($values['section']) === HOME_SCREEN_VISUAL_CONSOLE) {
        $values['data_section'] = $visual_console;
    } else if ($values['section'] === HOME_SCREEN_OTHER || io_safe_output($values['section']) === HOME_SCREEN_EXTERNAL_LINK) {
        $values['data_section'] = get_parameter('data_section');
    }

    $values['section'] = $homeScreenValues[$values['section']];

    if (enterprise_installed() === true) {
        $values['force_change_pass'] = 1;
        $values['last_pass_change'] = date('Y/m/d H:i:s', get_system_time());
        if (is_metaconsole() === true) {
            $values['metaconsole_access'] = get_parameter('metaconsole_access', 'basic');
            $values['metaconsole_agents_manager'] = ($user_is_admin == 1 ? 1 : get_parameter('metaconsole_agents_manager', '0'));
            $values['metaconsole_access_node'] = ($user_is_admin == 1 ? 1 : get_parameter('metaconsole_access_node', '0'));
        }
    }

    $values['not_login'] = (bool) get_parameter('not_login', false);
    $values['local_user'] = (bool) get_parameter('local_user', false);
    $values['middlename'] = get_parameter('middlename', 0);
    $values['strict_acl'] = (bool) get_parameter('strict_acl', false);
    $values['session_time'] = (int) get_parameter('session_time', 0);

    // eHorus user level conf.
    if ((bool) $config['ehorus_user_level_conf'] === true) {
        $values['ehorus_user_level_enabled'] = (bool) get_parameter('ehorus_user_level_enabled', false);
        if ($values['ehorus_user_level_enabled'] === true) {
            $values['ehorus_user_level_user'] = (string) get_parameter('ehorus_user_level_user');
            $values['ehorus_user_level_pass'] = (string) get_parameter('ehorus_user_level_pass');
        } else {
            $values['ehorus_user_level_user'] = null;
            $values['ehorus_user_level_pass'] = null;
        }
    }

    // Generate new API token.
    $values['api_token'] = api_token_generate();
    // Validate the user ID if it already exists.
    $user_exists = get_user_info($id);

    if (empty($id) === true) {
        ui_print_error_message(__('User ID cannot be empty'));
        $is_err = true;
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if (isset($user_exists['id_user'])) {
        $is_err = true;
        ui_print_error_message(__('User ID already exists'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if (preg_match('/^\s+|\s+$/', io_safe_output($id))) {
        ui_print_error_message(__('Invalid user ID: leading or trailing blank spaces not allowed'));
        $is_err = true;
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if (empty($password_new) === true) {
        $is_err = true;
        ui_print_error_message(__('Passwords cannot be empty'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if ($password_new != $password_confirm) {
        $is_err = true;
        ui_print_error_message(__('Passwords didn\'t match'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if (enterprise_hook('excludedPassword', [$password_new]) === true) {
        $is_err = true;
        ui_print_error_message(__('The password provided is not valid. Please set another one.'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else {
        if ((!is_user_admin($config['id_user']) || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
            $pass_ok = login_validate_pass($password_new, $id, true);
            if ($pass_ok != 1) {
                ui_print_error_message($pass_ok);
            } else {
                $result = create_user($id, $password_new, $values);
            }
        } else {
            $result = create_user($id, $password_new, $values);
        }

        $info = '{"Id_user":"'.$values['id_user'].'","FullName":"'.$values['fullname'].'","Firstname":"'.$values['firstname'].'","Lastname":"'.$values['lastname'].'","Email":"'.$values['email'].'","Phone":"'.$values['phone'].'","Comments":"'.$values['comments'].'","Is_admin":"'.$values['is_admin'].'","Language":"'.$values['language'].'","Timezone":"'.$values['timezone'].'","Block size":"'.$values['block_size'].'"';
        if ($values['allowed_ip_active'] === true) {
            $info .= ',"IPS Allowed":"'.$values['allowed_ip_list'].'"';
        }

        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            $info .= ',"Skin":"'.$values['id_skin'].'"}';
        } else {
            $info .= '}';
        }

        $can_create = false;



        if ($result) {
            $res = save_pass_history($id, $password_new);
        } else {
            $is_err = true;
            $user_info = $values;
            $password_new = '';
            $password_confirm = '';
            $new_user = true;
        }

        db_pandora_audit(
            AUDIT_LOG_USER_MANAGEMENT,
            'Created user '.io_safe_output($id),
            false,
            false,
            $info
        );

        ui_print_result_message(
            $result,
            __('Successfully created'),
            __('Could not be created')
        );

        $password_new = '';
        $password_confirm = '';

        if ($result) {
            if ($values['strict_acl']) {
                if ($values['is_admin']) {
                    ui_print_info_message(__('Strict ACL is not recommended for admin users because performance could be affected.'));
                }
            }

            $user_info = get_user_info($id);
            $new_user = false;

            if (empty($json_profile) === false) {
                $json_profile = json_decode(io_safe_output($json_profile), true);
                foreach ($json_profile as $key => $profile) {
                    if (is_array($profile) === false) {
                        $profile = json_decode($profile, true);
                    }

                    if (!empty($profile)) {
                        $group2 = $profile['group'];
                        $profile2 = $profile['profile'];
                        $tags = $profile['tags'];
                        foreach ($tags as $k => $tag) {
                            if (empty($tag)) {
                                unset($tags[$k]);
                            }
                        }

                        $tags = implode(',', $tags);
                        $no_hierarchy = $profile['hierarchy'];

                        db_pandora_audit(
                            AUDIT_LOG_USER_MANAGEMENT,
                            'Added profile for user '.io_safe_output($id2),
                            false,
                            false,
                            'Profile: '.$profile2.' Group: '.$group2.' Tags: '.$tags
                        );

                        $result_profile = profile_create_user_profile($id, $profile2, $group2, false, $tags, $no_hierarchy);

                        if ($result_profile === false) {
                            $is_err = true;
                            $user_info = $values;
                            $password_new = '';
                            $password_confirm = '';
                            $new_user = true;
                        } else {
                            $pm = db_get_value_filter('pandora_management', 'tperfil', ['id_perfil' => $profile2]);

                            if ((int) $pm === 1) {
                                $user_source = db_get_value_filter(
                                    'id_source',
                                    'tnotification_source_user',
                                    [
                                        'id_source' => $notification['id'],
                                        'id_user'   => $id,
                                    ]
                                );
                                if ($user_source === false) {
                                    $notificationSources = db_get_all_rows_filter('tnotification_source', [], 'id');
                                    foreach ($notificationSources as $notification) {
                                        if ((int) $notification['id'] === 1 || (int) $notification['id'] === 5) {
                                            $notification_user = db_get_value_filter(
                                                'id_source',
                                                'tnotification_source_user',
                                                [
                                                    'id_source' => $notification['id'],
                                                    'id_user'   => $id,
                                                ]
                                            );
                                            if ($notification_user === false) {
                                                @db_process_sql_insert(
                                                    'tnotification_source_user',
                                                    [
                                                        'id_source' => $notification['id'],
                                                        'id_user'   => $id,
                                                    ]
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        ui_print_result_message(
                            $result_profile,
                            __('Profile added successfully'),
                            __('Profile cannot be added')
                        );
                    }
                }
            }
        } else {
            $user_info = $values;
            $new_user = true;
        }
    }
}

if ($update_user) {
    if (html_print_csrf_error() === true) {
        return;
    }

    $values = [];
    $values['fullname'] = (string) get_parameter('fullname');
    $values['firstname'] = (string) get_parameter('firstname');
    $values['lastname'] = (string) get_parameter('lastname');
    $values['email'] = (string) get_parameter('email');
    $values['phone'] = (string) get_parameter('phone');
    $values['comments'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('comments'))));
    $values['allowed_ip_active'] = ((int) get_parameter('allowed_ip_active', -1) === 0);
    $values['allowed_ip_list'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('allowed_ip_list'))));
    $values['is_admin'] = (get_parameter('is_admin', 0) === 0) ? 0 : 1;
    $values['language'] = (string) get_parameter('language');
    $values['timezone'] = (string) get_parameter('timezone');
    $values['default_event_filter'] = (int) get_parameter('default_event_filter');
    $values['default_custom_view'] = (int) get_parameter('default_custom_view');
    $values['show_tips_startup'] = (int) get_parameter_switch('show_tips_startup');
    $values['time_autorefresh'] = (int) get_parameter('time_autorefresh');
    // API Token information.
    $apiTokenRenewed = (bool) get_parameter('renewAPIToken');
    $values['api_token'] = ($apiTokenRenewed === true) ? api_token_generate() : users_get_API_token($id);

    if (users_is_admin() === false && (bool) $values['is_admin'] !== false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to add administrator privileges to user by non administrator user '.$config['id_user']
        );

        include 'general/noaccess.php';
        exit;
    }

    // Ehorus user level conf.
    $values['ehorus_user_level_enabled'] = (bool) get_parameter('ehorus_user_level_enabled', false);
    $values['ehorus_user_level_user'] = (string) get_parameter('ehorus_user_level_user');
    $values['ehorus_user_level_pass'] = (string) get_parameter('ehorus_user_level_pass');

    $values['middlename'] = get_parameter('middlename', 0);

    $dashboard = get_parameter('dashboard', '');
    $visual_console = get_parameter('visual_console', '');

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $values['id_skin'] = get_parameter('skin', 0);
    }

    $values['block_size'] = get_parameter('block_size', $config['block_size']);

    $values['section'] = get_parameter('section');
    if (($values['section'] === HOME_SCREEN_EVENT_LIST) || ($values['section'] === HOME_SCREEN_GROUP_VIEW) || ($values['section'] === HOME_SCREEN_ALERT_DETAIL) || ($values['section'] === HOME_SCREEN_TACTICAL_VIEW) || ($values['section'] === HOME_SCREEN_DEFAULT)) {
        $values['data_section'] = '';
    } else if ($values['section'] === HOME_SCREEN_DASHBOARD) {
        $values['data_section'] = $dashboard;
    } else if (io_safe_output($values['section']) === HOME_SCREEN_VISUAL_CONSOLE) {
        $values['data_section'] = $visual_console;
    } else if ($values['section'] === HOME_SCREEN_OTHER || io_safe_output($values['section']) === HOME_SCREEN_EXTERNAL_LINK) {
        $values['data_section'] = get_parameter('data_section');
    }

    $values['section'] = $homeScreenValues[$values['section']];

    if (enterprise_installed() === true && is_metaconsole() === true) {
        if (users_is_admin() === true) {
            $values['metaconsole_access'] = get_parameter('metaconsole_access');
            $values['metaconsole_agents_manager'] = get_parameter('metaconsole_agents_manager', '0');
            $values['metaconsole_access_node'] = get_parameter('metaconsole_access_node', '0');
        } else {
            $values['metaconsole_access'] = $user_info['metaconsole_access'];
            $values['metaconsole_agents_manager'] = $user_info['metaconsole_agents_manager'];
            $values['metaconsole_access_node'] = db_get_value('metaconsole_access_node', 'tusuario', 'id_user', $id);
        }
    }

    $values['not_login'] = (bool) get_parameter('not_login', false);
    $values['local_user'] = (bool) get_parameter('local_user', false);
    $values['strict_acl'] = (bool) get_parameter('strict_acl', false);
    $values['session_time'] = (int) get_parameter('session_time', 0);
    // Previously defined.
    $values['autorefresh_white_list'] = $autorefresh_white_list;

    $res1 = update_user($id, $values);

    if ($config['user_can_update_password']) {
        $password_new = (string) get_parameter('password_new', '');
        $password_confirm = (string) get_parameter('password_confirm', '');
        $own_password_confirm = (string) get_parameter('own_password_confirm', '');

        if ($password_new != '') {
            $correct_password = false;

            $user_credentials_check = process_user_login($config['id_user'], $own_password_confirm, true);

            if ($user_credentials_check !== false) {
                $correct_password = true;
            }

            if ((string) $password_confirm === (string) $password_new) {
                if ($correct_password === true || is_user_admin($config['id_user'])) {
                    if ((is_user_admin($config['id_user']) === false || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
                        $pass_ok = login_validate_pass($password_new, $id, true);
                        if ($pass_ok != 1) {
                            ui_print_error_message($pass_ok);
                        } else {
                            $res2 = update_user_password($id, $password_new);
                            if ($res2) {
                                db_process_sql_insert(
                                    'tsesion',
                                    [
                                        'id_sesion'   => '',
                                        'id_usuario'  => $id,
                                        'ip_origen'   => $_SERVER['REMOTE_ADDR'],
                                        'accion'      => 'Password&#x20;change',
                                        'descripcion' => 'Access password updated',
                                        'fecha'       => date('Y-m-d H:i:s'),
                                        'utimestamp'  => time(),
                                    ]
                                );
                                $res3 = save_pass_history($id, $password_new);

                                // Generate new API token.
                                $newToken = api_token_generate();
                                $res4 = update_user($id, ['api_token' => $newToken]);
                            }

                            ui_print_result_message(
                                $res1 || $res2,
                                __('User info successfully updated'),
                                __('Error updating user info (no change?)')
                            );
                        }
                    } else {
                        $res2 = update_user_password($id, $password_new);
                        if ($res2) {
                            $res3 = save_pass_history($id, $password_new);
                            db_process_sql_insert(
                                'tsesion',
                                [
                                    'id_sesion'   => '',
                                    'id_usuario'  => $id,
                                    'ip_origen'   => $_SERVER['REMOTE_ADDR'],
                                    'accion'      => 'Password&#x20;change',
                                    'descripcion' => 'Access password updated',
                                    'fecha'       => date('Y-m-d H:i:s'),
                                    'utimestamp'  => time(),
                                ]
                            );

                            // Generate new API token.
                            $newToken = api_token_generate();
                            $res4 = update_user($id, ['api_token' => $newToken]);
                        }

                        ui_print_result_message(
                            $res1 || $res2,
                            __('User info successfully updated'),
                            __('Error updating user info (no change?)')
                        );
                    }
                } else {
                    if ($own_password_confirm === '') {
                        ui_print_error_message(__('Password of the active user is required to perform password change'));
                    } else {
                        ui_print_error_message(__('Password of active user is not correct'));
                    }
                }
            } else {
                db_process_sql_insert(
                    'tsesion',
                    [
                        'id_sesion'   => '',
                        'id_usuario'  => $id,
                        'ip_origen'   => $_SERVER['REMOTE_ADDR'],
                        'accion'      => 'Password&#x20;change',
                        'descripcion' => 'Access password update failed',
                        'fecha'       => date('Y-m-d H:i:s'),
                        'utimestamp'  => time(),
                    ]
                );
                ui_print_error_message(__('Passwords does not match'));
            }
        } else {
            $has_skin = false;
            $has_wizard = false;

            $info = '{"id_user":"'.$id.'",
				"FullName":"'.$values['fullname'].'",
				"Firstname":"'.$values['firstname'].'",
				"Lastname":"'.$values['lastname'].'",
				"Email":"'.$values['email'].'",
				"Phone":"'.$values['phone'].'",
				"Comments":"'.$values['comments'].'",
				"Is_admin":"'.$values['is_admin'].'",
				"Language":"'.$values['language'].'",
				"Timezone":"'.$values['timezone'].'",
				"Block size":"'.$values['block_size'].'",
				"Section":"'.$values['section'].'"';

            if ($values['allowed_ip_active'] === true) {
                $info .= ',"IPS Allowed":"'.$values['allowed_ip_list'].'"';
            }

            if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
                $info .= ',"Skin":"'.$values['id_skin'].'"';
                $has_skin = true;
            }

            if (enterprise_installed() === true && is_metaconsole() === true) {
                $info .= ',"Wizard access":"'.$values['metaconsole_access'].'"}';
                $has_wizard = true;
            } else if ($has_skin === true) {
                $info .= '}';
            }

            if ($has_skin === false && $has_wizard === false) {
                $info .= '}';
            }


            db_pandora_audit(
                AUDIT_LOG_USER_MANAGEMENT,
                'Updated user '.io_safe_output($id),
                false,
                false,
                $info
            );

            ui_print_result_message(
                $res1,
                ($apiTokenRenewed === true) ? __('You have generated a new API Token.') : __('User info successfully updated'),
                __('Error updating user info (no change?)')
            );
        }
    } else {
        ui_print_result_message(
            $res1,
            __('User info successfully updated'),
            __('Error updating user info (no change?)')
        );
    }


    if ((bool) $values['strict_acl'] === true) {
        $count_groups = 0;
        $count_tags = 0;

        $profiles = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $id);
        if ($profiles === false) {
            $profiles = [];
        }

        foreach ($profiles as $profile) {
            $count_groups++;
            $arr_tags = explode(',', $profile['tags']);
            $count_tags = ($count_tags + count($arr_tags));
        }

        if (($count_groups > 3) && ($count_tags > 10)) {
            ui_print_info_message(__('Strict ACL is not recommended for this user. Performance could be affected.'));
        }
    }

    $user_info = $values;
}

if ($delete_profile) {
    $id2 = (string) get_parameter('id_user');
    $id_up = (int) get_parameter('id_user_profile');
    $perfilUser = db_get_row('tusuario_perfil', 'id_up', $id_up);
    $id_perfil = $perfilUser['id_perfil'];
    $perfil = db_get_row('tperfil', 'id_perfil', $id_perfil);

    db_pandora_audit(
        AUDIT_LOG_USER_MANAGEMENT,
        'Deleted profile for user '.io_safe_output($id2),
        false,
        false,
        'The profile with id '.$id_perfil.' in the group '.$perfilUser['id_grupo']
    );

    $return = profile_delete_user_profile($id2, $id_up);
    ui_print_result_message(
        $return,
        __('Successfully deleted'),
        __('Could not be deleted')
    );


    $has_profile = db_get_row('tusuario_perfil', 'id_usuario', $id2);
    $user_is_global_admin = users_is_admin($id2);

    if ($has_profile === false && $user_is_global_admin === false) {
        $result = delete_user($id2);

        if ($result === true) {
            db_pandora_audit(
                AUDIT_LOG_USER_MANAGEMENT,
                __('Deleted user %s', io_safe_output($id_user))
            );
        }

        ui_print_result_message(
            $result,
            __('Successfully deleted'),
            __('There was a problem deleting the user')
        );

        // Delete the user in all the consoles.
        if (is_metaconsole() === true) {
            $servers = metaconsole_get_servers();
            foreach ($servers as $server) {
                // Connect to the remote console.
                metaconsole_connect($server);

                // Delete the user.
                $result = delete_user($id_user);
                if ($result === true) {
                    db_pandora_audit(
                        AUDIT_LOG_USER_MANAGEMENT,
                        __('Deleted user %s from metaconsole', io_safe_output($id_user))
                    );
                }

                // Restore the db connection.
                metaconsole_restore_db();

                // Log to the metaconsole too.
                if ($result === true) {
                    db_pandora_audit(
                        AUDIT_LOG_USER_MANAGEMENT,
                        __(
                            'Deleted user %s from %s',
                            io_safe_input($id_user),
                            io_safe_input($server['server_name'])
                        )
                    );
                }

                ui_print_result_message(
                    $result,
                    __('Successfully deleted from %s', io_safe_input($server['server_name'])),
                    __('There was a problem deleting the user from %s', io_safe_input($server['server_name']))
                );
            }
        }
    }
}

if ((int) $status !== -1) {
    ui_print_result_message(
        $status,
        __('User info successfully updated'),
        __('Error updating user info (no change?)')
    );
}

if ($add_profile && empty($json_profile)) {
    $id2 = (string) get_parameter('id', get_parameter('id_user'));
    $group2 = (int) get_parameter('assign_group');
    $profile2 = (int) get_parameter('assign_profile');
    $tags = (array) get_parameter('assign_tags');
    $no_hierarchy = (int) get_parameter('no_hierarchy', 0);

    foreach ($tags as $k => $tag) {
        if (empty($tag) === true) {
            unset($tags[$k]);
        }
    }

    $tags = implode(',', $tags);

    db_pandora_audit(
        AUDIT_LOG_USER_MANAGEMENT,
        'Added profile for user '.io_safe_output($id2),
        false,
        false,
        'Profile: '.$profile2.' Group: '.$group2.' Tags: '.$tags
    );

    $return = profile_create_user_profile($id2, $profile2, $group2, false, $tags, $no_hierarchy);
    if ($return === false) {
        $is_err = true;
    } else {
        $pm = db_get_value_filter('pandora_management', 'tperfil', ['id_perfil' => $profile2]);

        if ((int) $pm === 1) {
            $user_source = db_get_value_filter(
                'id_source',
                'tnotification_source_user',
                [
                    'id_source' => $notification['id'],
                    'id_user'   => $id,
                ]
            );
            if ($user_source === false) {
                $notificationSources = db_get_all_rows_filter('tnotification_source', [], 'id');
                foreach ($notificationSources as $notification) {
                    if ((int) $notification['id'] === 1 || (int) $notification['id'] === 5) {
                        $notification_user = db_get_value_filter(
                            'id_source',
                            'tnotification_source_user',
                            [
                                'id_source' => $notification['id'],
                                'id_user'   => $id,
                            ]
                        );
                        if ($notification_user === false) {
                            @db_process_sql_insert(
                                'tnotification_source_user',
                                [
                                    'id_source' => $notification['id'],
                                    'id_user'   => $id,
                                ]
                            );
                        }
                    }
                }
            }
        }
    }

    ui_print_result_message(
        $return,
        __('Profile added successfully'),
        __('Profile cannot be added')
    );
}

if (isset($values) === true && empty($values) === false) {
    $user_info = $values;
}

if (!users_is_admin() && $config['id_user'] !== $id && $new_user === false) {
    $group_um = users_get_groups_UM($config['id_user']);
    if (isset($group_um[0]) === true) {
        $group_um_string = implode(',', array_keys(users_get_groups($config['id_user'], 'um', true)));
    } else {
        $group_um_string = implode(',', array_keys($group_um));
    }

    $sql = sprintf(
        "SELECT tusuario_perfil.* FROM tusuario_perfil
        INNER JOIN tperfil ON tperfil.id_perfil = tusuario_perfil.id_perfil
        WHERE id_usuario like '%s' AND id_grupo IN (%s) AND user_management = 1",
        $config['id_user'],
        $group_um_string
    );

    $result = db_get_all_rows_sql($sql);
    if ((bool) $result === false && (bool) $user_info['is_admin'] === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access User Management'
        );
        include 'general/noaccess.php';

        return;
    }
}

if (!$new_user) {
    $user_id = '<div class="label_select_simple"><p class="edit_user_labels">'.__('User ID').': </p>';
    $user_id .= '<span>'.$id.'</span>';
    $user_id .= html_print_input_hidden('id_user', $id, true);
    $user_id .= '</div>';

    $apiTokenContentElements[] = '<span style="line-height: 15px; height: 15px;font-size: 14px;">'.__('API Token').'</span>';
    $apiTokenContentElements[] = html_print_button(
        __('Renew'),
        'renew_api_token',
        false,
        sprintf(
            'javascript:renewAPIToken("%s", "%s", "%s")',
            __('Warning'),
            __('The API token will be renewed. After this action, the last token you were using will not work. Are you sure?'),
            'user_profile_form',
        ),
        [
            'mode'  => 'link',
            'style' => 'min-width: initial;',
        ],
        true,
    );
    $apiTokenContentElements[] = html_print_button(
        __('Show'),
        'show_api_token',
        false,
        sprintf(
            'javascript:showAPIToken("%s", "%s")',
            __('API Token'),
            base64_encode(__('Your API Token is:').'&nbsp;<br><span class="font_12pt bolder">'.users_get_API_token($id).'</span><br>&nbsp;'.__('Please, avoid share this string with others.')),
        ),
        [
            'mode'  => 'link',
            'style' => 'min-width: initial;',
        ],
        true,
    );

    $apiTokenContent = html_print_div(
        [
            'class'   => 'flex-row-center',
            'content' => implode('', $apiTokenContentElements),
        ],
        true
    );

    $user_id .= $apiTokenContent;

    $CodeQRContent .= html_print_div(['id' => 'qr_container_image', 'class' => 'scale-0-8'], true);
    $CodeQRContent .= html_print_anchor(
        ['id' => 'qr_code_agent_view'],
        true
    );
    $CodeQRContent .= '<br/>'.$custom_id_div;

    // QR code div.
    $CodeQRTable = html_print_div(
        [
            'class'   => 'agent_qr',
            'content' => $CodeQRContent,
        ],
        true
    );
} else {
    $user_id = '<div class="label_select_simple">'.html_print_input_text_extended(
        'id_user',
        $id,
        '',
        '',
        20,
        255,
        !$new_user || $view_mode,
        '',
        [
            'class'       => 'input_line',
            'placeholder' => __('User ID'),
        ],
        true
    ).'</div>';
}

if (is_user_admin($id) === true) {
    $avatar = html_print_image(
        'images/people_1.png',
        true,
        ['class' => 'user_avatar']
    );
} else {
    $avatar = html_print_image(
        'images/people_2.png',
        true,
        ['class' => 'user_avatar']
    );
}

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

$language = '<div class="label_select"><p class="edit_user_labels">'.__('Language').'</p>';
$language .= html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true
).'</div>';


$timezone = '<div class="label_select"><p class="edit_user_labels">'.__('Timezone').ui_print_help_tip(
    __('The timezone must be that of the associated server.'),
    true
).'</p>';
$timezone .= html_print_timezone_select('timezone', $user_info['timezone']).'</div>';

if ($config['user_can_update_password']) {
    $new_pass = '<div class="label_select_simple"><span>'.html_print_input_text_extended(
        'password_new',
        '',
        'password_new',
        '',
        '25',
        '45',
        $view_mode,
        '',
        [
            'class'       => 'input',
            'placeholder' => __('Password'),
        ],
        true,
        true
    ).'</span></div>';
    $new_pass_confirm = '<div class="label_select_simple"><span>'.html_print_input_text_extended(
        'password_confirm',
        '',
        'password_conf',
        '',
        '20',
        '45',
        $view_mode,
        '',
        [
            'class'       => 'input',
            'placeholder' => __('Password confirmation'),
        ],
        true,
        true
    ).'</span></div>';

    if (!is_user_admin($config['id_user'])) {
        $own_pass_confirm = '<div class="label_select_simple"><span>'.html_print_input_text_extended(
            'own_password_confirm',
            '',
            'own_password_confirm',
            '',
            '20',
            '45',
            $view_mode,
            '',
            [
                'class'       => 'input',
                'placeholder' => __('Own password confirmation'),
            ],
            true,
            true
        ).'</span></div>';
    }
}

if (users_is_admin() === true) {
    $global_profile = '<div class="label_select_simple" style="display: flex;align-items: center;">';
    $global_profile .= '<p class="edit_user_labels" style="margin-top: 0;">'.__('Administrator user').'</p>';
    $global_profile .= html_print_checkbox_switch(
        'is_admin',
        0,
        $user_info['is_admin'],
        true
    );
    $global_profile .= '</div>';
} else {
    $global_profile = html_print_input_hidden(
        'is_admin_sent',
        0,
        true
    );
}

$email = '<div class="label_select_simple">'.html_print_input_text_extended(
    'email',
    $user_info['email'],
    'email',
    '',
    '25',
    '100',
    $view_mode,
    '',
    [
        'class'       => 'input input_line',
        'placeholder' => __('E-mail'),
    ],
    true
).'</div>';

$phone = '<div class="label_select_simple">'.html_print_input_text_extended(
    'phone',
    $user_info['phone'],
    'phone',
    '',
    '20',
    '30',
    $view_mode,
    '',
    [
        'class'       => 'input input_line phone_icon_input',
        'placeholder' => __('Phone number'),
    ],
    true
).'</div>';

$comments = '<p class="edit_user_labels">'.__('Comments').'</p>';
$comments .= html_print_textarea(
    'comments',
    2,
    65,
    $user_info['comments'],
    ($view_mode ? 'readonly="readonly"' : ''),
    true
);

$allowedIP = '<p class="edit_user_labels">';
$allowedIP .= __('Login allowed IP list').'&nbsp;';
$allowedIP .= ui_print_help_tip(__('Add the source IPs that will allow console access. Each IP must be separated only by comma. * allows all.'), true).'&nbsp;';
$allowedIP .= html_print_checkbox_switch(
    'allowed_ip_active',
    0,
    ($user_info['allowed_ip_active'] ?? 0),
    true
);
$allowedIP .= '</p>';
$allowedIP .= html_print_textarea(
    'allowed_ip_list',
    2,
    65,
    ($user_info['allowed_ip_list'] ?? 0),
    (((bool) $view_mode === true) ? 'readonly="readonly"' : ''),
    true
);


// If we want to create a new user, skins displayed are the skins of the creator's group. If we want to update, skins displayed are the skins of the modified user.
$own_info = get_user_info($config['id_user']);
if ($own_info['is_admin'] || check_acl($config['id_user'], 0, 'PM')) {
    $display_all_group = true;
} else {
    $display_all_group = false;
}

if ($new_user) {
    $usr_groups = (users_get_groups($config['id_user'], 'AR', $display_all_group));
    $id_usr = $config['id_user'];
} else {
    $usr_groups = (users_get_groups($id, 'AR', $display_all_group));
    $id_usr = $id;
}

if (is_metaconsole() === false) {
    // User only can change skins if has more than one group.
    if (function_exists('skins_print_select')) {
        if (count($usr_groups) > 1) {
            if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
                $skin = '<div class="label_select"><p class="edit_user_labels">'.__('Skin').'</p>';
                $skin .= skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true).'</div>';
            }
        }
    }
}

if (is_metaconsole() === true) {
    $array_filters = get_filters_custom_fields_view(0, true);

    $searchCustomFieldView = [];
    $searchCustomFieldView[] = __('Search custom field view');
    $searchCustomFieldView[] = html_print_select(
        $array_filters,
        'default_custom_view',
        $user_info['default_custom_view'],
        '',
        __('None'),
        0,
        true,
        false,
        true,
        '',
        false
    ).ui_print_input_placeholder(
        __('Load by default the selected view in custom field view'),
        true
    );
}

$values = [
    -1 => __('Use global conf'),
    1  => __('Yes'),
    0  => __('No'),
];

$home_screen = '<div class="label_select"><p class="edit_user_labels">'.__('Home screen').ui_print_help_tip(
    __('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1 to show agent detail view'),
    true
).'</p>';

$dashboards = Manager::getDashboards(
    -1,
    -1,
    false,
    false,
    $id_usr
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
$home_screen .= html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true);
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
$home_screen .= html_print_select(
    $layouts_aux,
    'visual_console',
    $user_info['data_section'],
    '',
    '',
    '',
    true
);
$home_screen .= '</div>';

$home_screen .= html_print_input_text(
    'data_section',
    $user_info['data_section'],
    '',
    60,
    255,
    true,
    false
);

$home_screen = '';

$size_pagination = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Block size for pagination').'</p>';
$size_pagination .= html_print_input_text(
    'block_size',
    $user_info['block_size'],
    '',
    5,
    5,
    true
).'</div>';

if ($id === $config['id_user']) {
    $language .= html_print_input_hidden(
        'quick_language_change',
        1,
        true
    );
}

if (enterprise_installed() === true && is_metaconsole() === true) {
    $user_info_metaconsole_access = 'only_console';
    if (isset($user_info['metaconsole_access'])) {
        $user_info_metaconsole_access = $user_info['metaconsole_access'];
    }

    $metaconsole_accesses = [
        'basic'    => __('Basic'),
        'advanced' => __('Advanced'),
    ];

    $outputMetaAccess = [];
    $outputMetaAccess[] = __('Metaconsole access');
    $outputMetaAccess[] = html_print_select(
        $metaconsole_accesses,
        'metaconsole_access',
        $user_info_metaconsole_access,
        '',
        '',
        -1,
        true,
        false,
        false
    );
}

$user_groups = implode(',', array_keys((users_get_groups($id, 'AR', $display_all_group))));

if (empty($user_groups) === false) {
    $event_filter_data = db_get_all_rows_sql('SELECT id_name, id_filter FROM tevent_filter WHERE id_group_filter IN ('.$user_groups.')');
    if ($event_filter_data === false) {
        $event_filter_data = [];
    }
} else {
    $event_filter_data = [];
}


$event_filter = [];
$event_filter[0] = __('None');
foreach ($event_filter_data as $filter) {
    $event_filter[$filter['id_filter']] = $filter['id_name'];
}

if (is_metaconsole() === true && empty($user_info['metaconsole_default_event_filter']) !== true) {
    $user_info['default_event_filter'] = $user_info['metaconsole_default_event_filter'];
}

$default_event_filter = '<div class="label_select"><p class="edit_user_labels">'.__('Default event filter').'</p>';
$default_event_filter .= html_print_select(
    $event_filter,
    'default_event_filter',
    ($user_info['default_event_filter'] ?? 0),
    '',
    '',
    __('None'),
    true,
    false,
    false
).'</div>';

if (isset($config['ehorus_user_level_conf']) === true && (bool) $config['ehorus_user_level_conf'] === true) {
    $ehorus = '<div class="label_select_simple"><p class="edit_user_labels">'.__('eHorus user access enabled').'</p>';
    $ehorus .= html_print_checkbox_switch(
        'ehorus_user_level_enabled',
        1,
        $user_info['ehorus_user_level_enabled'],
        true
    ).'</div>';
    $ehorus .= '<div class="user_edit_ehorus_outer">';
    $ehorus .= '<div class="label_select_simple user_edit_ehorus_inner"><p class="edit_user_labels">'.__('eHorus user').'</p>';
    $ehorus .= html_print_input_text(
        'ehorus_user_level_user',
        $user_info['ehorus_user_level_user'],
        '',
        15,
        45,
        true
    ).'</div>';
    $ehorus .= '<div class="label_select_simple user_edit_ehorus_inner"><p class="edit_user_labels">'.__('eHorus password').'</p>';
    $ehorus .= html_print_input_password(
        'ehorus_user_level_pass',
        io_output_password($user_info['ehorus_user_level_pass']),
        '',
        15,
        45,
        true
    ).'</div>';
    $ehorus .= '</div>';
}

// Double authentication.
$doubleAuthElementsContent = [];
if (isset($config['double_auth_enabled']) === true && (bool) ($config['double_auth_enabled']) === true && check_acl($config['id_user'], 0, 'PM')) {
    // Know if Double Auth is enabled.
    $double_auth_enabled = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $id);
    // Double authentication elements.
    $doubleAuthElementsSubContent = [];
    // Caption.
    $doubleAuthElementsSubContent[] = '<span>'.__('Double authentication').'</span>';
    // Switch.
    if (($config['2FA_all_users'] == '' && !$double_auth_enabled)
        || ($config['double_auth_enabled'] == '' && $double_auth_enabled)
        || check_acl($config['id_user'], 0, 'PM')
    ) {
        if ($new_user === false) {
            $doubleAuthElementsSubContent[] = html_print_checkbox_switch('double_auth', 1, $double_auth_enabled, true);
        } else {
            $doubleAuthElementsSubContent[] = ui_print_help_tip(__('User must be created before activating double authentication.'), true);
        }
    }

    // Control for show.
    $doubleAuthElementsContent[] = html_print_div(
        [
            'style'   => 'display: flex; flex-direction: row-reverse; align-items: center;',
            'class'   => 'margin-top-10',
            'content' => implode('', $doubleAuthElementsSubContent),
        ],
        true
    );

    // Dialog.
    $doubleAuthElementsContent[] = html_print_div(
        [
            'id'      => 'dialog-double_auth',
            'class'   => 'invisible',
            'content' => html_print_div(['id' => 'dialog-double_auth-container'], true),
        ],
        true
    );
}

if ($double_auth_enabled === true && (bool) $config['double_auth_enabled'] === true && empty($config['2FA_all_users']) === false) {
    $doubleAuthElementsContent[] = html_print_button(
        __('Show information'),
        'show_info',
        false,
        'javascript:show_double_auth_info();',
        '',
        true
    );
}

if (empty($doubleAuthElementsContent) === false) {
    $doubleAuthentication = html_print_div(['content' => implode('', $doubleAuthElementsContent)], true);
} else {
    $doubleAuthentication = '';
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


if (isset($autorefresh_list) === false || empty($autorefresh_list) === true || empty($autorefresh_list[0]) === true) {
    $select = db_process_sql("SELECT autorefresh_white_list FROM tusuario WHERE id_user = '".$id."'");
    $autorefresh_list = json_decode($select[0]['autorefresh_white_list']);
    if ($autorefresh_list === null || $autorefresh_list === 0) {
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

if (is_metaconsole() === true) {
    enterprise_include_once('include/functions_metaconsole.php');

    $access_node = db_get_value('metaconsole_access_node', 'tusuario', 'id_user', $id);

    $metaconsoleAgentManager = [];
    $metaconsoleAgentManager[] = __('Enable agents managment');
    $metaconsoleAgentManager[] = html_print_checkbox_switch(
        'metaconsole_agents_manager',
        1,
        $user_info['metaconsole_agents_manager'],
        true
    );

    $metaconsoleAgentManager[] = __('Enable node access').ui_print_help_tip(
        __('With this option enabled, the user will can access to nodes console'),
        true
    );
    $metaconsoleAgentManager[] = html_print_checkbox_switch(
        'metaconsole_access_node',
        1,
        $access_node,
        true
    );
}

echo '<div class="max_floating_element_size">';
echo '<form id="user_profile_form" name="user_profile_form" method="post" autocomplete="off" action="#">';

if (!$id) {
    $user_id_update_view = $user_id;
    $user_id_create = '';
} else {
    $user_id_update_view = '';
    $user_id_create = $user_id;
}

// User management form.
require_once 'user_management.php';

if ((bool) $config['admin_can_add_user'] === true) {
    html_print_csrf_hidden();
    html_print_input_hidden((($new_user === true) ? 'create_user' : 'update_user'), 1);
}

echo '</div>';
html_print_input_hidden('json_profile', $json_profile);

echo '</form>';

// User Profile definition table. (Only where user is not creating).
if ((bool) check_acl($config['id_user'], 0, 'UM') === true) {
    profile_print_profile_table($id, io_safe_output($json_profile), false, ($is_err === true));
}

echo '</div>';

$actionButtons = [];

if ((bool) $config['admin_can_add_user'] === true) {
    if ($new_user === true) {
        $submitButtonCaption = __('Create');
        $submitButtonName = 'crtbutton';
        $submitButtonIcon = 'wand';
    } else {
        $submitButtonCaption = __('Update');
        $submitButtonName = 'uptbutton';
        $submitButtonIcon = 'update';
    }

    $actionButtons[] = html_print_submit_button(
        $submitButtonCaption,
        $submitButtonName,
        false,
        [
            'icon' => $submitButtonIcon,
            'form' => 'user_profile_form',
        ],
        true
    );
}

if ((bool) check_acl($config['id_user'], 0, 'UM') === true) {
    $actionButtons[] = html_print_go_back_button(
        ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure=0'),
        ['button_class' => ''],
        true
    );
}

html_print_action_buttons(implode('', $actionButtons), ['type' => 'form_action']);

echo '</div>';

// This is an image generated for JS.
$delete_image = html_print_input_image(
    'del',
    'images/delete.svg',
    1,
    '',
    true,
    [
        'onclick' => 'delete_profile(event, this)',
        'class'   => 'invert_filter main_menu_icon',
    ]
);

if (is_metaconsole() === false) {
    ?>

    <style>
        /* Styles for timezone map */
        #timezone-picker div.timezone-picker {
            margin: 0 auto;
        }
    </style>

    <script language="javascript" type="text/javascript">
        $(document).ready(function() {
            // Set up the picker to update target timezone and country select lists.
            $('#timezone-image').timezonePicker({
                target: '#timezone1',
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

<script type="text/javascript">
    var json_profile = $('#hidden-json_profile');
    /* <![CDATA[ */
    $(document).ready(function() {
        $("#right_autorefreshlist").click(function() {
            jQuery.each($("select[name='autorefresh_list_out[]'] option:selected"), function(key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("select[name='autorefresh_list[]'] option").each(function() { $(this).attr("selected", true) });
                    $("select[name='autorefresh_list[]']").append($("<option></option>").val(id_imodule).html('<i>' + imodule_name + '</i>').attr("selected", true));
                    $("#autorefresh_list_out").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list").find("option[value='']").remove();
                    $("#autorefresh_list").find("option[value='0']").remove();
                    if ($("#autorefresh_list_out option").length == 0) {
                        $("select[name='autorefresh_list_out[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
            });
        });

        $("#left_autorefreshlist").click(function() {
            jQuery.each($("select[name='autorefresh_list[]'] option:selected"), function(key, value) {
                imodule_name = $(value).html();
                if (imodule_name != <?php echo "'".__('None')."'"; ?>) {
                    id_imodule = $(value).attr('value');
                    $("#autorefresh_list").find("option[value='" + id_imodule + "']").remove();
                    $("#autorefresh_list_out").find("option[value='']").remove();
                    $("select[name='autorefresh_list_out[]']").append($("<option><option>").val(id_imodule).html('<i>' + imodule_name + '</i>'));
                    $("#autorefresh_list_out option").last().remove();
                    if ($("#autorefresh_list option").length == 0) {
                        $("select[name='autorefresh_list[]']").append($("<option></option>").val('').html('<i><?php echo __('None'); ?></i>'));
                    }
                }
            });
        });

        $("#button-uptbutton").click (function () {
            console.log('aaaaaaaaaaaaa');
            if($("#autorefresh_list option").length > 0) {
                $('#autorefresh_list option').prop('selected', true);
            }
        });

        $("input#checkbox-double_auth").change(function(e) {
            e.preventDefault();
            if (this.checked) {
                show_double_auth_activation();
            } else {
                show_double_auth_deactivation();
            }
        });

        $('#checkbox-is_admin').change(function() {
            if ($('#checkbox-is_admin').is(':checked') == true) {
                $('#metaconsole_agents_manager_div').hide();
                $('#metaconsole_access_node_div').hide();
                $('#metaconsole_assigned_server_div').hide();
            } else {
                $('#metaconsole_agents_manager_div').show();
                $('#metaconsole_access_node_div').show();
                if ($('#checkbox-metaconsole_agents_manager').prop('checked')) {
                    $('#metaconsole_assigned_server_div').show();
                }
            }
        });

        $('#checkbox-metaconsole_agents_manager').change(function() {
            if ($('#checkbox-metaconsole_agents_manager').prop('checked')) {
                $('#metaconsole_assigned_server_div').show();
            } else {
                $('#metaconsole_assigned_server_div').hide();
            }
        });

        $('#checkbox-is_admin').trigger('change');
        $('#checkbox-metaconsole_agents_manager').trigger('change');

        show_data_section();
        $('#checkbox-ehorus_user_level_enabled').change(function() {
            switch_ehorus_conf();
        });
        $('#checkbox-ehorus_user_level_enabled').trigger('change');
        var img_delete = '<?php echo $delete_image; ?>';
        var id_user = '<?php echo io_safe_output($id); ?>';
        var is_metaconsole = '<?php echo is_metaconsole(); ?>';
        var user_is_global_admin = '<?php echo users_is_admin($id); ?>';
        var is_err = '<?php echo $is_err; ?>';
        var data = [];
        var aux = 0;

        function addProfile(form) {
            try {
                var data = JSON.parse(json_profile.val());
            } catch {
                var data = [];
            }

            var profile = $('#assign_profile').val();
            var profile_text = $('#assign_profile option:selected').text();
            var group = $('#assign_group').val();
            var group_text = $('#assign_group option:selected').text();
            var tags = $('#assign_tags').val();
            var tags_text = $('#assign_tags option:selected').toArray().map(item => item.text).join();
            if ($('#checkbox-no_hierarchy').is(':checked')) {
                var hierarchy = 1;
                var hierarchy_text = '<?php echo __('yes'); ?>';
            } else {
                var hierarchy = 0;
                var hierarchy_text = '<?php echo __('no'); ?>';
            }

            if (profile === '0' || group === '-1') {
                alert('<?php echo __('Please select profile and group'); ?>');
                return;
            }

            if (id_user == '' || is_err == 1) {
                let new_json = `{"profile":${profile},"group":${group},"tags":[${tags}],"hierarchy":${hierarchy}}`;

                var profile_is_added = Object.entries(data).find(function(_data) {
                    return _data[1] === new_json;
                });

                if (typeof profile_is_added === 'undefined') {
                    data.push(new_json);
                } else {
                    alert('<?php echo __('This profile is already defined'); ?>');
                    return;
                }

                json_profile.val(JSON.stringify(data));

                profile_text = `<a href="index.php?sec2=godmode/users/configure_profile&id=${profile}">${profile_text}</a>`;
                group_img = `<img id="img_group_${aux}" src="" data-title="${group_text}" data-use_title_for_force_title="1" class="invert_filter main_menu_icon bot forced_title" alt="${group_text}"/>`;
                group_text = `<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id=${group}">${group_img} ${group_text}</a>`;

                $('#table_profiles tr:last').before(
                    `<tr>
                    <td>${profile_text}</td>
                    <td>${group_text}</td>
                    <td>${tags_text}</td>
                    <td>${hierarchy_text}</td>
                    <td>${img_delete}</td>
                </tr>`
                );

                getGroupIcon(group, $(`#img_group_${aux}`));
                aux++;

            } else {
                form.submit();
            }
        }

        $('input:image[name="add"]').click(function(e) {
            e.preventDefault();

            if (id_user.length === 0) {
                addProfile(this.form);
                return;
            }

            var params = [];
            params.push("get_user_profile=1");
            params.push("profile_id=" + $('#assign_profile').val())
            params.push("group_id=" + $('#assign_group').val());
            params.push("user_id=" + id_user);
            params.push("page=godmode/users/configure_user");
            jQuery.ajax({
                data: params.join("&"),
                type: 'POST',
                dataType: "json",
                async: false,
                form: this.form,
                url: action = "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                success: function(data) {
                    if (data.length > 0) {
                        alert('<?php echo __('This profile is already defined'); ?>');
                    } else {
                        addProfile(this.form);
                    }
                }
            });
        });

        $('input:image[name="del"]').click(function(e) {
            if ($(json_profile).length > 0) return;
            if (!confirm('Are you sure?')) return;
            e.preventDefault();
            var rows = $("#table_profiles tr").length;
            if (((is_metaconsole === '1' && rows <= 4) || (is_metaconsole === '' && rows <= 3)) && user_is_global_admin !== '1') {
                if (!confirm('<?php echo __('Deleting last profile will delete this user'); ?>' + '. ' + '<?php echo __('Are you sure?'); ?>')) {
                    return;
                }
            }

            var id_user_profile = $(this).siblings();
            id_user_profile = id_user_profile[1].value;
            var row = $(this).closest('tr');

            var params = [];
            params.push("delete_profile=1");
            params.push("id_user=" + id_user);
            params.push("id_user_profile=" + id_user_profile);
            params.push("page=godmode/users/configure_user");
            jQuery.ajax({
                data: params.join("&"),
                type: 'POST',
                url: action = "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                success: function(data) {
                    row.remove();
                    var rows = $("#table_profiles tr").length;

                    if (is_metaconsole === '' && rows <= 2 && user_is_global_admin !== '1') {
                        window.location.replace("<?php echo ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure=0', false, false, false); ?>");
                    } else if (is_metaconsole === '1' && rows <= 3 && user_is_global_admin !== '1') {
                        window.location.replace("<?php echo ui_get_full_url('index.php?sec=advanced&sec2=advanced/users_setup', false, false, true); ?>");
                    }
                }
            });
        });

        function checkProfiles(e) {
            e.preventDefault();
            if ($('#checkbox-is_admin').is(':checked') == true) {
                // Admin does not require profiles.
                $('#user_profile_form').submit();
            } else {
                if ($('#table_profiles tbody').children().length == 1) {
                    confirmDialog({
                        title: "<?php echo __('Warning'); ?>",
                        message: "<?php echo __('User will be created without profiles assigned and won\'t be able to log in, are you sure?'); ?>",
                        onAccept: function() {
                            $('#user_profile_form').submit();
                        }
                    });
                } else {
                    $('#user_profile_form').submit();
                }
            }
        }

        $('#submit-crtbutton').click(function(e) {
            checkProfiles(e);
        });

        $('#submit-uptbutton').click(function(e) {
            checkProfiles(e);
        });
    });

    function delete_profile(event, btn) {
        event.preventDefault();
        var row = btn.parentNode.parentNode;
        var position = row.rowIndex;
        row.parentNode.removeChild(row);

        var json = json_profile.val();
        var test = JSON.parse(json);

        var position_offset = <?php echo (is_metaconsole() === true) ? 2 : 1; ?>;

        test.splice(position - position_offset, 1);
        json_profile.val(JSON.stringify(test));
    }

    function show_data_section() {
        var $section = $("#section").val();
        var $allElements = $('div[id^="custom_home_screen_"]');
        var $elementSelected = $('div[id="custom_home_screen_' + $section + '"]');
        // Hide all elements.
        $allElements.each(function() {
            $(this).addClass('invisible');
            $(this).children().addClass('invisible');
        })
        // Show only the selected.
        $elementSelected.removeClass('invisible');
        $elementSelected.children().removeClass('invisible');
    }

    function switch_ehorus_conf() {
        if (!$('#checkbox-ehorus_user_level_enabled').prop('checked')) {
            $(".user_edit_ehorus_outer").hide();

        } else {
            $(".user_edit_ehorus_outer").show();
        }


    }

    function show_double_auth_info() {
        var userID = '<?php echo io_safe_output($id); ?>';

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
                FA_forced: 1,
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
                } else {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Error').'</div></b>'; ?>");
                }
            },
            error: function(xhr, textStatus, errorThrown) {
                $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('There was an error loading the data').'</div></b>'; ?>");
            }
        });

        $("div#dialog-double_auth")
            .css('display', 'block')
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

    function show_double_auth_activation() {
        var userID = '<?php echo io_safe_output($id); ?>';

        var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
        var $dialogContainer = $("div#dialog-double_auth-container");
        // Uncheck until completed successfully.
        $("input#checkbox-double_auth").prop("checked", false);

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
                FA_forced: 1,
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
                } else {
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
                }
            })
            .show();
    }

    function show_double_auth_deactivation() {
        var userID = '<?php echo io_safe_output($id); ?>';
        var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
        var $dialogContainer = $("div#dialog-double_auth-container");

        var message = "<p><?php echo __('Are you sure?').'<br>'.__('The double authentication will be deactivated'); ?></p>";
        var $button = $("<input type=\"button\" value=\"<?php echo __('Deactivate'); ?>\" />");
        // Prevent switch deactivaction until proceess is done
        $("input#checkbox-double_auth").prop("checked", true);


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
                    FA_forced: 1,
                    deactivate_double_auth: 1
                },
                complete: function(xhr, textStatus) {

                },
                success: function(data, textStatus, xhr) {
                    if (data === -1) {
                        $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                    } else if (data) {
                        $dialogContainer.html("<?php echo '<b><div class=\"green\">'.__('The double autentication was deactivated successfully').'</div></b>'; ?>");
                        $("input#checkbox-double_auth").prop("checked", false);
                    } else {
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

                }
            })
            .show();
    }


    /* ]]> */
</script>
