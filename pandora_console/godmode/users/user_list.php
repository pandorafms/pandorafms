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

enterprise_hook('open_meta_frame');

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('meta/include/functions_users_meta.php');

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access User Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_ajax()) {
    $method = get_parameter('method');
    $group_id = get_parameter('group_id');
    $group_recursion = (bool) get_parameter('group_recursion', 0);
    $return_all = false;

    if ($group_id == -1) {
        $sql = 'SELECT tusuario.id_user FROM tusuario 
                        LEFT OUTER JOIN tusuario_perfil
                        ON tusuario.id_user = tusuario_perfil.id_usuario
                        WHERE tusuario_perfil.id_usuario IS NULL';

        $users = io_safe_output(db_get_all_rows_sql($sql));

        foreach ($users as $key => $user) {
            $ret_user[$user['id_user']] = $user['id_user'];
        }

        echo json_encode($ret_user);
        return;
    }

    if ($group_id == 0) {
        $users = io_safe_output(db_get_all_rows_filter('tusuario', [], 'id_user'));
        foreach ($users as $key => $user) {
            $ret_user[$user['id_user']] = $user['id_user'];
        }

        echo json_encode($ret_user);
        return;
    }

    if ($method === 'get_users_by_group') {
        if ($group_recursion === true) {
            $group_id = groups_get_children_ids($group_id);
        }

        $users_id = io_safe_output(users_get_user_users(false, 'AR', false, null, $group_id));
        foreach ($users_id as $key => $user_id) {
            $ret_id[$user_id] = $user_id;
        }

        echo json_encode($ret_id);
        return;
    }
}

$sortField = get_parameter('sort_field');
$sort = get_parameter('sort', 'none');
$tab = get_parameter('tab', 'user');
$pure = get_parameter('pure', 0);

$selected = true;
$selectUserIDUp = false;
$selectUserIDDown = false;
$selectFullnameUp = false;
$selectFullnameDown = false;
$selectLastConnectUp = false;
$selectLastConnectDown = false;
$order = null;

