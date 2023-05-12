<?php
/**
 * Users.
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

require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';
enterprise_include_once('include/functions_metaconsole.php');
enterprise_include_once('meta/include/functions_users_meta.php');

if (! check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access User Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (is_ajax()) {
    $method = get_parameter('method');
    $group_id = get_parameter('group_id');
    $group_recursion = (bool) get_parameter('group_recursion', 0);
    $get_user_profile_group = (bool) get_parameter('get_user_profile_group', false);

    $return_all = false;

    if ($get_user_profile_group === true) {
        $id_user = get_parameter('id_user');

        $user_is_admin = users_is_admin();

        $user_profiles = [];

        if ($user_is_admin === false) {
            $group_um = users_get_groups_UM($config['id_user']);
        }

        // User profiles.
        if ($user_is_admin || $id_user == $config['id_user'] || isset($group_um[0])) {
            $user_profiles = db_get_all_rows_field_filter(
                'tusuario_perfil',
                'id_usuario',
                $id_user
            );
        } else {
            $user_profiles_aux = users_get_user_profile($id_user);
            foreach ($group_um as $key => $value) {
                if (isset($user_profiles_aux[$key]) === true) {
                    $user_profiles[$key] = $user_profiles_aux[$key];
                    unset($user_profiles_aux[$key]);
                }
            }
        }

        foreach ($user_profiles as $key => $value) {
            $user_profiles[$key]['id_perfil'] = profile_get_name($value['id_perfil']);
            $user_profiles[$key]['id_grupo'] = groups_get_name($value['id_grupo'], true);
        }

        echo json_encode($user_profiles);
        return;
    }

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

            default:
                // Nothing to do.
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

            default:
                // Nothing to do.
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

            default:
                // Nothing to do.
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

// Header.
if (is_metaconsole() === true) {
    user_meta_print_header();
    $sec = 'advanced';
} else {
    if (check_acl($config['id_user'], 0, 'PM')) {
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
    } else {
        $buttons = [
            'user' => [
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
        ];
    }

    $buttons[$tab]['active'] = true;

    // Header.
    ui_print_standard_header(
        __('Users management'),
        'images/user.svg',
        false,
        '',
        false,
        $buttons,
        [
            [
                'link'  => '',
                'label' => __('Profiles'),
            ],
            [
                'link'  => '',
                'label' => __('Manage users'),
            ],
        ]
    );

    $sec = 'gusuarios';
}


$disable_user = get_parameter('disable_user', false);
$delete_user = (bool) get_parameter('user_del', false);

if ($delete_user === true) {
    // Delete user.
    $id_user = get_parameter('delete_user', 0);
    if ($id_user !== 0) {
        if (users_is_admin($id_user) === true && users_is_admin() === false) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to delete admininstrator user by non administrator user '.$config['id_user']
            );

            include 'general/noaccess.php';
            exit;
        }

        // Only allow delete user if is not the actual user.
        if ($id_user != $config['id_user']) {
            $user_row = users_get_user_by_id($id_user);

            $private_dashboards = db_get_all_rows_filter(
                'tdashboard',
                ['id_user' => $id_user],
                'id'
            );

            if (isset($private_dashboards) === true) {
                $dashboardRemoveResult = db_process_sql_delete('tdashboard', ['id_user' => $id_user]);
                // Refresh the view when delete private dashboards. For review.
                if ($dashboardRemoveResult === false || (int) $dashboardRemoveResult > 0) {
                    header('Refresh:1');
                }
            }

            $result = delete_user($id_user);

            if ($result) {
                delete_session_user($id_user);
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
                    if (metaconsole_connect($server) === NOERR) {
                        // Delete the user.
                        if (isset($private_dashboards) === true) {
                            db_process_sql_delete('tdashboard', ['id_user' => $id_user]);
                        }

                        $result = delete_user($id_user);
                        if ($result) {
                            db_pandora_audit(
                                AUDIT_LOG_USER_MANAGEMENT,
                                __('Deleted user %s from metaconsole', io_safe_input($id_user))
                            );
                        }

                        // Restore the db connection.
                        metaconsole_restore_db();
                    }

                    // Log to the metaconsole too.
                    if ($result) {
                        db_pandora_audit(
                            AUDIT_LOG_USER_MANAGEMENT,
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
    } else {
        ui_print_error_message(__('ID user cannot be empty'));
    }
} else if (isset($_GET['profile_del'])) {
    // Delete profile.
    $id_profile = (int) get_parameter_post('delete_profile');
    $result = profile_delete_profile($id_profile);
    ui_print_result_message(
        $result,
        __('Successfully deleted'),
        __('There was a problem deleting the profile')
    );
} else if ($disable_user !== false) {
    // CSRF Validator.
    /*
        if (html_print_csrf_error()) {
        return;
        }
    */
    // Disable_user.
    $id_user = get_parameter('id', 0);

    if (users_is_admin($id_user) === true && users_is_admin() === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to disable admininstrator user by non administrator user '.$config['id_user']
        );

        include 'general/noaccess.php';
        exit;
    }

    if ($id_user !== 0) {
        $result = users_disable($id_user, $disable_user);
    } else {
        $result = false;
    }

    if ($disable_user === 1) {
        ui_print_result_message(
            $result,
            __('Successfully disabled'),
            __('There was a problem disabling user')
        );
    } else if ($disable_user === 0) {
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

$filterTable = new stdClass();
$filterTable->width = '100%';
$filterTable->class = 'filter-table-adv';
$filterTable->rowclass[0] = '';
$filterTable->cellstyle[0][0] = 'width:0';
$filterTable->cellstyle[0][1] = 'width:0';
$filterTable->data[0][] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        false,
        'AR',
        true,
        'filter_group',
        $filter_group,
        '',
        '',
        0,
        true
    )
);

