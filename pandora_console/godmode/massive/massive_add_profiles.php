<?php
/**
 * View for Add profiles in Massive Operations
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

if (!check_acl($config['id_user'], 0, 'UM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive profile addition'
    );
    include 'general/noaccess.php';
    return;
}

if (is_management_allowed() === false) {
    if (is_metaconsole() === false) {
        $url = '<a target="_blank" href="'.ui_get_meta_url(
            'index.php?sec=advanced&sec2=advanced/users_setup&tab=profile&pure='.(int) $config['pure']
        ).'">'.__('metaconsole').'</a>';
    } else {
        $url = __('any node');
    }

    ui_print_warning_message(
        __(
            'This node is configured with centralized mode. All profiles user information is read only. Go to %s to manage it.',
            $url
        )
    );

    return;
}

require_once 'include/functions_agents.php';
require_once 'include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_profile.php';
require_once $config['homedir'].'/include/functions_users.php';

$create_profiles = (int) get_parameter('create_profiles');

// Get users and groups user can manage to check and for selectors.
$group_um = users_get_groups_UM($config['id_user']);

$users_profiles = '';
$users_order = [
    'field' => 'id_user',
    'order' => 'ASC',
];

$info_users = [];
// Is admin.
if (users_is_admin()) {
    $info_users = users_get_info($users_order, 'id_user');
    // has PM permission.
} else if (check_acl($config['id_user'], 0, 'PM')) {
    $info_users = users_get_info($users_order, 'id_user');
    foreach ($info_users as $id_user => $value) {
        if (users_is_admin($id_user)) {
            unset($info_users[$value]);
        }
    }
} else {
    $info = [];
    foreach ($group_um as $group => $value) {
        $info = array_merge($info, users_get_users_by_group($group, $value));
    }

    foreach ($info as $key => $value) {
        if (!$value['is_admin']) {
            $info_users[$key] = $value['id_user'];
        }
    }
}

if ($create_profiles) {
    $profiles_id = get_parameter('profiles_id', -1);
    $groups_id = get_parameter('groups_id', -1);
    $users_id = get_parameter('users_id', -1);
    $n_added = 0;

    if ($profiles_id == -1 || $groups_id == -1 || $users_id == -1) {
        $result = false;
    } else {
        foreach ($profiles_id as $profile) {
             // Check profiles permissions for non admin user.
            if (is_user_admin($config['id_user']) === false) {
                $user_profiles = profile_get_profiles(
                    [
                        'pandora_management' => '<> 1',
                        'db_management'      => '<> 1',
                    ]
                );

                if (array_search((int) $profile, array_keys($user_profiles)) === false) {
                    db_pandora_audit(
                        AUDIT_LOG_ACL_VIOLATION,
                        'Trying to add administrator profile whith standar user for user '.io_safe_input($user)
                    );
                    continue;
                }
            }

            foreach ($groups_id as $group) {
                if (check_acl($config['id_user'], $group, 'UM') === false) {
                    db_pandora_audit(
                        AUDIT_LOG_ACL_VIOLATION,
                        'Trying to add profile group without permission for user '.io_safe_input($user)
                    );
                    continue;
                }

                foreach ($users_id as $user) {
                    if (array_search($user, $info_users) === false) {
                        db_pandora_audit(
                            AUDIT_LOG_ACL_VIOLATION,
                            'Trying to edit user without permission for user '.io_safe_input($user)
                        );
                        continue;
                    }

                    $profile_data = db_get_row_filter('tusuario_perfil', ['id_usuario' => $user, 'id_perfil' => $profile, 'id_grupo' => $group]);
                    // If the profile doesnt exist, we create it
                    if ($profile_data === false) {
                        db_pandora_audit(
                            AUDIT_LOG_USER_MANAGEMENT,
                            'Added profile for user '.io_safe_input($user)
                        );
                        $return = profile_create_user_profile($user, $profile, $group);
                        if ($return !== false) {
                            $n_added++;
                        }
                    }
                }
            }
        }
    }

    if ($n_added > 0) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Add profiles',
            false,
            false,
            'Profiles: '.json_encode($profiles_id).' Groups: '.json_encode($groups_id).'Users: '.json_encode($users_id)
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail to try add profiles',
            false,
            false,
            'Profiles: '.json_encode($profiles_id).' Groups: '.json_encode($groups_id).'Users: '.json_encode($users_id)
        );
    }

    ui_print_result_message(
        $n_added > 0,
        __('Profiles added successfully').'('.$n_added.')',
        __('Profiles cannot be added')
    );
}

if ($table !== null) {
    html_print_table($table);
}

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->data = [];
$table->head = [];
$table->align = [];
$table->style = [];
$table->style[0] = 'font-weight: bold';
$table->style[1] = 'font-weight: bold';
$table->head[0] = __('Profile name');
$table->head[1] = __('Group');
$table->head[2] = __('Users');
$table->align[2] = 'left';
$table->size[0] = '34%';
$table->size[1] = '33%';
$table->size[2] = '33%';

$data = [];
$data[0] = '<form method="post" id="form_profiles" action="index.php?sec=gmassive&sec2=godmode/massive/massive_operations&tab=massive_users&option=add_profiles">';


$display_all_group = true;
if (check_acl($config['id_user'], 0, 'PM')) {
    $data[0] .= html_print_select(
        profile_get_profiles(),
        'profiles_id[]',
        '',
        '',
        '',
        '',
        true,
        true,
        false,
        '',
        false,
        'width: 100%'
    );
} else {
    if (!isset($group_um[0])) {
        $display_all_group = false;
    }

    $data[0] .= html_print_select(
        profile_get_profiles(
            [
                'pandora_management' => '<> 1',
                'db_management'      => '<> 1',
            ]
        ),
        'profiles_id[]',
        '',
        '',
        '',
        '',
        true,
        true,
        false,
        '',
        false,
        'width: 100%'
    );
}

$data[1] = html_print_select_groups(
    $config['id_user'],
    'UM',
    $display_all_group,
    'groups_id[]',
    '',
    '',
    '',
    '',
    true,
    true,
    false,
    '',
    false,
    'width: 100%'
);
$data[2] = '<span id="alerts_loading" class="invisible">';
$data[2] .= html_print_image('images/spinner.png', true);
$data[2] .= '</span>';

$data[2] .= html_print_select(
    $info_users,
    'users_id[]',
    '',
    '',
    '',
    '',
    true,
    true,
    true,
    '',
    false,
    'width: 100%'
);

// Waiting spinner.
ui_print_spinner(__('Loading'));

array_push($table->data, $data);

html_print_table($table);

attachActionButton('create_profiles', 'update', $table->width, false, $SelectAction);

echo '</form>';

unset($table);
