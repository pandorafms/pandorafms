<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Profile_Functions
 */


function profile_exist($name)
{
    return (bool) db_get_value('id_perfil', 'tperfil', 'name', $name);
}


/**
 * Get profile name from id.
 *
 * @param integer $id_profile Id profile in tperfil
 *
 * @return string Profile name of the given id
 */
function profile_get_name($id_profile)
{
    return (string) db_get_value('name', 'tperfil', 'id_perfil', (int) $id_profile);
}


/**
 * Selects all profiles (array (id => name)) or profiles filtered
 *
 * @param mixed Array with filter conditions to retrieve profiles or false.
 *
 * @return array List of all profiles
 */
function profile_get_profiles($filter=false)
{
    if ($filter === false) {
        $profiles = db_get_all_rows_in_table('tperfil', 'name');
    } else {
        $profiles = db_get_all_rows_filter('tperfil', $filter);
    }

    $return = [];
    if ($profiles === false) {
        return $return;
    }

    foreach ($profiles as $profile) {
        $return[$profile['id_perfil']] = $profile['name'];
    }

    return $return;
}


/**
 * Create Profile for User
 *
 * @param string User ID
 * @param int Profile ID (default 1 => AR)
 * @param int Group ID (default 1 => All)
 * @param string Assign User who assign the profile to user.
 * @param string tags where the view of the user in this group will be restricted
 * @param bool Profile is marked to not provide hierarchy
 *
 * @return mixed Number id if succesful, false if not
 */
function profile_create_user_profile(
    $id_user,
    $id_profile=1,
    $id_group=0,
    $assignUser=false,
    $tags='',
    $no_hierarchy=false
) {
    global $config;

    if (empty($id_profile) || $id_group < 0) {
        return false;
    }

    // Checks if the user exists
    $result_user = users_get_user_by_id($id_user);

    if (!$result_user) {
        return false;
    }

    if (isset($config['id_user'])) {
        // Usually this is set unless we call it while logging in (user known by auth scheme but not by pandora)
        $assign = $config['id_user'];
    } else {
        $assign = $id_user;
    }

    if ($assignUser !== false) {
        $assign = $assignUser;
    }

    $insert = [
        'id_usuario'   => $id_user,
        'id_perfil'    => $id_profile,
        'id_grupo'     => $id_group,
        'tags'         => $tags,
        'assigned_by'  => $assign,
        'no_hierarchy' => $no_hierarchy ? 1 : 0,
    ];

    return db_process_sql_insert('tusuario_perfil', $insert);
}


/**
 * Delete user profile from database
 *
 * @param string User ID
 * @param int Profile ID
 *
 * @return boolean Whether or not it's deleted
 */
function profile_delete_user_profile($id_user, $id_profile)
{
    $where = [
        'id_usuario' => $id_user,
        'id_up'      => $id_profile,
    ];

    return (bool) db_process_sql_delete('tusuario_perfil', $where);
}


/**
 * Delete profile from database (not user-profile link (tusuario_perfil), but the actual profile (tperfil))
 *
 * @param int Profile ID
 *
 * @return boolean Whether or not it's deleted
 */
function profile_delete_profile($id_profile)
{
    return (bool) db_process_sql_delete('tperfil', ['id_perfil' => $id_profile]);
}


/**
 * Delete profile from database and remove from the assigned users (tusuario_perfil)
 *
 * @param int Profile ID
 *
 * @return boolean Whether or not it's deleted in both tables
 */
function profile_delete_profile_and_clean_users($id_profile)
{
    $profile_deletion = (bool) db_process_sql_delete('tperfil', ['id_perfil' => $id_profile]);

    // Delete in tusuario_perfil only if is needed
    if (!(bool) db_get_value('id_perfil', 'tusuario_perfil', 'id_perfil', $id_profile)) {
        return $profile_deletion;
    }

    return $profile_deletion &&
        (bool) db_process_sql_delete('tusuario_perfil', ['id_perfil' => $id_profile]);
}


/**
 * Print the table to display, create and delete profiles
 *
 * @param int User id
 * @param bool Show the tags select or not
 */
