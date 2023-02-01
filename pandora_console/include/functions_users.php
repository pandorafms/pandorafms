<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
 * @subpackage Users
 */

require_once $config['homedir'].'/include/functions_groups.php';


function users_is_strict_acl($id_user=null)
{
    global $config;

    if (empty($id_user)) {
        $id_user = $config['id_user'];
    }

    $strict_acl = (bool) db_get_value(
        'strict_acl',
        'tusuario',
        'id_user',
        $id_user
    );

    return $strict_acl;
}


/**
 * Get a list of all users in an array [username] => (info)
 *
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 * @param string Which info to get (defaults to nombre_real)
 *
 * @return array An array of users
 */
function users_get_info($order='fullname', $info='fullname')
{
    $users = get_users($order);

    $ret = [];
    foreach ($users as $user_id => $user_info) {
        $ret[$user_id] = $user_info[$info];
    }

    return $ret;
}


/**
 * Enable/Disable a user
 *
 * @param int user id
 * @param int new disabled value (0 when enable, 1 when disable)
 *
 * @return integer sucess return
 */
function users_disable($user_id, $new_disabled_value)
{
    return db_process_sql_update(
        'tusuario',
        ['disabled' => $new_disabled_value],
        ['id_user' => $user_id]
    );
}


/**
 * Get all the Model groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_all_model_groups()
{
    $groups = db_get_all_rows_in_table('tmodule_group');
    if ($groups === false) {
        $groups = [];
    }

    $returnGroups = [];
    foreach ($groups as $group) {
        $returnGroups[$group['id_mg']] = $group['name'];
    }

    $returnGroups[0] = 'Not assigned';
    // Module group external to DB but it exist
    return $returnGroups;
}


/**
 * Get all the groups a user has reading privileges with the special format to use it on select.
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean                                                             $returnAllGroup   Flag the return group, by default true.
 * @param boolean                                                             $returnAllColumns Flag to return all columns of groups.
 * @param array                                                               $id_groups        The id of node that must do not show the children and own.
 * @param string                                                              $keys_field       The field of the group used in the array keys. By default ID
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups_for_select(
    $id_user,
    $privilege='AR',
    $returnAllGroup=true,
    $returnAllColumns=false,
    $id_groups=null,
    $keys_field='id_grupo',
    $ajax_format=false,
    $check_user_can_manage_all=false
) {
    if ($id_groups === false) {
        $id_groups = null;
    }

    if ($check_user_can_manage_all === true && users_can_manage_group_all($privilege) === false) {
        $returnAllGroup = false;
    }

    $user_groups = users_get_groups(
        $id_user,
        $privilege,
        $returnAllGroup,
        $returnAllColumns,
        null
    );

    if ($id_groups !== null && empty($id_groups) === false) {
        $children = [];
        foreach ($id_groups as $key => $id_group) {
            $children[] = groups_get_children($id_group);
        }

        if (empty($children) === false) {
            foreach ($children as $child) {
                unset($user_groups[$child['id_grupo']]);
            }
        }

        foreach ($id_groups as $key => $id_group) {
            unset($user_groups[$id_group]);
        }
    }

    if (empty($user_groups)) {
        $user_groups_tree = [];
    } else {
        // First group it's needed to retrieve its parent group.
        $first_group = array_slice($user_groups, 0, 1);
        $first_group = reset($first_group);
        $parent_group = $first_group['parent'];

        $user_groups_tree = groups_get_groups_tree_recursive($user_groups, $parent_group);
    }

    $fields = [];

    foreach ($user_groups_tree as $group) {
        $groupName = ui_print_truncate_text($group['nombre'], GENERIC_SIZE_TEXT, false, true, false);

        if ($ajax_format === false) {
            $fields[$group[$keys_field]] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $group['deep']).$groupName;
        } else {
            $tmp['id'] = $group[$keys_field];
            $tmp['text'] = io_safe_output($groupName);
            $tmp['level'] = $group['deep'];
            $fields[] = $tmp;
        }
    }

    return $fields;
}


/**
 * Extract ancestors for given group.
 *
 * @param integer $group_id Target group.
 * @param array   $groups   All groups.
 *
 * @return array
 */