$filterTable->data[0][] = html_print_label_input_block(
    __('Search'),
    html_print_input_text(
        'filter_search',
        $filter_search,
        __('Search by username, fullname or email'),
        30,
        90,
        true
    ).ui_print_input_placeholder(
        __('Search by username, fullname or email'),
        true
    )
);

$form_filter = "<form method='post'>";
$form_filter .= html_print_table($filterTable, true);
$form_filter .= html_print_div(
    [
        'class'   => 'action-buttons-right-forced',
        'content' => html_print_submit_button(
            __('Search'),
            'search',
            false,
            [
                'icon'  => 'search',
                'class' => 'float-right',
                'mode'  => 'secondary mini',
            ],
            true
        ),
    ],
    true
);
$form_filter .= '</form>';

ui_toggle(
    $form_filter,
    '<span class="subsection_header_title">'.__('Filter').'</span>',
    __('Filter'),
    'filter',
    true,
    false,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph fixed_filter_bar'
);

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
$table->class = 'info_table tactical_table';
$table->id = 'user_list';
$table->styleTable = 'margin: 0 10px';

$table->head = [];
$table->data = [];
$table->align = [];
$table->size = [];
$table->valign = [];

$table->head[0] = '<span>'.__('User ID').'</span>';
$table->head[0] .= ui_get_sorting_arrows($url_up_id, $url_down_id, $selectUserIDUp, $selectUserIDDown);
$table->head[1] = '<span>'.__('Name').'</span>';
$table->head[1] .= ui_get_sorting_arrows($url_up_name, $url_down_name, $selectFullnameUp, $selectFullnameDown);
$table->head[2] = '<span>'.__('Last contact').'</span>';
$table->head[2] .= ui_get_sorting_arrows($url_up_last, $url_down_last, $selectLastConnectUp, $selectLastConnectDown);

$table->head[3] = '<span>'.__('Admin').'</span>';
$table->head[4] = '<span>'.__('Profile / Group').'</span>';
$table->head[5] = '<span>'.__('Description').'</span>';
if ($is_management_allowed === true) {
    $table->head[6] = '<span>'.__('Actions').'</span>';
}

if (is_metaconsole() === false) {
    $table->align[2] = '';
    $table->size[2] = '150px';
}

$table->align[3] = 'left';

if (is_metaconsole() === true) {
    $table->size[6] = '110px';
} else {
    $table->size[6] = '85px';
}