function profile_print_profile_table($id)
{
    global $config;

    $title = __('Profiles/Groups assigned to this user');

    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'databox data';
    if (defined('METACONSOLE')) {
        $table->head_colspan[0] = 0;
        $table->width = '100%';
        $table->class = 'databox_tactical data';
        $table->title = $title;
    } else {
        echo '<h4>'.$title.'</h4>';
    }

    $table->data = [];
    $table->head = [];
    $table->align = [];
    $table->style = [];
    if (!defined('METACONSOLE')) {
        $table->style[0] = 'font-weight: bold';
        $table->style[1] = 'font-weight: bold';
    }

    $table->head['name'] = __('Profile name');
    $table->head['group'] = __('Group');
    $table->head['tags'] = __('Tags');
    $table->head['hierarchy'] = __('No hierarchy');
    $table->head['actions'] = __('Action');
    $table->align['actions'] = 'center';

    $result = db_get_all_rows_filter(
        'tusuario_perfil',
        ['id_usuario' => $id]
    );

    if ($result === false) {
        $result = [];
    }

    foreach ($result as $profile) {
        if ($profile['id_grupo'] == -1) {
            continue;
        }

        $data = [];

        $data['name'] = '<a href="index.php?sec='.$sec.'&amp;sec2=godmode/users/configure_profile&id='.$profile['id_perfil'].'&pure='.$pure.'">'.profile_get_name($profile['id_perfil']).'</a>';
        $data['group'] = ui_print_group_icon($profile['id_grupo'], true);

        if (!defined('METACONSOLE')) {
            $data['group'] .= '<a href="index.php?sec=estado&sec2=operation/agentes/estado_agente&refr=60&group_id='.$profile['id_grupo'].'&pure='.$pure.'">';
        }

        $data['group'] .= '&nbsp;'.ui_print_truncate_text(groups_get_name($profile['id_grupo'], true), GENERIC_SIZE_TEXT);
        if (!defined('METACONSOLE')) {
            $data['group'] .= '</a>';
        }

        if (empty($profile['tags'])) {
            $data['tags'] = '';
        } else {
            $tags_ids = explode(',', $profile['tags']);
            $tags = tags_get_tags($tags_ids);
            $data['tags'] = tags_get_tags_formatted($tags);
        }

        $data['hierarchy'] = $profile['no_hierarchy'] ? __('Yes') : __('No');

        $data['actions'] = '<form method="post" onsubmit="if (!confirm (\''.__('Are you sure?').'\')) return false">';
        $data['actions'] .= html_print_input_hidden('delete_profile', 1, true);
        $data['actions'] .= html_print_input_hidden('id_user_profile', $profile['id_up'], true);
        $data['actions'] .= html_print_input_hidden('id_user', $id, true);
        $data['actions'] .= html_print_input_image('del', 'images/cross.png', 1, '', true);
        $data['actions'] .= '</form>';

        array_push($table->data, $data);
    }

    $data = [];

    $data['name'] = '<form method="post">';
    if (check_acl($config['id_user'], 0, 'PM')) {
        $data['name'] .= html_print_select(
            profile_get_profiles(),
            'assign_profile',
            0,
            '',
            __('None'),
            0,
            true,
            false,
            false
        );
    } else {
        $data['name'] .= html_print_select(
            profile_get_profiles(
                [
                    'pandora_management' => '<> 1',
                    'db_management'      => '<> 1',
                ]
            ),
            'assign_profile',
            0,
            '',
            __('None'),
            0,
            true,
            false,
            false
        );
    }

    $data['group'] = html_print_select_groups(
        $config['id_user'],
        'UM',
        users_is_admin($config['id_user']),
        'assign_group',
        -1,
        '',
        __('None'),
        -1,
        true,
        false,
        false
    );

    $tags = tags_get_all_tags();
    $data['tags'] = html_print_select($tags, 'assign_tags[]', '', '', __('Any'), '', true, true);

    $data['hierarchy'] = html_print_checkbox('no_hierarchy', 1, false, true).ui_print_help_icon('no_hierarchy', true);

    $data['actions'] = html_print_input_image('add', 'images/add.png', 1, '', true);
    $data['actions'] .= html_print_input_hidden('id', $id, true);
    $data['actions'] .= html_print_input_hidden('add_profile', 1, true);
    $data['actions'] .= '</form>';

    array_push($table->data, $data);

    html_print_table($table);
    unset($table);
}