function get_group_ancestors($group_id, $groups)
{
    if ($group_id == 0) {
        return 0;
    }

    if (!isset($groups[$group_id])) {
        return null;
    }

    $parent = $groups[$group_id]['parent'];

    if ($groups[$group_id]['propagate'] == 0) {
        return $group_id;
    }

    $r = get_group_ancestors($parent, $groups);

    if (is_array($r)) {
        $r = array_merge([$group_id], $r);
    } else {
        $r = [
            $group_id,
            $r,
        ];
    }

    return $r;
}


function groups_combine_acl($acl_group_a, $acl_group_b)
{
    if (!is_array($acl_group_a)) {
        if (is_array($acl_group_b)) {
            return $acl_group_b;
        } else {
            return null;
        }
    } else {
        if (!is_array($acl_group_b)) {
            return $acl_group_a;
        }
    }

    $acl_list = [
        'agent_view'                => 1,
        'agent_edit'                => 1,
        'agent_disable'             => 1,
        'alert_edit'                => 1,
        'alert_management'          => 1,
        'pandora_management'        => 1,
        'db_management'             => 1,
        'user_management'           => 1,
        'report_view'               => 1,
        'report_edit'               => 1,
        'report_management'         => 1,
        'event_view'                => 1,
        'event_edit'                => 1,
        'event_management'          => 1,
        'map_view'                  => 1,
        'map_edit'                  => 1,
        'map_management'            => 1,
        'vconsole_view'             => 1,
        'vconsole_edit'             => 1,
        'vconsole_management'       => 1,
        'tags'                      => 1,
        'network_config_view'       => 1,
        'network_config_edit'       => 1,
        'network_config_management' => 1,

    ];

    foreach ($acl_group_a['tags'] as $key => $value) {
        $acl_group_b['tags'][$key] = array_merge($value, $acl_group_b['tags'][$key]);
    }

    foreach ($acl_list as $acl => $aux) {
        if ($acl == 'tags') {
            continue;
        }

        // propagate ACL
        $acl_group_b[$acl] = $acl_group_a[$acl] || $acl_group_b[$acl];
    }

    return $acl_group_b;

}


