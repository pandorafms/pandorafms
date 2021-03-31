<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/vendor/autoload.php';

use PandoraFMS\Dashboard\Manager;

enterprise_hook('open_meta_frame');

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
require_once $config['homedir'].'/include/functions_visual_map.php';
require_once $config['homedir'].'/include/functions_custom_fields.php';
enterprise_include_once('include/functions_profile.php');

$meta = false;
if (enterprise_installed() && defined('METACONSOLE')) {
    $meta = true;
}

$isFunctionSkins = enterprise_include_once('include/functions_skins.php');

// Add the columns for the enterprise Pandora edition.
$enterprise_include = false;
if (ENTERPRISE_NOT_HOOK !== enterprise_include('include/functions_policies.php')) {
    $enterprise_include = true;
}

if ($enterprise_include) {
    enterprise_include_once('meta/include/functions_users_meta.php');
}


if (!is_metaconsole()) {
    date_default_timezone_set('UTC');
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
}

// This defines the working user. Beware with this, old code get confusses
// and operates with current logged user (dangerous).
$id = get_parameter('id', get_parameter('id_user', ''));
// ID given as parameter
$pure = get_parameter('pure', 0);

$user_info = get_user_info($id);
$is_err = false;

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access User Management'
    );
    include 'general/noaccess.php';

    return;
}