switch ($sortField) {
    case 'id_user':
        switch ($sort) {
            case 'up':
                $selectUserIDUp = $selected;
                $order = [
                    'field' => 'id_user',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectUserIDDown = $selected;
                $order = [
                    'field' => 'id_user',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'fullname':
        switch ($sort) {
            case 'up':
                $selectFullnameUp = $selected;
                $order = [
                    'field' => 'fullname',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectFullnameDown = $selected;
                $order = [
                    'field' => 'fullname',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    case 'last_connect':
        switch ($sort) {
            case 'up':
                $selectLastConnectUp = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'ASC',
                ];
            break;

            case 'down':
                $selectLastConnectDown = $selected;
                $order = [
                    'field' => 'last_connect',
                    'order' => 'DESC',
                ];
            break;
        }
    break;

    default:
        $selectUserIDUp = $selected;
        $selectUserIDDown = false;
        $selectFullnameUp = false;
        $selectFullnameDown = false;
        $selectLastConnectUp = false;
        $selectLastConnectDown = false;
        $order = [
            'field' => 'id_user',
            'order' => 'ASC',
        ];
    break;
}

$buttons[$tab]['active'] = true;

// Header
if (defined('METACONSOLE')) {
    user_meta_print_header();
    $sec = 'advanced';
} else {
    if (check_acl($config['id_user'], 0, 'PM')) {
        $buttons = [
            'user'    => [
                'active' => false,
                'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image(
                    'images/gm_users.png',
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
    } else {
        $buttons = [
            'user' => [
                'active' => false,
                'text'   => '<a href="index.php?sec=gusuarios&sec2=godmode/users/user_list&tab=user&pure='.$pure.'">'.html_print_image(
                    'images/gm_users.png',
                    true,
                    [
                        'title' => __('User management'),
                        'class' => 'invert_filter',
                    ]
                ).'</a>',
            ],
        ];
    }

    $buttons[$tab]['active'] = true;

    ui_print_page_header(
        __('User management').' &raquo; '.__('Users defined on %s', get_product_name()),
        'images/gm_users.png',
        false,
        '',
        true,
        $buttons
    );

    $sec = 'gusuarios';
}


$disable_user = get_parameter('disable_user', false);

if (isset($_GET['user_del'])) {
    // delete user
    $id_user = get_parameter('delete_user', 0);
    // Only allow delete user if is not the actual user
    if ($id_user != $config['id_user']) {
        $user_row = users_get_user_by_id($id_user);

        $result = delete_user($id_user);

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
        if (defined('METACONSOLE') && isset($_GET['delete_all'])) {
            $servers = metaconsole_get_servers();
            foreach ($servers as $server) {
                // Connect to the remote console.
                if (metaconsole_connect($server) === NOERR) {
                    // Delete the user
                    $result = delete_user($id_user);
                    if ($result) {
                        db_pandora_audit(
                            'User management',
                            __('Deleted user %s from metaconsole', io_safe_input($id_user))
                        );
                    }

                    // Restore the db connection.
                    metaconsole_restore_db();
                }

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
    } else {
        ui_print_error_message(__('There was a problem deleting the user'));
    }
} else if (isset($_GET['profile_del'])) {
    // delete profile
    $id_profile = (int) get_parameter_post('delete_profile');
    $result = profile_delete_profile($id_profile);
    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('There was a problem deleting the profile')
    );
} else if ($disable_user !== false) {
    // disable_user
    $id_user = get_parameter('id', 0);

    if ($id_user !== 0) {
        $result = users_disable($id_user, $disable_user);
    } else {
        $result = false;
    }

    if ($disable_user == 1) {
        ui_print_result_message(
            $result,
            __('Successfully disabled'),
            __('There was a problem disabling user')
        );
    } else {
        ui_print_result_message(
            $result,
            __('Successfully enabled'),
            __('There was a problem enabling user')
        );
    }
}

$filter_group = (int) get_parameter('filter_group', 0);
$filter_search = get_parameter('filter_search', '');
$search = (bool) get_parameter('search', false);

if (($filter_group == 0) && ($filter_search == '')) {
    $search = false;
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->rowclass[0] = '';
$table->data[0][0] = '<b>'.__('Group').'</b>';
$table->data[0][1] = html_print_select_groups(
    false,
    'AR',
    true,
    'filter_group',
    $filter_group,
    '',
    '',
    0,
    true
);
$table->data[0][2] = '<b>'.__('Search').'</b>'.ui_print_help_tip(__('Search by username, fullname or email'), true);
$table->data[0][3] = html_print_input_text(
    'filter_search',
    $filter_search,
    __('Search by username, fullname or email'),
    30,
    90,
    true
);
$table->data[0][4] = html_print_submit_button(
    __('Search'),
    'search',
    false,
    ['class' => 'sub search'],
    true
);


if (defined('METACONSOLE')) {
    $table->width = '96%';
    $form_filter = "<form class='filters_form' method='post'>";
    $form_filter .= html_print_table($table, true);
    $form_filter .= '</form>';
    ui_toggle($form_filter, __('Show Options'));
} else {
    $form_filter = "<form method='post'>";
    $form_filter .= html_print_table($table, true);
    $form_filter .= '</form>';
    ui_toggle(
        $form_filter,
        __('Users control filter'),
        __('Toggle filter(s)'),
        '',
        !$search
    );
}

// Urls to sort the table.
$url_up_id = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=id_user&sort=up&pure='.$pure;
$url_down_id = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=id_user&sort=down&pure='.$pure;
$url_up_name = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=fullname&sort=up&pure='.$pure;
$url_down_name = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=fullname&sort=down&pure='.$pure;
$url_up_last = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=last_connect&sort=up&pure='.$pure;
$url_down_last = '?sec='.$sec.'&sec2=godmode/users/user_list&sort_field=last_connect&sort=down&pure='.$pure;


$table = new stdClass();
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->width = '100%';
$table->class = 'info_table';

$table->head = [];
$table->data = [];
$table->align = [];
$table->size = [];
$table->valign = [];

$table->head[0] = __('User ID').ui_get_sorting_arrows($url_up_id, $url_down_id, $selectUserIDUp, $selectUserIDDown);
$table->head[1] = __('Name').ui_get_sorting_arrows($url_up_name, $url_down_name, $selectFullnameUp, $selectFullnameDown);
$table->head[2] = __('Last contact').ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastConnectUp, $selectLastConnectDown);

$table->head[3] = __('Admin');
$table->head[4] = __('Profile / Group');
$table->head[5] = __('Description');
$table->head[6] = '<span title="Operations">'.__('Op.').'</span>';
if (!defined('METACONSOLE')) {
    $table->align[2] = '';
    $table->size[2] = '150px';
}

$table->align[3] = 'left';

if (defined('METACONSOLE')) {
    $table->size[6] = '110px';
} else {
    $table->size[6] = '85px';
}

if (!defined('METACONSOLE')) {
    $table->valign[0] = 'top';
    $table->valign[1] = 'top';
    $table->valign[2] = 'top';
    $table->valign[3] = 'top';
    $table->valign[4] = 'top';
    $table->valign[5] = 'top';
    $table->valign[6] = 'top';
}

$info1 = [];

$user_is_admin = users_is_admin();

if ($user_is_admin) {
    $info1 = get_users($order);
} else {
    $group_um = users_get_groups_UM($config['id_user']);
    // 0 is the group 'all'.
    if (isset($group_um[0])) {
        $info1 = get_users($order);
    } else {
        foreach ($group_um as $group => $value) {
            $info1 = array_merge($info1, users_get_users_by_group($group, $value));
        }
    }
}

// Filter the users
if ($search) {
    foreach ($info1 as $iterator => $user_info) {
        $found = false;

        if (!empty($filter_search)) {
            if (preg_match('/.*'.strtolower($filter_search).'.*/', strtolower($user_info['fullname'])) != 0) {
                $found = true;
            }

            if (preg_match('/.*'.strtolower($filter_search).'.*/', strtolower($user_info['id_user'])) != 0) {
                $found = true;
            }

            if (preg_match('/.*'.$filter_search.'.*/', $user_info['email']) != 0) {
                $found = true;
            }
        }

        if ($filter_group != 0) {
            $groups = users_get_groups(
                $user_info['id_user'],
                'AR',
                $user_info['is_admin']
            );

            $id_groups = array_keys($groups);

            if (array_search($filter_group, $id_groups) !== false) {
                $found = true;
            }
        }

        if (!$found) {
            unset($info1[$iterator]);
        }
    }
}

$info = $info1;

// Prepare pagination
ui_pagination(count($info));

$offset = (int) get_parameter('offset');
$limit = (int) $config['block_size'];

$rowPair = true;
$iterator = 0;
$cont = 0;
foreach ($info as $user_id => $user_info) {
    if (!$user_is_admin && $user_info['is_admin']) {
        // If user is not admin then don't display admin users.
        continue;
    }

    // User profiles.
    if ($user_is_admin || $user_id == $config['id_user'] || isset($group_um[0])) {
        $user_profiles = db_get_all_rows_field_filter('tusuario_perfil', 'id_usuario', $user_id);
    } else {
        $user_profiles_aux = users_get_user_profile($user_id);
        $user_profiles = [];
        foreach ($group_um as $key => $value) {
            if (isset($user_profiles_aux[$key])) {
                $user_profiles[$key] = $user_profiles_aux[$key];
                unset($user_profiles_aux[$key]);
            }
        }

        if (!empty($user_profiles_aux)) {
            $user_info['not_delete'] = 1;
        }

        if ($user_profiles == false) {
            continue;
        }
    }

    $cont++;

    // Manual pagination due the complicated process of the ACL data
    if ($cont <= $offset) {
        continue;
    }

    if ($cont > ($limit + $offset)) {
        break;
    }


    if ($rowPair) {
        $table->rowclass[$iterator] = 'rowPair';
    } else {
        $table->rowclass[$iterator] = 'rowOdd';
    }

    $rowPair = !$rowPair;
    if ($user_info['disabled']) {
        $table->rowclass[$iterator] .= ' disabled_row_user';
    }

    $iterator++;

    if ($user_is_admin || $config['id_user'] == $user_info['id_user'] || (!$user_info['is_admin'] && (!isset($user_info['edit']) || isset($group_um[0]) || (isset($user_info['edit']) && $user_info['edit'])))) {
        $data[0] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'&amp;id='.$user_id.'">'.$user_id.'</a>';
    } else {
        $data[0] = $user_id;
    }

    $data[1] = '<ul class="user_list_ul">';
    $data[1] .= '<li>'.$user_info['fullname'].'</li>';
    $data[1] .= '<li>'.$user_info['phone'].'</li>';
    $data[1] .= '<li>'.$user_info['email'].'</li>';
    $data[1] .= '</ul>';
    $data[2] = ui_print_timestamp($user_info['last_connect'], true);

    if ($user_info['is_admin']) {
        $data[3] = html_print_image(
            'images/user_suit.png',
            true,
            [
                'alt'   => __('Admin'),
                'title' => __('Administrator'),
                'class' => 'invert_filter',
            ]
        ).'&nbsp;';
    } else {
        $data[3] = '';
    }

    $data[4] = '';
    if ($user_profiles !== false) {
        $total_profile = 0;

            $data[4] .= '<div class="text_end">';
        foreach ($user_profiles as $row) {
            if ($total_profile <= 5) {
                $data[4] .= "<div class='float-left'>";
                $data[4] .= profile_get_name($row['id_perfil']);
                $data[4] .= ' / </div>';
                $data[4] .= "<div class='float-left pdd_l_5px'>";
                $data[4] .= groups_get_name($row['id_grupo'], true);
                $data[4] .= '</div>';

                if ($total_profile == 0 && count($user_profiles) >= 5) {
                    $data[4] .= '<span onclick="showGroups()" class="pdd_l_15px">
                    '.html_print_image(
                        'images/zoom.png',
                        true,
                        [
                            'title' => __('Show'),
                            'class' => 'invert_filter',
                        ]
                    ).'</span>';
                }

                $data[4] .= '<br />';
                $data[4] .= '<br />';
                $data[4] .= '</div>';
            } else {
                $data[4] .= "<div id='groups_list' class='invisible'>";
                $data[4] .= '<div >';
                $data[4] .= profile_get_name($row['id_perfil']);
                $data[4] .= ' / '.groups_get_name($row['id_grupo'], true).'</div>';
                $data[4] .= '<br/>';
            }

            $total_profile++;
        }

        if (isset($user_info['not_delete'])) {
            $data[4] .= __('Other profiles are also assigned.').ui_print_help_tip(__('Other profiles you cannot manage are also assigned. These profiles are not shown. You cannot enable/disable or delete this user.'), true);
        }

        $data[4] .= '</div>';
    } else {
        $data[4] .= __('The user doesn\'t have any assigned profile/group');
    }

    $data[5] = ui_print_string_substr($user_info['comments'], 24, true);

    $table->cellclass[][6] = 'action_buttons';
    $data[6] = '';
    if ($user_is_admin || $config['id_user'] == $user_info['id_user'] || isset($group_um[0]) || (!$user_info['is_admin'] && (!isset($user_info['edit']) || (isset($user_info['edit']) && $user_info['edit'])))) {
        if (!isset($user_info['not_delete'])) {
            if ($user_info['disabled'] == 0) {
                $data[6] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;disable_user=1&pure='.$pure.'&amp;id='.$user_info['id_user'].'">'.html_print_image('images/lightbulb.png', true, ['title' => __('Disable'), 'class' => 'invert_filter']).'</a>';
            } else {
                $data[6] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;disable_user=0&pure='.$pure.'&amp;id='.$user_info['id_user'].'">'.html_print_image('images/lightbulb_off.png', true, ['title' => __('Enable')]).'</a>';
            }
        }

        $data[6] .= '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'&amp;id='.$user_id.'">'.html_print_image('images/config.png', true, ['title' => __('Edit'), 'class' => 'invert_filter']).'</a>';
        if ($config['admin_can_delete_user'] && $user_info['id_user'] != $config['id_user'] && !isset($user_info['not_delete'])) {
            $data[6] .= "<a href='index.php?sec=".$sec.'&sec2=godmode/users/user_list&user_del=1&pure='.$pure.'&delete_user='.$user_info['id_user']."'>".html_print_image('images/cross.png', true, ['class' => 'invert_filter', 'title' => __('Delete'), 'onclick' => "if (! confirm ('".__('Deleting User').' '.$user_info['id_user'].'. '.__('Are you sure?')."')) return false"]).'</a>';
            if (defined('METACONSOLE')) {
                $data[6] .= "<a href='index.php?sec=".$sec.'&sec2=godmode/users/user_list&user_del=1&pure='.$pure.'&delete_user='.$user_info['id_user']."&delete_all=1'>".html_print_image('images/cross_double.png', true, ['class' => 'invert_filter', 'title' => __('Delete from all consoles'), 'onclick' => "if (! confirm ('".__('Deleting User %s from all consoles', $user_info['id_user']).'. '.__('Are you sure?')."')) return false"]).'</a>';
            }
        } else {
            $data[6] .= '';
            // Delete button not in this mode
        }
    }

    array_push($table->data, $data);
}

html_print_table($table);
ui_pagination(count($info), false, 0, 0, false, 'offset', true, 'pagination-bottom');

echo '<div style="width: '.$table->width.'" class="action-buttons">';
unset($table);
if ($config['admin_can_add_user'] !== false) {
    echo '<form method="post" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'">';
    html_print_input_hidden('new_user', 1);
    html_print_submit_button(__('Create user'), 'crt', false, 'class="sub next"');
    echo '</form>';
} else {
    echo '<i>'.__("The current authentication scheme doesn't support creating users on %s", get_product_name()).'</i>';
}

echo '</div>';

enterprise_hook('close_meta_frame');

echo '<script type="text/javascript">
function showGroups(){
    var groups_list = document.getElementById("groups_list");

    if(groups_list.style.display == "none"){
        document.querySelectorAll("[id=groups_list]").forEach(element=> 
        element.style.display = "block");
    }else{
        document.querySelectorAll("[id=groups_list]").forEach(element=> 
        element.style.display = "none");
    };
}
</script>';