/**
 * Get all the groups a user has reading privileges.
 *
 * @param string  $id_user          User id.
 * @param string  $privilege        The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup   Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 * @param array   $id_groups        The list of group to scan to bottom child. By default null.
 * @param string  $keys_field       The field of the group used in the array keys. By default ID.
 * @param boolean $cache            Set it to false to not use cache.
 * @param string  $term             Return only groups matching keyword '$term'.
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups(
    $id_user=false,
    $privilege='AR',
    $returnAllGroup=true,
    $returnAllColumns=false,
    $id_groups=null,
    $keys_field='id_grupo',
    $cache=true,
    $search=''
) {
    static $group_cache = [];

    $filter = '';

    // Added users_group_cache to avoid unnecessary proccess on massive calls...
    static $users_group_cache = [];
    $users_group_cache_key = $id_user.'|'.$privilege.'|'.$returnAllGroup.'|'.$returnAllColumns.'|'.$search;

    if (empty($id_user)) {
        global $config;

        $id_user = null;
        if (isset($config['id_user'])) {
            $id_user = $config['id_user'];
        }
    }

    // Check the group cache first.
    if (array_key_exists($users_group_cache_key, $group_cache) && $cache) {
        $forest_acl = $group_cache[$users_group_cache_key];
    } else {
        // Admin.
        if (is_user_admin($id_user)) {
            if (empty($search) === false) {
                $filter = sprintf(
                    ' WHERE lower(tgrupo.nombre) like lower("%%%s%%")',
                    $search
                );
            }

            $sql = sprintf(
                'SELECT * FROM tgrupo %s ORDER BY nombre',
                $filter
            );

            $forest_acl = db_get_all_rows_sql($sql);
        }

        // Per-group permissions.
        else {
            $query  = 'SELECT * FROM tgrupo ORDER BY nombre';
            $raw_groups = db_get_all_rows_sql($query);

            $query = sprintf(
                "SELECT tgrupo.*, tperfil.*, tusuario_perfil.tags, tusuario_perfil.no_hierarchy FROM tgrupo, tusuario_perfil, tperfil
						WHERE (tgrupo.id_grupo = tusuario_perfil.id_grupo OR tusuario_perfil.id_grupo = 0)
						AND tusuario_perfil.id_perfil = tperfil.id_perfil
						AND tusuario_perfil.id_usuario = '%s' %s ORDER BY nombre",
                $id_user,
                $filter
            );
            $raw_forest = db_get_all_rows_sql($query);
            if ($raw_forest === false) {
                $raw_forest = [];
            }

            foreach ($raw_forest as $g) {
                users_get_explode_tags($g);

                if (!isset($forest_acl[$g['id_grupo']])) {
                    $forest_acl[$g['id_grupo']] = $g;
                } else {
                    $forest_acl[$g['id_grupo']] = groups_combine_acl($forest_acl[$g['id_grupo']], $g);
                }
            }

            $groups = [];
            foreach ($raw_groups as $g) {
                $groups[$g['id_grupo']] = $g;
            }

            foreach ($groups as $group) {
                $parents = get_group_ancestors($group['parent'], $groups);
                if (is_array($parents)) {
                    foreach ($parents as $parent) {
                        if ((isset($forest_acl[$parent]))
                            && ($groups[$parent]['propagate'] == 1)
                            && ($forest_acl[$parent]['no_hierarchy'] == 0)
                        ) {
                            if (isset($forest_acl[$group['id_grupo']])) {
                                // update ACL propagation
                                $tmp = groups_combine_acl($forest_acl[$parent], $forest_acl[$group['id_grupo']]);
                            } else {
                                // add group to user ACL forest
                                users_get_explode_tags($group);
                                $tmp = groups_combine_acl($forest_acl[$parent], $group);
                            }

                            if ($tmp !== null) {
                                // add only if valid
                                $forest_acl[$group['id_grupo']] = $tmp;
                            }
                        }
                    }
                }

                // No parents, direct assignment already done
            }
        }

        // Update the group cache.
        $group_cache[$users_group_cache_key] = $forest_acl;
    }

    $user_groups = [];
    if (!$forest_acl) {
        return $user_groups;
    }

    if ($returnAllGroup) {
        // All group
        $groupall = [
            'id_grupo'    => 0,
            'nombre'      => __('All'),
            'icon'        => 'world',
            'parent'      => 0,
            'disabled'    => 0,
            'custom_id'   => null,
            'description' => '',
            'propagate'   => 0,
        ];

        // Add the All group to the beginning to be always the first
        array_unshift($forest_acl, $groupall);
    }

    $acl_column = get_acl_column($privilege);

    if (array_key_exists($users_group_cache_key, $users_group_cache) && $cache) {
        return $users_group_cache[$users_group_cache_key];
    }

    foreach ($forest_acl as $group) {
        // Check the specific permission column. acl_column is undefined for admins.
        if (isset($group[$acl_column]) && $group[$acl_column] != '1') {
            continue;
        }

        if ($returnAllColumns) {
            $user_groups[$group[$keys_field]] = $group;
        } else {
            $user_groups[$group[$keys_field]] = $group['nombre'];
        }
    }

    // Search filter.
    if (empty($search) === false) {
        $user_groups = array_filter(
            $user_groups,
            function ($group) use ($search) {
                return (bool) preg_match('/'.$search.'/i', $group['nombre']);
            }
        );
    }

    $users_group_cache[$users_group_cache_key] = $user_groups;

    return $user_groups;
}


/**
 * Get all the groups a user has reading privileges. Version for tree groups.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean                          $returnAllGroup   Flag the return group, by default true.
 * @param boolean                          $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A treefield list of the groups the user has certain privileges.
 */
function users_get_groups_tree($id_user=false, $privilege='AR', $returnAllGroup=true)
{
    $user_groups = users_get_groups($id_user, $privilege, $returnAllGroup, true);

    $user_groups_tree = groups_get_groups_tree_recursive($user_groups);

    return $user_groups_tree;
}


/**
 * Get the first group of an user.
 *
 * Useful function when you need a default group for a user.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean                          $all_group Flag to return all group, by default true;
 *
 * @return array The first group where the user has certain privileges.
 */
function users_get_first_group($id_user=false, $privilege='AR', $all_group=true)
{
    $groups = array_keys(users_get_groups($id_user, $privilege));

    $return = array_shift($groups);

    if ((!$all_group) && ($return == 0)) {
        $return = array_shift($groups);
    }

    return $return;
}