if (is_metaconsole() === false) {
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
    if (isset($group_um[0]) === true) {
        $info1 = get_users($order);
    } else {
        foreach ($group_um as $group => $value) {
            $info1 = array_merge($info1, users_get_users_by_group($group, $value));
        }
    }
}

// Filter the users.
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

foreach ($info1 as $user_id => $user_info) {
    // If user is not admin then don't display admin users.
    if ($user_is_admin === false && (bool) $user_info['is_admin'] === true) {
        unset($info1[$user_id]);
    }
}

$info = $info1;

$offset = (int) get_parameter('offset');
$limit = (int) $config['block_size'];

$rowPair = true;
$iterator = 0;
$cont = 0;
// Creates csrf.
$csrf = html_print_csrf_hidden(true);
foreach ($info as $user_id => $user_info) {
    if (empty($user_id) === true) {
        continue;
    }

    // User profiles.
    if ($user_is_admin || $user_id == $config['id_user'] || isset($group_um[0])) {
        $user_profiles = db_get_all_rows_sql(
            'SELECT * FROM tusuario_perfil where id_usuario LIKE "'.$user_id.'" LIMIT 5'
        );
    } else {
        $user_profiles_aux = users_get_user_profile($user_id, 'LIMIT 5');
        $user_profiles = [];
        foreach ($group_um as $key => $value) {
            if (isset($user_profiles_aux[$key]) === true) {
                $user_profiles[$key] = $user_profiles_aux[$key];
                unset($user_profiles_aux[$key]);
            }
        }

        if (empty($user_profiles_aux) === false) {
            $user_info['not_delete'] = 1;
        }

        if ($user_profiles == false) {
            continue;
        }
    }

    $cont++;

    // Manual pagination due the complicated process of the ACL data.
    if ($cont <= $offset && $search !== true) {
        continue;
    }

    if ($cont > ($limit + $offset) && $search !== true) {
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

    if ($is_management_allowed === true
        && ($user_is_admin
        || $config['id_user'] == $user_info['id_user']
        || (!$user_info['is_admin'] && (!isset($user_info['edit'])
        || isset($group_um[0]) || (isset($user_info['edit'])
        && $user_info['edit']))))
    ) {
        $data[0] = html_print_anchor(
            [
                'href'    => ui_get_full_url('index.php?sec=gusuarios&sec2=godmode/users/configure_user&edit_user=1&pure=0&id_user='.$user_id),
                'content' => $user_id,
            ],
            true
        );
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
            'images/user.svg',
            true,
            [
                'alt'   => __('Admin'),
                'title' => __('Administrator'),
                'class' => 'main_menu_icon invert_filter',
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
            $data[4] .= "<div class='float-left'>";
            $data[4] .= profile_get_name($row['id_perfil']);
            $data[4] .= ' / </div>';
            $data[4] .= "<div class='float-left pdd_l_5px'>";
            $data[4] .= groups_get_name($row['id_grupo'], true);
            $data[4] .= '</div>';

            if ($total_profile == 0 && count($user_profiles) >= 5) {
                $data[4] .= '<span onclick="showGroups(`'.$row['id_usuario'].'`)">'.html_print_image(
                    'images/zoom.png',
                    true,
                    [
                        'title' => __('Show profiles'),
                        'class' => 'invert_filter',
                    ]
                ).'</span>';

                $data[4] .= html_print_input_hidden(
                    'show_groups_'.$row['id_usuario'],
                    -1,
                    true
                );
            }

            $data[4] .= '<br/>';

            $total_profile++;
        }

        if (isset($user_info['not_delete']) === true) {
            $data[4] .= __('Other profiles are also assigned.');
            $data[4] .= ui_print_help_tip(
                __('Other profiles you cannot manage are also assigned. These profiles are not shown. You cannot enable/disable or delete this user.'),
                true
            );
        }

        $data[4] .= '</div>';
        $data[4] .= '<div class="invisible" id="profiles_'.$user_profiles[0]['id_usuario'].'">';
        $data[4] .= '</div>';
    } else {
        $data[4] .= __('The user doesn\'t have any assigned profile/group');
    }

    $data[5] = ui_print_string_substr($user_info['comments'], 24, true);
    $table->cellclass[][6] = 'table_action_buttons';
    $data[6] = '';
    $userListActionButtons = [];
    if ($is_management_allowed === true) {
        if ($user_is_admin
            || $config['id_user'] == $user_info['id_user']
            || isset($group_um[0])
            || (!$user_info['is_admin'] && (!isset($user_info['edit'])
            || (isset($user_info['edit']) && $user_info['edit'])))
        ) {
            // Disable / Enable user.
            if (isset($user_info['not_delete']) === false) {
                if ((int) $user_info['disabled'] === 0) {
                    $toDoString = __('Disable');
                    $toDoAction = '1';
                    $toDoImage  = 'images/disable.svg';
                    $toDoClass  = '';
                } else {
                    $toDoString = __('Enable');
                    $toDoAction = '0';
                    $toDoImage  = 'images/enable.svg';
                    $toDoClass  = 'filter_none';
                }

                $userListActionButtons[] = html_print_menu_button(
                    [
                        'href'  => ui_get_full_url(
                            sprintf(
                                'index.php?sec=%s&amp;sec2=godmode/users/user_list&disable_user=%s&pure=%s&id=%s',
                                $sec,
                                $toDoAction,
                                $pure,
                                $user_info['id_user']
                            )
                        ),
                        'image' => $toDoImage,
                        'title' => $toDoString,
                    ],
                    true
                );
                /*
                    $data[6] = '<form method="POST" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;pure='.$pure.'&offset='.$offset.'" class="inline">';
                    $data[6] .= html_print_input_hidden(
                    'id',
                    $user_info['id_user'],
                    true
                    );
                    // Same csrf for every disable button for submit.
                    $data[6] .= $csrf;
                    $data[6] .= html_print_input_hidden(
                    'disable_user',
                    $toDoAction,
                    true
                    );
                    $data[6] .= html_print_input_image(
                    'submit_disable_enable',
                    $toDoImage,
                    '',
                    '',
                    true,
                    [
                        'data-title'                     => $toDoString,
                        'data-use_title_for_force_title' => '1',
                        'class'                          => 'main_menu_icon forced_title no-padding '.$toDoClass,
                    ]
                    );
                    $data[6] .= '</form>';
                */
            }

            /*
                // Edit user.
                $data[6] .= '<form method="POST" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'" id="edit_user_form_'.$user_info['id_user'].'" class="inline">';
                $data[6] .= html_print_input_hidden(
                'id_user',
                $user_info['id_user'],
                true
                );
                $data[6] .= html_print_input_hidden(
                'edit_user',
                '1',
                true
                );
                $data[6] .= html_print_input_image(
                'submit_edit_user',
                'images/edit.svg',
                '',
                'padding:0',
                true,
                [
                    'data-title'                     => __('Edit'),
                    'data-use_title_for_force_title' => '1',
                    'class'                          => 'main_menu_icon forced_title no-padding',
                ]
                );
            $data[6] .= '</form>';*/

            $userListActionButtons[] = html_print_menu_button(
                [
                    'href'  => ui_get_full_url(
                        sprintf(
                            'index.php?sec=%s&amp;sec2=godmode/users/configure_user&edit_user=1&pure=%s&id_user=%s',
                            $sec,
                            $pure,
                            $user_info['id_user']
                        )
                    ),
                    'image' => 'images/edit.svg',
                    'title' => __('Edit user'),
                ],
                true
            );

            if ($config['admin_can_delete_user']
                && $user_info['id_user'] != $config['id_user']
                && isset($user_info['not_delete']) === false
            ) {
                /*
                    $offset_delete = ($offset >= count($info) - 1) ? ($offset - $config['block_size']) : $offset;
                    $data[6] .= '<form method="POST" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;pure='.$pure.'&offset='.$offset_delete.'" class="inline">';
                    $data[6] .= html_print_input_hidden(
                    'delete_user',
                    $user_info['id_user'],
                    true
                    );
                    $data[6] .= html_print_input_hidden(
                    'user_del',
                    '1',
                    true
                    );
                    $data[6] .= html_print_input_image(
                    'submit_delete_user',
                    'images/delete.svg',
                    '',
                    'padding:0',
                    true,
                    [
                        'data-title'                     => __('Delete'),
                        'data-use_title_for_force_title' => '1',
                        'class'                          => 'main_menu_icon forced_title no-padding',
                    ]
                    );
                    $data[6] .= '</form>';
                */
                $userListActionButtons[] = html_print_menu_button(
                    [
                        'href'    => ui_get_full_url(
                            sprintf(
                                'index.php?sec=%s&amp;sec2=godmode/users/user_list&user_del=1&pure=%s&delete_user=%s',
                                $sec,
                                $pure,
                                $user_info['id_user']
                            )
                        ),
                        'image'   => 'images/delete.svg',
                        'title'   => __('Delete'),
                        'onClick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                    ],
                    true
                );

                if (is_metaconsole() === true) {
                    $data[6] .= '<form method="POST" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/user_list&amp;pure='.$pure.'" class="inline">';
                    $data[6] .= html_print_input_hidden(
                        'delete_user',
                        $user_info['id_user'],
                        true
                    );
                    $data[6] .= html_print_input_hidden(
                        'user_del',
                        '1',
                        true
                    );
                    $data[6] .= html_print_input_hidden(
                        'delete_all',
                        '1',
                        true
                    );
                    $data[6] .= '</form>';
                } else {
                    $data[6] = implode('', $userListActionButtons);
                }
            } else {
                $data[6] .= '';
                // Delete button not in this mode.
            }

            // TODO. Check this in META!!!
            $data[6] = implode('', $userListActionButtons);
        } else {
            $data[6] .= '';
            // Delete button not in this mode.
        }
    }

    array_push($table->data, $data);
}

html_print_table($table);
$tablePagination = ui_pagination(count($info), false, 0, 0, true, 'offset', false, 'dataTables_paginate paging_simple_numbers');
unset($table);
if ($is_management_allowed === true) {
    if ($config['admin_can_add_user'] !== false) {
        echo '<form method="post" action="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_user&pure='.$pure.'">';
        html_print_action_buttons(
            html_print_submit_button(
                __('Create user'),
                'crt',
                false,
                [ 'icon' => 'wand' ],
                true
            ),
            [
                'type'          => 'form_action',
                'right_content' => $tablePagination,
            ],
        );
        html_print_input_hidden('new_user', 1);
        echo '</form>';
    } else {
        echo '<i>'.__("The current authentication scheme doesn't support creating users on %s", get_product_name()).'</i>';
    }
}

?>
<script type="text/javascript">
    function showGroups(id_user) {
        if ($(`#hidden-show_groups_${id_user}`).val() === '-1') {
            var request = $.ajax({
                url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                type: 'GET',
                dataType: 'json',
                data: {
                    page: 'godmode/users/user_list',
                    get_user_profile_group: 1,
                    id_user: id_user
                },
                success: function (data, textStatus, xhr) {
                    let count = 1;
                    data.forEach( function(valor, indice, array) {
                        if (count >= 6) {
                            let main_div = $(`#profiles_${id_user}`);
                            main_div.append(
                                `<div id="left_${id_user}_${count}" class='float-left'>${valor.id_perfil} / </div>`,
                                `<div id="right_${id_user}_${count}" class='float-left pdd_l_5px'>${valor.id_grupo}</div>`,
                                `<br/><br/>`
                            );
                        }
                        count ++;
                    });
                },
                error: function (e, textStatus) {
                    console.error(textStatus);
                }
            });
            $(`#hidden-show_groups_${id_user}`).val('1');
            $(`#profiles_${id_user}`).show();
        } else if ($(`#hidden-show_groups_${id_user}`).val() === '1') {
            $(`#hidden-show_groups_${id_user}`).val('0');
            $(`#profiles_${id_user}`).hide();
        } else {
            $(`#hidden-show_groups_${id_user}`).val('1');
            $(`#profiles_${id_user}`).show();
        }
    }

</script>
