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
enterprise_include_once('include/functions_dashboard.php');

// Add the columns for the enterprise Pandora edition.
$enterprise_include = false;
if (ENTERPRISE_NOT_HOOK !== enterprise_include('include/functions_policies.php')) {
    $enterprise_include = true;
}

if ($enterprise_include) {
    enterprise_include_once('meta/include/functions_users_meta.php');
}

// This defines the working user. Beware with this, old code get confusses
// and operates with current logged user (dangerous).
$id = get_parameter('id', get_parameter('id_user', ''));
// ID given as parameter
$pure = get_parameter('pure', 0);

$user_info = get_user_info($id);

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access User Management'
    );
    include 'general/noaccess.php';

    return;
}

/*
 * Disabled at the moment.
    if (!check_referer()) {
    require ("general/noaccess.php");

    return;
    }
 */

$tab = get_parameter('tab', 'user');

// Header
if ($meta) {
    user_meta_print_header();
    $sec = 'advanced';
} else {
    $buttons = [
        'user'    => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image('images/gm_users.png', true, ['title' => __('User management')]).'</a>',
        ],
        'profile' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/profile_list&tab=profile&pure='.$pure.'">'.html_print_image('images/profiles.png', true, ['title' => __('Profile management')]).'</a>',
        ],
    ];

    $buttons[$tab]['active'] = true;

    ui_print_page_header(
        __('User detail editor'),
        'images/gm_users.png',
        false,
        'profile_tab',
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
$delete_profile = (bool) get_parameter('delete_profile');
$update_user = (bool) get_parameter('update_user');
$status = get_parameter('status', -1);

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
    $values['comments'] = (string) get_parameter('comments');
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
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if ($password_new == '') {
        ui_print_error_message(__('Passwords cannot be empty'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else if ($password_new != $password_confirm) {
        ui_print_error_message(__('Passwords didn\'t match'));
        $user_info = $values;
        $password_new = '';
        $password_confirm = '';
        $new_user = true;
    } else {
        $have_number = false;
        $have_simbols = false;
        if ($config['enable_pass_policy']) {
            if ($config['pass_needs_numbers']) {
                $nums = preg_match('/([[:alpha:]])*(\d)+(\w)*/', $password_confirm);
                if ($nums == 0) {
                    ui_print_error_message(__('Password must contain numbers'));
                    $user_info = $values;
                    $password_new = '';
                    $password_confirm = '';
                    $new_user = true;
                } else {
                    $have_number = true;
                }
            }

            if ($config['pass_needs_symbols']) {
                $symbols = preg_match('/(\w)*(\W)+(\w)*/', $password_confirm);
                if ($symbols == 0) {
                    ui_print_error_message(__('Password must contain symbols'));
                    $user_info = $values;
                    $password_new = '';
                    $password_confirm = '';
                    $new_user = true;
                } else {
                    $have_simbols = true;
                }
            }

            if ($config['pass_needs_symbols'] && $config['pass_needs_numbers']) {
                if ($have_number && $have_simbols) {
                    $result = create_user($id, $password_new, $values);
                }
            } else if ($config['pass_needs_symbols'] && !$config['pass_needs_numbers']) {
                if ($have_simbols) {
                    $result = create_user($id, $password_new, $values);
                }
            } else if (!$config['pass_needs_symbols'] && $config['pass_needs_numbers']) {
                if ($have_number) {
                    $result = create_user($id, $password_new, $values);
                }
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
            'Created user '.io_safe_input($id),
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
    $values['comments'] = (string) get_parameter('comments');
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
        if ($password_new != '') {
            if ($password_confirm == $password_new) {
                if ((!$values['is_admin'] || $config['enable_pass_policy_admin']) && $config['enable_pass_policy']) {
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
                'Updated user '.io_safe_input($id),
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

if ($add_profile) {
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
        'Added profile for user '.io_safe_input($id2),
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

if ($delete_profile) {
    $id2 = (string) get_parameter('id_user');
    $id_up = (int) get_parameter('id_user_profile');

    $perfilUser = db_get_row('tusuario_perfil', 'id_up', $id_up);
    $id_perfil = $perfilUser['id_perfil'];
    $perfil = db_get_row('tperfil', 'id_perfil', $id_perfil);

    db_pandora_audit(
        'User management',
        'Deleted profile for user '.io_safe_input($id2),
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
}

if ($values) {
    $user_info = $values;
}

$table = new stdClass();
$table->id = 'user_configuration_table';
$table->width = '100%';
$table->class = 'databox filters';
if (defined('METACONSOLE')) {
    if ($id) {
        $table->head[0] = __('Update User');
    } else {
        $table->head[0] = __('Create User');
    }

    $table->head_colspan[0] = 5;
    $table->headstyle[0] = 'text-align: center';
}

$table->data = [];
$table->colspan = [];
$table->size = [];
$table->size[0] = '35%';
$table->size[1] = '65%';
$table->style = [];
$table->style[0] = 'font-weight: bold;';

$table->data[0][0] = __('User ID');
$table->data[0][1] = html_print_input_text_extended(
    'id_user',
    $id,
    '',
    '',
    20,
    60,
    !$new_user || $view_mode,
    '',
    '',
    true
);

$table->data[1][0] = __('Full (display) name');
$table->data[1][1] = html_print_input_text_extended(
    'fullname',
    $user_info['fullname'],
    '',
    '',
    30,
    125,
    $view_mode,
    '',
    '',
    true
);

$table->data[2][0] = __('Language');
$table->data[2][1] = html_print_select_from_sql(
    'SELECT id_language, name FROM tlanguage',
    'language',
    $user_info['language'],
    '',
    __('Default'),
    'default',
    true
);

$table->data[3][0] = __('Timezone');
$table->data[3][1] = html_print_timezone_select('timezone', $user_info['timezone']);

if ($config['user_can_update_password']) {
    $table->data[4][0] = __('Password');
    $table->data[4][1] = html_print_input_text_extended(
        'password_new',
        '',
        '',
        '',
        15,
        45,
        $view_mode,
        '',
        '',
        true,
        true
    );
    $table->data[5][0] = __('Password confirmation');
    $table->data[5][1] = html_print_input_text_extended(
        'password_confirm',
        '',
        '',
        '',
        15,
        45,
        $view_mode,
        '',
        '',
        true,
        true
    );
}

$own_info = get_user_info($config['id_user']);
if ($config['admin_can_make_admin']) {
    $table->data[6][0] = __('Global Profile');
    $table->data[6][1] = '';
    if ($own_info['is_admin'] || $user_info['is_admin']) {
        $table->data[6][1] = html_print_radio_button('is_admin', 1, '', $user_info['is_admin'], true);
        $table->data[6][1] .= __('Administrator');
        $table->data[6][1] .= ui_print_help_tip(__('This user has permissions to manage all. An admin user should not requiere additional group permissions, except for using Enterprise ACL.'), true);
        $table->data[6][1] .= '<br />';
    }

    $table->data[6][1] .= html_print_radio_button('is_admin', 0, '', $user_info['is_admin'], true);
    $table->data[6][1] .= __('Standard User');
    $table->data[6][1] .= ui_print_help_tip(__('This user has separated permissions to view data in his group agents, create incidents belong to his groups, add notes in another incidents, create personal assignments or reviews and other tasks, on different profiles'), true);
}

$table->data[7][0] = __('E-mail');
$table->data[7][1] = html_print_input_text_extended(
    'email',
    $user_info['email'],
    '',
    '',
    20,
    100,
    $view_mode,
    '',
    '',
    true
);

$table->data[8][0] = __('Phone number');
$table->data[8][1] = html_print_input_text_extended(
    'phone',
    $user_info['phone'],
    '',
    '',
    10,
    30,
    $view_mode,
    '',
    '',
    true
);

$table->data[9][0] = __('Comments');
$table->data[9][1] = html_print_textarea(
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
            $table->data[10][0] = __('Skin');
            $table->data[10][1] = skins_print_select($id_usr, 'skin', $user_info['id_skin'], '', __('None'), 0, true);
        }
    }
}

if ($meta) {
    $array_filters = get_filters_custom_fields_view(0, true);
    $table->data[11][0] = __('Search custom field view').' '.ui_print_help_tip(__('Load by default the selected view in custom field view'), true);
    $table->data[11][1] = html_print_select(
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
    );
}

$values = [
    -1 => __('Use global conf'),
    1  => __('Yes'),
    0  => __('No'),
];

$table->data[12][0] = __('Home screen').ui_print_help_tip(__('User can customize the home page. By default, will display \'Agent Detail\'. Example: Select \'Other\' and type index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=1 to show agent detail view'), true);
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
if (enterprise_installed() && !is_metaconsole()) {
    $values['Dashboard'] = __('Dashboard');
}


$table->data[12][1] = html_print_select($values, 'section', io_safe_output($user_info['section']), 'show_data_section();', '', -1, true, false, false);

if (enterprise_installed()) {
    $dashboards = dashboard_get_dashboards();
    $dashboards_aux = [];
    if ($dashboards === false) {
        $dashboards = ['None' => 'None'];
    } else {
        foreach ($dashboards as $key => $dashboard) {
            $dashboards_aux[$dashboard['name']] = $dashboard['name'];
        }
    }

    $table->data[12][1] .= html_print_select($dashboards_aux, 'dashboard', $user_info['data_section'], '', '', '', true);
}

$layouts = visual_map_get_user_layouts($config['id_user'], true);
$layouts_aux = [];
if ($layouts === false) {
    $layouts_aux = ['None' => 'None'];
} else {
    foreach ($layouts as $layout) {
        $layouts_aux[$layout] = $layout;
    }
}

$table->data[12][1] .= html_print_select($layouts_aux, 'visual_console', $user_info['data_section'], '', '', '', true);
$table->data[12][1] .= html_print_input_text('data_section', $user_info['data_section'], '', 60, 255, true, false);

$table->data[13][0] = __('Block size for pagination');
$table->data[13][1] = html_print_input_text('block_size', $user_info['block_size'], '', 5, 5, true);

if ($id == $config['id_user']) {
    $table->data[13][1] .= html_print_input_hidden('quick_language_change', 1, true);
}

if (enterprise_installed() && defined('METACONSOLE')) {
    $user_info_metaconsole_access = 'only_console';
    if (isset($user_info['metaconsole_access'])) {
        $user_info_metaconsole_access = $user_info['metaconsole_access'];
    }

    $table->data[13][0] = __('Metaconsole access').' '.ui_print_help_icon('meta_access', true);
    $metaconsole_accesses = [
        'basic'    => __('Basic'),
        'advanced' => __('Advanced'),
    ];
    $table->data[13][1] = html_print_select(
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

$table->data[14][0] = __('Not Login');
$table->data[14][0] .= ui_print_help_tip(__('The user with not login set only can access to API.'), true);
$table->data[14][1] = html_print_checkbox('not_login', 1, $user_info['not_login'], true);

$table->data[15][0] = __('Session Time');
$table->data[15][0] .= ui_print_help_tip(__('This is defined in minutes, If you wish a permanent session should putting -1 in this field.'), true);
$table->data[15][1] = html_print_input_text('session_time', $user_info['session_time'], '', 5, 5, true);

$event_filter_data = db_get_all_rows_sql('SELECT id_name, id_filter FROM tevent_filter');
if ($event_filter_data === false) {
    $event_filter_data = [];
}

$event_filter = [];
$event_filter[0] = __('None');
foreach ($event_filter_data as $filter) {
    $event_filter[$filter['id_filter']] = $filter['id_name'];
}

$table->data[16][0] = __('Default event filter');
$table->data[16][1] = html_print_select($event_filter, 'default_event_filter', $user_info['default_event_filter'], '', '', __('None'), true, false, false);

$table->data[17][0] = __('Disabled newsletter');
if ($user_info['middlename'] >= 0) {
    $middlename = false;
} else {
    $middlename = true;
}

$table->data[17][1] = html_print_checkbox(
    'middlename',
    -1,
    $middlename,
    true
);

if ($config['ehorus_user_level_conf']) {
    $table->data[18][0] = __('eHorus user acces enabled');
    $table->data[18][1] = html_print_checkbox('ehorus_user_level_enabled', 1, $user_info['ehorus_user_level_enabled'], true);
    $table->data[19][0] = __('eHorus user');
    $table->data[20][0] = __('eHorus password');
    $table->data[19][1] = html_print_input_text('ehorus_user_level_user', $user_info['ehorus_user_level_user'], '', 15, 45, true);
    $table->data[20][1] = html_print_input_password('ehorus_user_level_pass', io_output_password($user_info['ehorus_user_level_pass']), '', 15, 45, true);
}


if ($meta) {
    enterprise_include_once('include/functions_metaconsole.php');

    $data = [];
    $data[0] = __('Enable agents managment');
    $data[1] = html_print_checkbox('metaconsole_agents_manager', 1, $user_info['metaconsole_agents_manager'], true);
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data['metaconsole_agents_manager'] = $data;

    $data = [];
    $data[0] = __('Assigned node').ui_print_help_tip(__('Server where the agents created of this user will be placed'), true);
    $servers = metaconsole_get_servers();
    $servers_for_select = [];
    foreach ($servers as $server) {
        $servers_for_select[$server['id']] = $server['server_name'];
    }

    $data[1] = html_print_select($servers_for_select, 'metaconsole_assigned_server', $user_info['metaconsole_assigned_server'], '', '', -1, true, false, false);
    $table->rowclass[] = '';
    $table->rowstyle[] = 'font-weight: bold;';
    $table->data['metaconsole_assigned_server'] = $data;

    $data = [];
    $data[0] = __('Enable node access').ui_print_help_tip(__('With this option enabled, the user will can access to nodes console'), true);
    $data[2] = html_print_checkbox('metaconsole_access_node', 1, $user_info['metaconsole_access_node'], true);
    $table->rowclass[] = '';
    $table->rowstyle[] = '';
    $table->data['metaconsole_access_node'] = $data;
}

echo '<form method="post" autocomplete="off">';

html_print_table($table);

echo '<div style="width: '.$table->width.'" class="action-buttons">';
if ($config['admin_can_add_user']) {
    html_print_csrf_hidden();
    if ($new_user) {
        html_print_input_hidden('create_user', 1);
        html_print_submit_button(__('Create'), 'crtbutton', false, 'class="sub wand"');
    } else {
        html_print_input_hidden('update_user', 1);
        html_print_submit_button(__('Update'), 'uptbutton', false, 'class="sub upd"');
    }
}

echo '</div>';
echo '</form>';
echo '<br />';

// Don't show anything else if we're creating an user
if (!empty($id) && !$new_user) {
    profile_print_profile_table($id);
}

enterprise_hook('close_meta_frame');

?>

<script type="text/javascript">
/* <![CDATA[ */
$(document).ready (function () {
    $('input:radio[name="is_admin"]').change(function() {
        if($('#radiobtn0002').prop('checked')) {            
            $('#user_configuration_table-metaconsole_agents_manager').show();
            $('#user_configuration_table-metaconsole_access_node').show();
            if($('#checkbox-metaconsole_agents_manager').prop('checked')) {            
                $('#user_configuration_table-metaconsole_assigned_server').show();
            }
        }
        else {            
            $('#user_configuration_table-metaconsole_agents_manager').hide();
            $('#user_configuration_table-metaconsole_access_node').hide();
            $('#user_configuration_table-metaconsole_assigned_server').hide();
        }
    });
    
    $('#checkbox-metaconsole_agents_manager').change(function() {
        if($('#checkbox-metaconsole_agents_manager').prop('checked')) {            
            $('#user_configuration_table-metaconsole_assigned_server').show();
        }
        else {            
            $('#user_configuration_table-metaconsole_assigned_server').hide();
        }
    });
    
    $('input:radio[name="is_admin"]').trigger('change');
    $('#checkbox-metaconsole_agents_manager').trigger('change');
    
    show_data_section();
    $('#checkbox-ehorus_user_level_enabled').change(function () {
        switch_ehorus_conf();
    });
    $('#checkbox-ehorus_user_level_enabled').trigger('change');

});

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
        $("#user_configuration_table-18").hide();
        $("#user_configuration_table-19").hide();

    }else
    {
        $("#user_configuration_table-18").show();
        $("#user_configuration_table-19").show()   
    }


}

/* ]]> */
</script>