/**
 * Return access to a specific agent by a specific user
 *
 * @param int Agent id.
 * @param string Access mode to be checked. Default AR (Agent reading)
 * @param string User id. Current user by default
 * @param bool True to use the metaconsole tables
 *
 * @return boolean Access to that agent (false not, true yes)
 */
function users_access_to_agent(
    $id_agent,
    $mode='AR',
    $id_user=false,
    $force_meta=false
) {
    if (empty($id_agent)) {
        return false;
    }

    if ($id_user == false) {
        global $config;
        $id_user = $config['id_user'];
    }

    return (bool) check_acl_one_of_groups(
        $id_user,
        agents_get_all_groups_agent((int) $id_agent, false, $force_meta),
        $mode
    );
}


/**
 * Return user by id (user name)
 *
 * @param string User id.
 *
 * @return mixed User row or false if something goes wrong
 */
function users_get_user_by_id($id_user)
{
    $result_user = db_get_row('tusuario', 'id_user', $id_user);

    return $result_user;
}


function users_is_admin($id_user=false)
{
    global $config;

    if (!isset($config['is_admin'])) {
        $config['is_admin'] = [];
    }

    if ($id_user === false) {
        $id_user = $config['id_user'];
    }

    if (isset($config['is_admin'][$id_user])) {
        return $config['is_admin'][$id_user];
    }

    $config['is_admin'][$id_user] = (bool) db_get_value(
        'is_admin',
        'tusuario',
        'id_user',
        $id_user
    );

    return $config['is_admin'][$id_user];
}


// Check if a user can manage a group when group is all
// This function dont check acls of the group, only if the
// user is admin or pandora manager and the group is all
function users_can_manage_group_all($access='PM')
{
    global $config;

    $access = get_acl_column($access);

    $sql = sprintf(
        'SELECT COUNT(*) FROM tusuario_perfil
		INNER JOIN tperfil
			ON tperfil.id_perfil = tusuario_perfil.id_perfil
		WHERE tusuario_perfil.id_grupo=0
			AND tusuario_perfil.id_usuario="%s"
			AND %s=1
		',
        $config['id_user'],
        $access
    );

    if (users_is_admin($config['id_user']) || (int) db_get_value_sql($sql) !== 0) {
        return true;
    }

    return false;
}


/**
 * Get the users that belongs to the same groups of the current user
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean                                                             $returnAllGroup Flag the return group, by default true.
 *
 * @return mixed Array with id_user as index and value
 */
function users_get_user_users(
    $id_user=false,
    $privilege='AR',
    $returnAllGroup=true,
    $fields=null,
    $filter_group=[]
) {
    global $config;

    $user_groups = users_get_groups($id_user, $privilege, $returnAllGroup);

    $user_users = [];
    $array_user_group = [];

    if (empty($filter_group)) {
        foreach ($user_groups as $id_user_group => $name_user_group) {
            $array_user_group[] = $id_user_group;
        }
    } else {
        $array_user_group = $filter_group;
    }

    $group_users = groups_get_users($array_user_group, false, $returnAllGroup);

    foreach ($group_users as $gu) {
        if (empty($fields)) {
            $user_users[$gu['id_user']] = $gu['id_user'];
        } else {
            $fields = (array) $fields;
            foreach ($fields as $field) {
                $user_users[$gu['id_user']][$field] = $gu[$field];
            }
        }
    }

    return $user_users;
}


function users_get_strict_mode_groups($id_user, $return_group_all)
{
    global $config;

    $sql = "SELECT * FROM tusuario_perfil WHERE id_usuario = '".$id_user."' AND tags = ''";
    $user_groups = db_get_all_rows_sql($sql);

    if ($user_groups == false) {
        $user_groups = [];
    }

    $return_user_groups = [];
    if ($return_group_all) {
        $return_user_groups[0] = __('All');
    }

    foreach ($user_groups as $group) {
        $return_user_groups[$group['id_grupo']] = groups_get_name($group['id_grupo']);
    }

    return $return_user_groups;
}


/**
 * Use carefully, it consumes a lot of memory.
 *
 * @param array $group Group array.
 *
 * @return void
 */