if (is_ajax()) {
    $delete_profile = (bool) get_parameter('delete_profile');
    if ($delete_profile) {
        $id2 = (string) get_parameter('id_user');
        $id_up = (int) get_parameter('id_user_profile');

        $perfilUser = db_get_row('tusuario_perfil', 'id_up', $id_up);
        $id_perfil = $perfilUser['id_perfil'];
        $perfil = db_get_row('tperfil', 'id_perfil', $id_perfil);

        db_pandora_audit(
            'User management',
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
        if ($has_profile == false) {
            $result = delete_user($id2);

            if ($result) {
                db_pandora_audit(
                    'User management',
                    __('Deleted user %s', io_safe_output($id_user))
                );
            }

            ui_print_result_message(
                $result,
                __('Successfully deleted'),
                __('There was a problem deleting the user')
            );

            // Delete the user in all the consoles
            if (defined('METACONSOLE')) {
                $servers = metaconsole_get_servers();
                foreach ($servers as $server) {
                    // Connect to the remote console
                    metaconsole_connect($server);

                    // Delete the user
                    $result = delete_user($id_user);
                    if ($result) {
                        db_pandora_audit(
                            'User management',
                            __('Deleted user %s from metaconsole', io_safe_output($id_user))
                        );
                    }

                    // Restore the db connection
                    metaconsole_restore_db();

                    // Log to the metaconsole too
                    if ($result) {
                        db_pandora_audit(
                            'User management',
                            __('Deleted user %s from %s', io_safe_input($id_user), io_safe_input($server['server_name']))
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

        return;
    }
}



$tab = get_parameter('tab', 'user');

if ($id) {
    $header_title = ' &raquo; '.__('Update user');
} else {
    $header_title = ' &raquo; '.__('Create user');
}

// Header
if ($meta) {
    user_meta_print_header();
    $sec = 'advanced';
} else {
    $buttons = [
        'user'    => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image(
                'images/user.png',
                true,
                [
                    'title' => __('User management'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ],
        'profile' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">'.html_print_image(
                'images/profiles.png',
                true,
                [
                    'title' => __('Profile management'),
                    'class' => 'invert_filter',
                ]
            ).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;

    ui_print_page_header(
        __('User detail editor').$header_title,
        'images/gm_users.png',
        false,
        '',
        true,
        $buttons
    );
    $sec = 'gusuarios';
}


if ($config['user_can_update_info']) {
    $view_mode = false;
} else {
    $view_mode = true;
}

$new_user = (bool) get_parameter('new_user');
$create_user = (bool) get_parameter('create_user');
$add_profile = (bool) get_parameter('add_profile');
$update_user = (bool) get_parameter('update_user');
$status = get_parameter('status', -1);
$json_profile = get_parameter('json_profile', '');

// Reset status var if current action is not update_user
if ($new_user || $create_user || $add_profile
    || $delete_profile || $update_user
) {
    $status = -1;
}

if ($new_user && $config['admin_can_add_user']) {
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
    $user_info['strict_acl'] = false;
    $user_info['session_time'] = 0;
    $user_info['middlename'] = 0;

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $user_info['id_skin'] = '';
    }

    $user_info['section'] = '';
    $user_info['data_section'] = '';
    // This attributes are inherited from global configuration
    $user_info['block_size'] = $config['block_size'];

    if (enterprise_installed() && defined('METACONSOLE')) {
        $user_info['metaconsole_agents_manager'] = 0;
        $user_info['metaconsole_assigned_server'] = '';
        $user_info['metaconsole_access_node'] = 0;
    }

    if ($config['ehorus_user_level_conf']) {
        $user_info['ehorus_user_level_user'] = '';
        $user_info['ehorus_user_level_pass'] = '';
        $user_info['ehorus_user_level_enabled'] = true;
    }
}

if ($create_user) {
    if (! $config['admin_can_add_user']) {
        ui_print_error_message(__('The current authentication scheme doesn\'t support creating users on %s', get_product_name()));
        return;
    }

    if (html_print_csrf_error()) {
        return;
    }

    $user_is_admin = (int) get_parameter('is_admin', 0);

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
    $values['is_admin'] = $user_is_admin;
    $values['language'] = get_parameter('language', 'default');
    $values['timezone'] = (string) get_parameter('timezone');
    $values['default_event_filter'] = (int) get_parameter('default_event_filter');
    $values['default_custom_view'] = (int) get_parameter('default_custom_view');
    $dashboard = get_parameter('dashboard', '');
    $visual_console = get_parameter('visual_console', '');

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $values['id_skin'] = (int) get_parameter('skin', 0);
    }

    $values['block_size'] = (int) get_parameter('block_size', $config['block_size']);

    $values['section'] = get_parameter('section');
    if (($values['section'] == 'Event list') || ($values['section'] == 'Group view') || ($values['section'] == 'Alert detail') || ($values['section'] == 'Tactical view') || ($values['section'] == 'Default')) {
        $values['data_section'] = '';
    } else if ($values['section'] == 'Dashboard') {
        $values['data_section'] = $dashboard;
    } else if (io_safe_output($values['section']) == 'Visual console') {
        $values['data_section'] = $visual_console;
    } else if ($values['section'] == 'Other' || io_safe_output($values['section']) == 'External link') {
        $values['data_section'] = get_parameter('data_section');
    }

    if (enterprise_installed()) {
        $values['force_change_pass'] = 1;
        $values['last_pass_change'] = date('Y/m/d H:i:s', get_system_time());
        if (defined('METACONSOLE')) {
            $values['metaconsole_access'] = get_parameter('metaconsole_access', 'basic');
            $values['metaconsole_agents_manager'] = ($user_is_admin == 1 ? 1 : get_parameter('metaconsole_agents_manager', '0'));
            $values['metaconsole_assigned_server'] = get_parameter('metaconsole_assigned_server', '');
            $values['metaconsole_access_node'] = ($user_is_admin == 1 ? 1 : get_parameter('metaconsole_access_node', '0'));
        }
    }

    $values['not_login'] = (bool) get_parameter('not_login', false);
    $values['middlename'] = get_parameter('middlename', 0);
    $values['strict_acl'] = (bool) get_parameter('strict_acl', false);
    $values['session_time'] = (int) get_parameter('session_time', 0);

    // eHorus user level conf
    if ($config['ehorus_user_level_conf']) {
        $values['ehorus_user_level_enabled'] = (bool) get_parameter('ehorus_user_level_enabled', false);
        if ($values['ehorus_user_level_enabled'] === true) {
            $values['ehorus_user_level_user'] = (string) get_parameter('ehorus_user_level_user');
            $values['ehorus_user_level_pass'] = (string) get_parameter('ehorus_user_level_pass');
        } else {
            $values['ehorus_user_level_user'] = null;
            $values['ehorus_user_level_pass'] = null;
        }
    }


    if ($id == '') {
        ui_print_error_message(__('User ID cannot be empty'));
        $is_err = true;
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
    } else if ($password_new == '') {
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

        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            $info .= ',"Skin":"'.$values['id_skin'].'"}';
        } else {
            $info .= '}';
        }

        $can_create = false;



        if ($result) {
            $res = save_pass_history($id, $password_new);
        }

        db_pandora_audit(
            'User management',
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

            if (!empty($json_profile)) {
                $json_profile = json_decode(io_safe_output($json_profile), true);
                foreach ($json_profile as $key => $profile) {
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
                            'User management',
                            'Added profile for user '.io_safe_output($id2),
                            false,
                            false,
                            'Profile: '.$profile2.' Group: '.$group2.' Tags: '.$tags
                        );

                        $result_profile = profile_create_user_profile($id, $profile2, $group2, false, $tags, $no_hierarchy);

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
    if (html_print_csrf_error()) {
        return;
    }

    $values = [];
    $values['id_user'] = (string) get_parameter('id_user');
    $values['fullname'] = (string) get_parameter('fullname');
    $values['firstname'] = (string) get_parameter('firstname');
    $values['lastname'] = (string) get_parameter('lastname');
    $values['email'] = (string) get_parameter('email');
    $values['phone'] = (string) get_parameter('phone');
    $values['comments'] = io_safe_input(strip_tags(io_safe_output((string) get_parameter('comments'))));
    $values['is_admin'] = get_parameter('is_admin', 0);
    $values['language'] = (string) get_parameter('language');
    $values['timezone'] = (string) get_parameter('timezone');
    $values['default_event_filter'] = (int) get_parameter('default_event_filter');
    $values['default_custom_view'] = (int) get_parameter('default_custom_view');

    // eHorus user level conf.
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
    if (($values['section'] == 'Event list') || ($values['section'] == 'Group view') || ($values['section'] == 'Alert detail') || ($values['section'] == 'Tactical view') || ($values['section'] == 'Default')) {
        $values['data_section'] = '';
    } else if ($values['section'] == 'Dashboard') {
        $values['data_section'] = $dashboard;
    } else if (io_safe_output($values['section']) == 'Visual console') {
        $values['data_section'] = $visual_console;
    } else if ($values['section'] == 'Other' || io_safe_output($values['section']) == 'External link') {
        $values['data_section'] = get_parameter('data_section');
    }

    if (enterprise_installed() && defined('METACONSOLE')) {
        $values['metaconsole_access'] = get_parameter('metaconsole_access');
        $values['metaconsole_agents_manager'] = get_parameter('metaconsole_agents_manager', '0');
        $values['metaconsole_assigned_server'] = get_parameter('metaconsole_assigned_server', '');
        $values['metaconsole_access_node'] = get_parameter('metaconsole_access_node', '0');
    }

    $values['not_login'] = (bool) get_parameter('not_login', false);
    $values['strict_acl'] = (bool) get_parameter('strict_acl', false);
    $values['session_time'] = (int) get_parameter('session_time', 0);


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

            if ($password_confirm == $password_new) {
                if ($correct_password === true || is_user_admin($config['id_user'])) {
                    if ((!is_user_admin($config['id_user']) || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
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

            $info = '{"id_user":"'.$values['id_user'].'",
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

            if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
                $info .= ',"Skin":"'.$values['id_skin'].'"';
                $has_skin = true;
            }

            if (enterprise_installed() && defined('METACONSOLE')) {
                $info .= ',"Wizard access":"'.$values['metaconsole_access'].'"}';
                $has_wizard = true;
            } else if ($has_skin) {
                $info .= '}';
            }

            if (!$has_skin && !$has_wizard) {
                $info .= '}';
            }


            db_pandora_audit(
                'User management',
                'Updated user '.io_safe_output($id),
                false,
                false,
                $info
            );

            ui_print_result_message(
                $res1,
                __('User info successfully updated'),
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

    if ($values['strict_acl']) {
        $count_groups = 0;
        $count_tags = 0;

        $profiles = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $id);
        if ($profiles === false) {
            $profiles = [];
        }

        foreach ($profiles as $profile) {
            $count_groups = ($count_groups + 1);
            $arr_tags = explode(',', $profile['tags']);
            $count_tags = ($count_tags + count($arr_tags));
        }

        if (($count_groups > 3) && ($count_tags > 10)) {
            ui_print_info_message(__('Strict ACL is not recommended for this user. Performance could be affected.'));
        }
    }

    $user_info = $values;
}

if ($status != -1) {
    ui_print_result_message(
        $status,
        __('User info successfully updated'),
        __('Error updating user info (no change?)')
    );
}

if ($add_profile && empty($json_profile)) {
    $id2 = (string) get_parameter('id');
    $group2 = (int) get_parameter('assign_group');
    $profile2 = (int) get_parameter('assign_profile');
    $tags = (array) get_parameter('assign_tags');
    $no_hierarchy = (int) get_parameter('no_hierarchy', 0);

    foreach ($tags as $k => $tag) {
        if (empty($tag)) {
            unset($tags[$k]);
        }
    }

    $tags = implode(',', $tags);

    db_pandora_audit(
        'User management',
        'Added profile for user '.io_safe_output($id2),
        false,
        false,
        'Profile: '.$profile2.' Group: '.$group2.' Tags: '.$tags
    );
    $return = profile_create_user_profile($id2, $profile2, $group2, false, $tags, $no_hierarchy);
    ui_print_result_message(
        $return,
        __('Profile added successfully'),
        __('Profile cannot be added')
    );
}

if ($values) {
    $user_info = $values;
}

if (!users_is_admin() && $config['id_user'] != $id && !$new_user) {
    $group_um = users_get_groups_UM($config['id_user']);
    if (isset($group_um[0])) {
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
    if ($result == false && $user_info['is_admin'] == false) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access User Management'
        );
        include 'general/noaccess.php';

        return;
    }
}

if (defined('METACONSOLE')) {
    if ($id) {
        echo '<div class="user_form_title">'.__('Update User').'</div>';
    } else {
        echo '<div class="user_form_title">'.__('Create User').'</div>';
    }
}

if (!$new_user) {
    $user_id = '<div class="label_select_simple"><p class="edit_user_labels">'.__('User ID').': </p>';
    $user_id .= '<span>'.$id.'</span>';
    $user_id .= html_print_input_hidden('id_user', $id, true);
    $user_id .= '</div>';
} else {
    $user_id = '<div class="label_select_simple">'.html_print_input_text_extended(
        'id_user',
        $id,
        '',
        '',
        20,
        100,
        !$new_user || $view_mode,
        '',
        [
            'class'       => 'input_line user_icon_input',
            'placeholder' => __('User ID'),
        ],
        true
    ).'</div>';
}

if (is_user_admin($id)) {
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

$own_info = get_user_info($config['id_user']);
$global_profile = '<div class="label_select_simple user_global_profile" ><span class="input_label" class"mrgn_0px">'.__('Global Profile').'</span>';
$global_profile .= '<div class="switch_radio_button">';
if (users_is_admin()) {
    $global_profile .= html_print_radio_button_extended(
        'is_admin',
        1,
        [
            'label'    => __('Administrator'),
            'help_tip' => __('This user has permissions to manage all. An admin user should not requiere additional group permissions, except for using Enterprise ACL.'),
        ],
        $user_info['is_admin'],
        false,
        '',
        '',
        true
    );
}

$global_profile .= html_print_radio_button_extended(
    'is_admin',
    0,
    [
        'label'    => __('Standard User'),
        'help_tip' => __('This user has separated permissions to view data in his group agents, create incidents belong to his groups, add notes in another incidents, create personal assignments or reviews and other tasks, on different profiles'),
    ],
    $user_info['is_admin'],
    false,
    '',
    '',
    true
);
$global_profile .= '</div></div>';

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
        'class'       => 'input input_line email_icon_input invert_filter',
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

if (!$meta) {
    // User only can change skins if has more than one group
    if (count($usr_groups) > 1) {
        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            $skin = '<div class="label_select"><p class="edit_user_labels">'.__('Skin').'</p>';
            $skin .= skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true).'</div>';
        }
    }
}

if ($meta) {
    $array_filters = get_filters_custom_fields_view(0, true);

    $search_custom_fields_view = '<div class="label_select"><p class="edit_user_labels">'.__('Search custom field view').' '.ui_print_help_tip(__('Load by default the selected view in custom field view'), true).'</p>';
    $search_custom_fields_view .= html_print_select(
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
    ).'</div>';
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
;
$values = [
    'Default'        => __('Default'),
    'Visual console' => __('Visual console'),
    'Event list'     => __('Event list'),
    'Group view'     => __('Group view'),
    'Tactical view'  => __('Tactical view'),
    'Alert detail'   => __('Alert detail'),
    'External link'  => __('External link'),
    'Other'          => __('Other'),
];
if (!is_metaconsole()) {
    $values['Dashboard'] = __('Dashboard');
}


$home_screen .= html_print_select(
    $values,
    'section',
    io_safe_output($user_info['section']),
    'show_data_section();',
    '',
    -1,
    true,
    false,
    false
).'</div>';


$dashboards = Manager::getDashboards(-1, -1);
$dashboards_aux = [];
if ($dashboards === false) {
    $dashboards = ['None' => 'None'];
} else {
    foreach ($dashboards as $key => $dashboard) {
        $dashboards_aux[$dashboard['id']] = $dashboard['name'];
    }
}

$home_screen .= html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true);


$layouts = visual_map_get_user_layouts($config['id_user'], true);
$layouts_aux = [];
if ($layouts === false) {
    $layouts_aux = ['None' => 'None'];
} else {
    foreach ($layouts as $layout) {
        $layouts_aux[$layout] = $layout;
    }
}

$home_screen .= html_print_select(
    $layouts_aux,
    'visual_console',
    $user_info['data_section'],
    '',
    '',
    '',
    true
);
$home_screen .= html_print_input_text(
    'data_section',
    $user_info['data_section'],
    '',
    60,
    255,
    true,
    false
);

$size_pagination = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Block size for pagination').'</p>';
$size_pagination .= html_print_input_text(
    'block_size',
    $user_info['block_size'],
    '',
    5,
    5,
    true
).'</div>';

if ($id == $config['id_user']) {
    $language .= html_print_input_hidden(
        'quick_language_change',
        1,
        true
    );
}

if (enterprise_installed() && defined('METACONSOLE')) {
    $user_info_metaconsole_access = 'only_console';
    if (isset($user_info['metaconsole_access'])) {
        $user_info_metaconsole_access = $user_info['metaconsole_access'];
    }

    // TODO review help tips on meta.
    $meta_access = '<div class="label_select"><p class="edit_user_labels">'.__('Metaconsole access').' './* ui_print_help_icon('meta_access', true). */'</p>';
    $metaconsole_accesses = [
        'basic'    => __('Basic'),
        'advanced' => __('Advanced'),
    ];
    $meta_access .= html_print_select(
        $metaconsole_accesses,
        'metaconsole_access',
        $user_info_metaconsole_access,
        '',
        '',
        -1,
        true,
        false,
        false
    ).'</div>';
}

$not_login = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Not Login').'</p>';
$not_login .= ui_print_help_tip(
    __('The user with not login set only can access to API.'),
    true
);
$not_login .= html_print_checkbox_switch(
    'not_login',
    1,
    $user_info['not_login'],
    true
).'</div>';

$session_time = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Session Time');
$session_time .= ui_print_help_tip(
    __('This is defined in minutes, If you wish a permanent session should putting -1 in this field.'),
    true
).'</p>';
$session_time .= html_print_input_text(
    'session_time',
    $user_info['session_time'],
    '',
    5,
    5,
    true.false,
    false,
    '',
    'class="input_line_small"'
).'</div>';

$event_filter_data = db_get_all_rows_sql('SELECT id_name, id_filter FROM tevent_filter');
if ($event_filter_data === false) {
    $event_filter_data = [];
}

$event_filter = [];
$event_filter[0] = __('None');
foreach ($event_filter_data as $filter) {
    $event_filter[$filter['id_filter']] = $filter['id_name'];
}

$default_event_filter = '<div class="label_select"><p class="edit_user_labels">'.__('Default event filter').'</p>';
$default_event_filter .= html_print_select(
    $event_filter,
    'default_event_filter',
    $user_info['default_event_filter'],
    '',
    '',
    __('None'),
    true,
    false,
    false
).'</div>';

$newsletter = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Disabled newsletter').'</p>';
if ($user_info['middlename'] >= 0) {
    $middlename = false;
} else {
    $middlename = true;
}

$newsletter .= html_print_checkbox_switch(
    'middlename',
    -1,
    $middlename,
    true
).'</div>';

if ($config['ehorus_user_level_conf']) {
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

$double_auth_enabled = (bool) db_get_value('id', 'tuser_double_auth', 'id_user', $id);

if ($config['double_auth_enabled'] && check_acl($config['id_user'], 0, 'PM')) {
    $double_authentication = '<div class="label_select_simple"><p class="edit_user_labels">'.__('Double authentication').'</p>';
    if (($config['2FA_all_users'] == '' && !$double_auth_enabled)
        || ($config['double_auth_enabled'] == '' && $double_auth_enabled)
        || check_acl($config['id_user'], 0, 'PM')
    ) {
        if ($new_user === false) {
            $double_authentication .= html_print_checkbox_switch('double_auth', 1, $double_auth_enabled, true);
        } else {
            $double_authentication .= ui_print_help_tip(__('User must be created before activating double authentication.'), true);
        }
    }

    // Dialog.
    $double_authentication .= '<div id="dialog-double_auth" class="invisible"><div id="dialog-double_auth-container"></div></div>';
}

if ($double_auth_enabled && $config['double_auth_enabled'] && $config['2FA_all_users'] != '') {
    $double_authentication .= html_print_button(
        __('Show information'),
        'show_info',
        false,
        'javascript:show_double_auth_info();',
        '',
        true
    );
}

if (isset($double_authentication)) {
    $double_authentication .= '</div>';
}

if ($meta) {
    enterprise_include_once('include/functions_metaconsole.php');

    $metaconsole_agents_manager = '<div class="label_select_simple" id="metaconsole_agents_manager_div"><p class="edit_user_labels">'.__('Enable agents managment').'</p>';
    $metaconsole_agents_manager .= html_print_checkbox_switch(
        'metaconsole_agents_manager',
        1,
        $user_info['metaconsole_agents_manager'],
        true
    ).'</div>';

    $metaconsole_assigned_server = '<div class="label_select" id="metaconsole_assigned_server_div"><p class="edit_user_labels">'.__('Assigned node').ui_print_help_tip(__('Server where the agents created of this user will be placed'), true).'</p>';
    $servers = metaconsole_get_servers();
    $servers_for_select = [];
    foreach ($servers as $server) {
        $servers_for_select[$server['id']] = $server['server_name'];
    }

    $metaconsole_assigned_server .= html_print_select($servers_for_select, 'metaconsole_assigned_server', $user_info['metaconsole_assigned_server'], '', '', -1, true, false, false).'</div>';

    $metaconsole_access_node = '<div class="label_select_simple" id="metaconsole_access_node_div"><p class="edit_user_labels">'.__('Enable node access').ui_print_help_tip(__('With this option enabled, the user will can access to nodes console'), true).'</p>';
    $metaconsole_access_node .= html_print_checkbox(
        'metaconsole_access_node',
        1,
        $user_info['metaconsole_access_node'],
        true
    ).'</div>';
}

echo '<form id="user_profile_form" name="user_profile_form" method="post" autocomplete="off" action="#">';


if (!$id) {
    $user_id_update_view = $user_id;
    $user_id_create = '';
} else {
    $user_id_update_view = '';
    $user_id_create = $user_id;
}

if (is_metaconsole()) {
    $access_or_pagination = $meta_access;
} else {
    $access_or_pagination = $size_pagination;
}

if ($id != '' && !$is_err) {
    $div_user_info = '<div class="edit_user_info_left">'.$avatar.$user_id_create.'</div>
    <div class="edit_user_info_right">'.$user_id_update_view.$full_name.$new_pass.$new_pass_confirm.$own_pass_confirm.$global_profile.'</div>';
} else {
    $div_user_info = '<div class="edit_user_info_left">'.$avatar.'</div>
    <div class="edit_user_info_right">'.$user_id_create.$user_id_update_view.$full_name.$new_pass.$new_pass_confirm.$global_profile.'</div>';
}

echo '<div id="user_form">
<div class="user_edit_first_row">
    <div class="edit_user_info white_box">'.$div_user_info.'</div>  
    <div class="edit_user_autorefresh white_box"><p class="bolder">Extra info</p>'.$email.$phone.$not_login.$session_time.'</div>
</div> 
<div class="user_edit_second_row white_box">
    <div class="edit_user_options">'.$language.$access_or_pagination.$skin.$home_screen.$default_event_filter.$newsletter.$double_authentication.'</div>

    <div class="edit_user_timezone">'.$timezone;
if (!is_metaconsole()) {
    echo '<div id="timezone-picker">
                        <img id="timezone-image" src="'.$local_file.'" width="'.$map_width.'" height="'.$map_height.'" usemap="#timezone-map" />
                        <img class="timezone-pin" src="include/javascript/timezonepicker/images/pin.png" class="pdd_t_4px" />
                        <map name="timezone-map" id="timezone-map">'.$area_data_timezone_polys.$area_data_timezone_rects.'</map>
                    </div>';
} else {
    echo $search_custom_fields_view.$metaconsole_agents_manager.$metaconsole_assigned_server.$metaconsole_access_node;
}

echo '</div>
</div>

<div class="user_edit_third_row white_box">
    <div class="edit_user_comments">'.$comments.'</div>
</div>';
if (!empty($ehorus)) {
    echo '<div class="user_edit_third_row white_box">'.$ehorus.'</div>';
}

echo '</div>';

echo '<div class="action-buttons w100p">';
if ($config['admin_can_add_user']) {
    html_print_csrf_hidden();
    if ($new_user) {
        html_print_input_hidden('create_user', 1);
    } else {
        html_print_input_hidden('update_user', 1);
    }
}

echo '</div>';

html_print_input_hidden('json_profile', '');

echo '</form>';


profile_print_profile_table($id);

echo '<br />';

echo '<div class="action-buttons w100p">';
if ($config['admin_can_add_user']) {
    if ($new_user) {
        html_print_submit_button(
            __('Create'),
            'crtbutton',
            false,
            'class="sub wand" form="user_profile_form"'
        );
    } else {
        html_print_submit_button(
            __('Update'),
            'uptbutton',
            false,
            'class="sub upd" form="user_profile_form"'
        );
    }
}

echo '</div>';


echo '</div>';

enterprise_hook('close_meta_frame');
$delete_image = html_print_input_image(
    'del',
    'images/cross.png',
    1,
    '',
    true,
    [
        'onclick' => 'delete_profile(event, this)',
        'class'   => 'invert_filter',
    ]
);

if (!is_metaconsole()) {
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

<script type="text/javascript">
var json_profile = $('#hidden-json_profile');
/* <![CDATA[ */
$(document).ready (function () {
    $("input#checkbox-double_auth").change(function (e) {
        e.preventDefault();
            if (this.checked) {
                show_double_auth_activation();
            } else {
                show_double_auth_deactivation();
            }
    }); 

    $('input:radio[name="is_admin"]').change(function() {
        if($('#radiobtn0002').prop('checked')) {     
            $('#metaconsole_agents_manager_div').show();
            $('#metaconsole_access_node_div').show();
            if($('#checkbox-metaconsole_agents_manager').prop('checked')) {
                $('#metaconsole_assigned_server_div').show();
            }
        }
        else {            
            $('#metaconsole_agents_manager_div').hide();
            $('#metaconsole_access_node_div').hide();
            $('#metaconsole_assigned_server_div').hide();
        }
    });
    
    $('#checkbox-metaconsole_agents_manager').change(function() { 
        if($('#checkbox-metaconsole_agents_manager').prop('checked')) {            
            $('#metaconsole_assigned_server_div').show();
        }
        else {
            $('#metaconsole_assigned_server_div').hide();
        }
    });
    
    $('input:radio[name="is_admin"]').trigger('change');
    $('#checkbox-metaconsole_agents_manager').trigger('change');
    
    show_data_section();
    $('#checkbox-ehorus_user_level_enabled').change(function () {
        switch_ehorus_conf();
    });
    $('#checkbox-ehorus_user_level_enabled').trigger('change');

    var img_delete = '<?php echo $delete_image; ?>';
    var id_user = '<?php echo io_safe_output($id); ?>';
    var is_metaconsole = '<?php echo $meta; ?>';
    var data = [];

    $('input:image[name="add"]').click(function (e) {
        e.preventDefault();
        var profile = $('#assign_profile').val();
        var profile_text = $('#assign_profile option:selected').text();
        var group = $('#assign_group').val();
        var group_text = $('#assign_group option:selected').text();
        var tags = $('#assign_tags').val();
        var tags_text = $('#assign_tags option:selected').toArray().map(item => item.text).join();
        if ( $('#checkbox-no_hierarchy').is(':checked')) {
            var hierarchy = 1;
            var hierarchy_text = '<?php echo __('yes'); ?>';
        } else {
            var hierarchy = 0;
            var hierarchy_text = '<?php echo __('no'); ?>';
        }

        if (profile === '0' || group === '-1') {
            alert('<?php echo __('please select profile and group'); ?>');
            return;
        }

        if (id_user === '') {
            let new_json = `{"profile":${profile},"group":${group},"tags":[${tags}],"hierarchy":${hierarchy}}`;
            data.push(new_json);
            json_profile.val('['+data+']');
            $('#table_profiles tr:last').before(
                `<tr>
                    <td>${profile_text}</td>
                    <td>${group_text}</td>
                    <td>${tags_text}</td>
                    <td>${hierarchy_text}</td>
                    <td>${img_delete}</td>
                </tr>`
            );
        } else {
            this.form.submit();
        }
    });

    $('input:image[name="del"]').click(function (e) {
        e.preventDefault();
        var rows = $("#table_profiles tr").length;
        if ((is_metaconsole === '1' && rows <= 4) || (is_metaconsole === '' && rows <= 3)) {
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
        jQuery.ajax ({
            data: params.join ("&"),
            type: 'POST',
            url: action="<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            success: function (data) {
                row.remove();
                var rows = $("#table_profiles tr").length;
                if ((is_metaconsole === '1' && rows <= 3) || (is_metaconsole === '' && rows <= 2)) {
                    window.location.replace("<?php echo ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure=0', false, false, false); ?>");
                }
            }
        });
    });

    function checkProfiles(e) {
        e.preventDefault();
        if ($('input[name="is_admin"]:checked').val() == 1) {
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

    $('#submit-crtbutton').click(function (e) {
        checkProfiles(e);
    });

    $('#submit-uptbutton').click(function (e) {
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
    delete test[position-1];
    json_profile.val(JSON.stringify(test));

}

function show_data_section () {
    section = $("#section").val();
    
    switch (section) {
        case <?php echo "'".'Dashboard'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Visual console'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "");
            break;
        case <?php echo "'".'Event list'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Group view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Tactical view'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Alert detail'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'External link'."'"; ?>:
            $("#text-data_section").css("display", "");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Other'."'"; ?>:
            $("#text-data_section").css("display", "");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
        case <?php echo "'".'Default'."'"; ?>:
            $("#text-data_section").css("display", "none");
            $("#dashboard").css("display", "none");
            $("#visual_console").css("display", "none");
            break;
    }
}

function switch_ehorus_conf()
{
    if(!$('#checkbox-ehorus_user_level_enabled').prop('checked')) 
    {
        $(".user_edit_ehorus_outer").hide();

    }else
    {
        $(".user_edit_ehorus_outer").show();
    }


}

function show_double_auth_info () {
    var userID = '<?php echo io_safe_output($id); ?>';

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    $dialogContainer.html($loadingSpinner);
console.log(userID);
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
    var userID = '<?php echo io_safe_output($id); ?>';

    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");
    // Uncheck until completed successfully.
    $("input#checkbox-double_auth").prop( "checked", false );

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
            title: "<?php echo __('Double autentication activation'); ?>",
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

function show_double_auth_deactivation () {
    var userID = '<?php echo io_safe_output($id); ?>';
    var $loadingSpinner = $("<img src=\"<?php echo $config['homeurl']; ?>/images/spinner.gif\" />");
    var $dialogContainer = $("div#dialog-double_auth-container");

    var message = "<p><?php echo __('Are you sure?').'<br>'.__('The double authentication will be deactivated'); ?></p>";
    var $button = $("<input type=\"button\" value=\"<?php echo __('Deactivate'); ?>\" />");
    // Prevent switch deactivaction until proceess is done
    $("input#checkbox-double_auth").prop( "checked", true );


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
                console.log(data);
                if (data === -1) {
                    $dialogContainer.html("<?php echo '<b><div class=\"red\">'.__('Authentication error').'</div></b>'; ?>");
                }
                else if (data) {
                    $dialogContainer.html("<?php echo '<b><div class=\"green\">'.__('The double autentication was deactivated successfully').'</div></b>'; ?>");
                    $("input#checkbox-double_auth").prop( "checked", false );
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
            title: "<?php echo __('Double autentication activation'); ?>",
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