function users_get_explode_tags(&$group)
{
    if (empty($group['tags'])) {
        $group['tags'] = [];
        $group['tags']['agent_view'] = [];
        $group['tags']['agent_edit'] = [];
        $group['tags']['agent_disable'] = [];
        $group['tags']['event_view'] = [];
        $group['tags']['event_edit'] = [];
        $group['tags']['event_management'] = [];
    } else {
        $aux = explode(',', $group['tags']);
        $group['tags'] = [];
        $group['tags']['agent_view'] = ($group['agent_view']) ? $aux : [];
        $group['tags']['agent_edit'] = ($group['agent_edit']) ? $aux : [];
        $group['tags']['agent_disable'] = ($group['agent_disable']) ? $aux : [];
        $group['tags']['event_view'] = ($group['event_view']) ? $aux : [];
        $group['tags']['event_edit'] = ($group['event_edit']) ? $aux : [];
        $group['tags']['event_management'] = ($group['event_management']) ? $aux : [];
    }

}


/**
 * Get mail admin.
 *
 * @return string Return mail admin.
 */
function get_mail_admin():string
{
    $mail = db_get_value('email', 'tusuario', 'is_admin', 1);

    return $mail;
}


/**
 * Get name admin.
 *
 * @return string Return name admin.
 */
function get_name_admin():string
{
    $mail = db_get_value('fullname', 'tusuario', 'is_admin', 1);

    return $mail;
}


/**
 * Obtiene una matriz con los grupos como clave y si tiene o no permiso UM sobre ese grupo(valor)
 *
 * @param  string User id
 * @return array Return .
 */
function users_get_groups_UM($id_user)
{
    $sql = sprintf(
        "SELECT id_grupo, user_management FROM tusuario_perfil
        LEFT JOIN tperfil ON tperfil.id_perfil = tusuario_perfil.id_perfil
        WHERE id_usuario like '%s' AND user_management = 1  ORDER BY id_grupo",
        $id_user
    );

    $groups = db_get_all_rows_sql($sql);
    $return = [];
    foreach ($groups as $key => $group) {
        if (!isset($return[$group['id_grupo']]) || (isset($return[$group['id_grupo']]) && $group['user_management'] != 0)) {
            $return[$group['id_grupo']] = $group['user_management'];
            $children = groups_get_children($group['id_grupo'], false, 'UM', false);
            foreach ($children as $key => $child_group) {
                $return[$child_group['id_grupo']] = $group['user_management'];
            }

            if ($group['id_grupo'] == '0') {
                $return['group_all'] = $group['id_grupo'];
            }
        }
    }

    return $return;
}


/**
 * Obtiene una matriz con los grupos como clave y si tiene o no permiso UM sobre ese grupo(valor)
 *
 * @param string  $id_group User id.
 * @param boolean $um       Um.
 * @param boolean $disabled Reurn also disabled users.
 *
 * @return array Return .
 */
function users_get_users_by_group($id_group, $um=false, $disabled=true)
{
    $sql = sprintf(
        "SELECT tusuario.* FROM tusuario 
        INNER JOIN tusuario_perfil ON tusuario_perfil.id_usuario = tusuario.id_user 
        AND tusuario_perfil.id_grupo = '%s'",
        $id_group
    );

    if ($disabled === false) {
        $sql .= 'WHERE tusuario.disabled = 0';
    }

    $users = db_get_all_rows_sql($sql);
    $return = [];
    foreach ($users as $key => $user) {
        $return[$user['id_user']] = $user;
        $return[$user['id_user']]['edit'] = $um;
    }

    return $return;
}


/**
 * Delete session user if exist
 *
 * @param string $id_user User id.
 *
 * @return boolean Return .
 */
function delete_session_user($id_user)
{
    $sql = "DELETE FROM tsessions_php where data like '%\"".$id_user."\"%'";
    return db_process_sql($sql);
}


function users_has_profile_without_UM($id_user, $id_groups)
{
    $sql = sprintf(
        "SELECT id_usuario, tperfil.user_management FROM tusuario_perfil
        INNER JOIN tperfil ON tperfil.id_perfil = tusuario_perfil.id_perfil AND tperfil.user_management = 0
        WHERE tusuario_perfil.id_usuario like '%s' AND tusuario_perfil.id_grupo IN (%s)
        ORDER BY tperfil.user_management DESC",
        $id_user,
        $id_groups
    );

    $without_um = db_get_all_rows_sql($sql);

    if (isset($without_um[0])) {
        $sql = sprintf(
            "SELECT id_grupo, tperfil.* FROM tusuario_perfil
            INNER JOIN tperfil ON tperfil.id_perfil = tusuario_perfil.id_perfil
            WHERE tusuario_perfil.id_usuario like '%s'
            ORDER BY tperfil.user_management DESC",
            $id_user
        );

        $um = db_get_all_rows_sql($sql);
        return 1;
    } else {
        return 0;
    }

}


function users_get_user_profile($id_user, $limit='')
{
    $sql = sprintf(
        "SELECT * FROM tusuario_perfil
        INNER JOIN tperfil ON tperfil.id_perfil = tusuario_perfil.id_perfil
        WHERE tusuario_perfil.id_usuario like '%s' %s",
        $id_user,
        $limit
    );

    $aux = db_get_all_rows_sql($sql);
    $user_profiles = [];
    foreach ($aux as $key => $value) {
        $user_profiles[$value['id_grupo']] = $value;
    }

    return $user_profiles;
}


/**
 * Obtiene una matriz con la informacion de cada usuario que pertenece a un grupo
 *
 * @param  string User id
 * @return array Return .
 */
function users_get_users_group_by_group($id_group)
{
    $sql = sprintf(
        "SELECT tusuario.* FROM tusuario 
        LEFT JOIN tusuario_perfil ON tusuario_perfil.id_usuario = tusuario.id_user 
        AND tusuario_perfil.id_grupo = '%s'
        GROUP BY tusuario_perfil.id_usuario",
        $id_group
    );

    $users = db_get_all_rows_sql($sql);

    return $users;
}


/**
 * Generates a cryptographically secure chain for use with API.
 *
 * @return string
 */
function api_token_generate()
{
    include_once 'functions_api.php';
    // Generate a cryptographically secure chain.
    $generateToken = bin2hex(openssl_random_pseudo_bytes(16));
    // Check if token exists in DB.
    $tokenExists = (bool) api_token_check($generateToken);
    // If not exists, can be assigned. In other case, try again.
    return ($tokenExists === false) ? $generateToken : api_token_generate();
}


/**
 * Returns User API Token
 *
 * @param string $idUser Id of the user.
 *
 * @return string
 */
function users_get_API_token(string $idUser)
{
    $output = db_get_value('api_token', 'tusuario', 'id_user', $idUser);

    if (empty($output) === true) {
        $output = '<< '.__('NONE').' >>';
    }

    return $output;
}


/**
 * Renews the API Token.
 *
 * @param integer $idUser Id of the user.
 *
 * @return boolean Return true if the token was renewed.
 */
function users_renew_API_token(int $idUser)
{
    $apiToken = api_token_generate();

    if (empty($apiToken) === false) {
        $result = db_process_sql_update(
            'tusuario',
            ['api_token' => $apiToken],
            ['id_user' => $idUser]
        );

        if ($result !== false) {
            return true;
        }
    }

    return false;
}


/**
 * Check if IP is in range. Check wildcard `*`, single IP and IP ranges.
 *
 * @param array  $arrayIP List of IPs.
 * @param string $userIP  IP for determine if is in the list.
 *
 * @return boolean True if IP is in range.
 */
function checkIPInRange(
    array $arrayIP,
    string $userIP=''
) {
    $output = false;

    if (empty($userIP) === true) {
        $userIP = $_SERVER['REMOTE_ADDR'];
    }

    if (empty($arrayIP) === false) {
        foreach ($arrayIP as $ip) {
            if ($ip === '*') {
                // The list has wildcard, this accept all IPs.
                $output = true;
                break;
            } else if ($ip === $userIP) {
                $output = true;
                break;
            } else if (preg_match('/([0-2]?[0-9]{1,2})[.]([0-2]?[0-9]{1,2})[.]([0-2]?[0-9]{0,2})[.](0){1}/', $ip) > 0) {
                $rangeArrayIP = explode('.', $ip);
                $userArrayIP = explode('.', $userIP);
                foreach ($rangeArrayIP as $position => $segmentIP) {
                    if ($segmentIP === $userArrayIP[$position]) {
                        $output = true;
                    } else if ((string) $segmentIP === '0') {
                        break 2;
                    } else {
                        $output = false;
                    }
                }
            } else {
                $output = false;
            }
        }
    }

    return $output;
}
