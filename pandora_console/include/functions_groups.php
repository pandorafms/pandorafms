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
global $config;

require_once $config['homedir'].'/include/functions_users.php';


/**
 * Check if the group is in use in the Pandora DB.
 *
 * @param integer $idGroup The id of group.
 *
 * @return boolean Return false if the group is unused in the Pandora, else true.
 */
function groups_check_used($idGroup)
{
    global $config;

    $return = [];
    $return['return'] = false;
    $return['tables'] = [];

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows(
                'SELECT *
				FROM tagente WHERE id_grupo = '.$idGroup.';'
            );
        break;

        case 'oracle':
            $numRows = db_get_num_rows(
                'SELECT *
				FROM tagente WHERE id_grupo = '.$idGroup
            );
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Agents');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows(
                'SELECT *
				FROM talert_actions WHERE id_group = '.$idGroup.';'
            );
        break;

        case 'oracle':
            $numRows = db_get_num_rows(
                'SELECT *
				FROM talert_actions WHERE id_group = '.$idGroup
            );
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Alert Actions');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM talert_templates WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM talert_templates WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Alert Templates');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM trecon_task WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM trecon_task WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Discovery task');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Graphs');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM treport WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM treport WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Reports');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tlayout WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tlayout WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Layout visual console');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tplanned_downtime WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tplanned_downtime WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Plannet down time');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tgraph WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Graphs');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map WHERE group_id = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map WHERE group_id = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('GIS maps');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map_connection WHERE group_id = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map_connection WHERE group_id = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('GIS connections');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map_layer WHERE tgrupo_id_grupo = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tgis_map_layer WHERE tgrupo_id_grupo = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('GIS map layers');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM tnetwork_map WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM tnetwork_map WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('Network maps');
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $numRows = db_get_num_rows('SELECT * FROM talert_snmp WHERE id_group = '.$idGroup.';');
        break;

        case 'oracle':
            $numRows = db_get_num_rows('SELECT * FROM talert_snmp WHERE id_group = '.$idGroup);
        break;
    }

    if ($numRows > 0) {
        $return['return'] = true;
        $return['tables'][] = __('SNMP alerts');
    }

    $hookEnterprise = enterprise_include_once('include/functions_groups.php');
    if ($hookEnterprise !== ENTERPRISE_NOT_HOOK) {
        $returnEnterprise = enterprise_hook('groups_check_used_group_enterprise', [$idGroup]);

        if ($returnEnterprise['return']) {
            $return['return'] = true;
            $return['tables'] = array_merge($return['tables'], $returnEnterprise['tables']);
        }
    }

    return $return;
}


/**
 * Return a array of id_group of children of given parent INCLUDING PARENT!!.
 *
 * @param integer $parent          The id_grupo parent to search its children.
 * @param array   $ignorePropagate Ignore propagate.
 * @param string  $privilege       Default privilege.
 * @param boolean $selfInclude     Include group "id_parent" in return.
 *
 * @return array Of Groups, children of $parent.
 */
function groups_get_children(
    $parent,
    $ignorePropagate=false,
    $privilege='AR',
    $selfInclude=true
) {
    static $groups;
    static $user_groups;

    if (empty($groups) === true) {
        $aux_groups = [];
        $groups = db_get_all_rows_in_table('tgrupo');
        foreach ($groups as $key => $value) {
            $aux_groups[$value['id_grupo']] = $value;
        }

        $groups = $aux_groups;
    }

    if (empty($user_groups) === true) {
        $user_groups = users_get_groups(false, $privilege, true);
    }

    // Admin see always all groups.
    $ignorePropagate = users_is_admin() || $ignorePropagate;

    // Prepare array.
    $return = [];

    if ($selfInclude === true) {
        if (array_key_exists($parent, $user_groups) === true) {
            $return[$parent] = $groups[$parent];
        }
    }

    foreach ($groups as $key => $g) {
        if ($g['id_grupo'] == 0) {
            continue;
        }

        // IgnorePropagate will be true if user can access child.
        $allowed = $ignorePropagate || array_key_exists(
            $g['id_grupo'],
            $user_groups
        );

        if ($allowed === true
            || (int) $parent === 0
            || (bool) $groups[$parent]['propagate'] === true
        ) {
            if ($g['parent'] == $parent) {
                $return += [$g['id_grupo'] => $g];
                if ($g['propagate'] || $ignorePropagate) {
                    $return += groups_get_children(
                        $g['id_grupo'],
                        $ignorePropagate,
                        $privilege,
                        $selfInclude
                    );
                }
            }
        }
    }

    return $return;
}


/**
 * Return a array of id_group of parents (to roots up).
 *
 * @param integer $parent        The id_group parent to search the parent.
 * @param boolean $onlyPropagate Flag to search only parents that true to propagate.
 * @param array   $groups        The groups, its for optimize the querys to DB.
 */
function groups_get_parents($parent, $onlyPropagate=false, $groups=null)
{
    if (empty($groups)) {
        $groups = db_get_all_rows_in_table('tgrupo');
    }

    $return = [];
    foreach ($groups as $key => $group) {
        if ($group['id_grupo'] == 0) {
            continue;
        }

        if (($group['id_grupo'] == $parent)
            && ($group['propagate'] || !$onlyPropagate)
        ) {
            $return = ($return + [$group['id_grupo'] => $group] + groups_get_parents($group['parent'], $onlyPropagate, $groups));
        }
    }

    return $return;
}


/**
 * Filter out groups the user doesn't have access to
 *
 * Access can be:
 * IR - Incident Read
 * IW - Incident Write
 * IM - Incident Management
 * AR - Agent Read
 * AW - Agent Write
 * LW - Alert Write
 * UM - User Management
 * DM - DB Management
 * LM - Alert Management
 * PM - Pandora Management
 *
 * @param integer $id_user  User id
 * @param mixed   $id_group Group ID(s) to check
 * @param string  $access   Access privilege
 *
 * @return array Groups the user DOES have acces to (or an empty array)
 */
function groups_safe_acl($id_user, $id_groups, $access)
{
    if (!is_array($id_groups) && check_acl($id_user, $id_groups, $access)) {
        // Return all the user groups if it's the group All
        if ($id_groups == 0) {
            return array_keys(users_get_groups($id_user, $access));
        }

        return [$id_groups];
    } else if (!is_array($id_groups)) {
        return [];
    }

    foreach ($id_groups as $group) {
        // Check ACL. If it doesn't match, remove the group
        if (!check_acl($id_user, $group, $access)) {
            unset($id_groups[$group]);
        }
    }

    return $id_groups;
}


/**
 * Get disabled field of a group
 *
 * @param int id_group Group id
 *
 * @return boolean Disabled field of given group
 */
function groups_give_disabled_group($id_group)
{
    return (bool) db_get_value('disabled', 'tgrupo', 'id_grupo', (int) $id_group);
}


/**
 * Get group icon from group.
 *
 * @param int id_group Id group to get the icon
 *
 * @return string Icon path of the given group
 */
function groups_get_icon($id_group)
{
    if ((int) $id_group === 0) {
        $icon = 'unknown@groups.svg';
    } else {
        $icon = (string) db_get_value('icon', 'tgrupo', 'id_grupo', (int) $id_group);

        $extension = pathinfo($icon, PATHINFO_EXTENSION);
        if (empty($extension) === true) {
            $icon .= '.png';
        }

        if (empty($extension) === true || $extension === 'png') {
            $icon = 'groups_small/'.$icon;
        }

        if (empty($icon) === true) {
            $icon = 'unknown@groups.svg';
        }
    }

    return $icon;
}


/**
 * Get all groups in array with index as id_group.
 *
 * @param bool Whether to return All group or not
 *
 * @return array with all groups selected
 */
function groups_get_all($groupWithAgents=false)
{
    global $config;

    $sql = 'SELECT id_grupo, nombre FROM tgrupo';

    global $config;

    if ($groupWithAgents) {
        $sql .= ' WHERE id_grupo IN (
		SELECT id_grupo
		FROM tagente
		GROUP BY id_grupo)';
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            $sql .= ' ORDER BY nombre DESC';
        break;

        case 'oracle':
            $sql .= ' ORDER BY dbms_lob.substr(nombre,4000,1) DESC';
        break;
    }

    $rows = db_get_all_rows_sql($sql);

    if ($rows === false) {
        $rows = [];
    }

    $return = [];
    foreach ($rows as $row) {
        if (check_acl($config['id_user'], $row['id_grupo'], 'AR')) {
            $return[$row['id_grupo']] = $row['nombre'];
        }
    }

    return $return;
}


/**
 * Get all groups recursive from an initial group  INCLUDING PARENT!!.
 *
 * @param integer $id_parent       Id of the parent group.
 * @param boolean $ignorePropagate Whether to force recursive search ignoring
 *                                 propagation (true) or not (false).
 * @param boolean $selfInclude     Include group "id_parent" in return.
 * @param string  $privilege       Privilege flag to search for default 'AR'.
 *
 * @return array With all result groups.
 */
function groups_get_children_ids(
    $id_parent,
    $ignorePropagate=false,
    $selfInclude=true,
    $privilege='AR'
) {
    $return = groups_get_children(
        $id_parent,
        $ignorePropagate,
        $privilege,
        $selfInclude
    );

    return array_keys($return);
}


function groups_flatten_tree_groups($tree, $deep)
{
    if (is_array($tree) === true) {
        foreach ($tree as $key => $group) {
            $return[$key] = $group;
            unset($return[$key]['branch']);
            $return[$key]['deep'] = $deep;

            if (empty($group['branch']) === false) {
                $return = ($return + groups_flatten_tree_groups($group['branch'], ($deep + 1)));
            }
        }
    } else {
        $return = [];
    }

    return $return;
}


/**
 * Make with a list of groups a treefied list of groups.
 *
 * @param array   $groups The list of groups to create the treefield list.
 * @param integer $parent The id_group of parent actual scan branch.
 * @param integer $deep   The level of profundity in the branch.
 *
 * @return array The treefield list of groups.
 */
function groups_get_groups_tree_recursive($groups, $trash=0, $trash2=0)
{
    $return = [];

    $tree = $groups;
    foreach ($groups as $key => $group) {
        if (is_array($group) === false || (int) $group['id_grupo'] === 0) {
            continue;
        }

        // If the user has ACLs on a gruop but not in his father,
        // we consider it as a son of group "all".
        if (isset($groups[$group['parent']]) === false) {
            $group['parent'] = 0;
        }

        if (is_array(($tree[$group['parent']] ?? null)) === false) {
            $tree[$group['parent']] = [
                'nombre'   => ($tree[$group['parent']] ?? ''),
                'id_grupo' => $group['parent'],
            ];
        }

        $tree[$group['parent']]['hash_branch'] = 1;
        $tree[$group['parent']]['branch'][$key] = &$tree[$key];
    }

    // Depends on the All group we give different format.
    if (isset($groups[0]) === true) {
        $tree = [$tree[0]];
    } else {
        $tree = $tree[0]['branch'];
    }

    $return = groups_flatten_tree_groups($tree, 0);

    return $return;
}


/**
 * Get agent status of a group.
 *
 * @param integer If of the group.
 *
 * @return integer Status of the agents.
 */
function groups_get_status($id_group=0, $ignore_alerts=false)
{
    global $config;

    include_once $config['homedir'].'/include/functions_reporting.php';

    $data = reporting_get_group_stats_resume($id_group);

    if ($data['monitor_alerts_fired'] > 0 && $ignore_alerts == false) {
        return AGENT_STATUS_ALERT_FIRED;
    } else if ($data['agent_critical'] > 0) {
        return AGENT_STATUS_CRITICAL;
    } else if ($data['agent_warning'] > 0) {
        return AGENT_STATUS_WARNING;
    } else if ($data['agent_unknown'] > 0) {
        return AGENT_STATUS_UNKNOWN;
    } else {
        return AGENT_STATUS_NORMAL;
    }
}


/**
 * This function gets the group name for a given group id
 *
 * @param int The group id
 * @param boolean          $returnAllGroup Flag the return group, by default false.
 *
 * @return string The group name
 */
function groups_get_name($id_group, $returnAllGroup=false)
{
    if ($id_group > 0) {
        return (string) db_get_value('nombre', 'tgrupo', 'id_grupo', (int) $id_group);
    } else if ($returnAllGroup) {
        return __('All');
    }
}


/**
 * Return the id of a group given its name.
 *
 * @param string Name of the group.
 *
 * @return integer The id of the given group.
 */
function groups_get_id($group_name, $returnAllGroup=false)
{
    return db_get_value('id_grupo', 'tgrupo', 'nombre', $group_name);
}


/**
 * Get all the users belonging to a group.
 *
 * @param integer                                                            $id_group The group id to look for
 * @param mixed filter array
 * @param bool True if users with all permissions in the group are retrieved
 *
 * @return array An array with all the users or an empty array
 */
function groups_get_users($id_group, $filter=false, $return_user_all=false)
{
    global $config;

    if (! is_array($filter)) {
        $filter = [];
    }

    if ($return_user_all) {
        if (is_array($id_group)) {
            $filter['id_grupo'] = $id_group;
        } else {
            $filter['id_grupo'][0] = $id_group;
        }

        array_push($filter['id_grupo'], 0);
    } else {
        $filter['id_grupo'] = $id_group;
    }

    $query = 'SELECT tu.*
		FROM tusuario tu, tusuario_perfil tup
		WHERE tup.id_usuario = tu.id_user';

    if (is_array($filter)) {
        foreach ($filter as $key => $value) {
            if ($key != 'limit' && $key != 'order'
                && $key != 'offset' && $key != 'group'
            ) {
                $filter_array['tup.'.$key] = $value;
            } else {
                $filter_array[$key] = $value;
            }
        }

        $clause_sql = mysql_db_format_array_where_clause_sql($filter_array, 'AND', false);
        if ($clause_sql) {
            $query .= ' AND '.$clause_sql;
        }
    }

    $result = db_get_all_rows_sql($query);

    if ($result === false) {
        return [];
    }

    return $result;
}


/**
 * Gets a group by id_group
 *
 * @param integer $id_group The group id of the row
 *
 * @return mixed Return the group row or false
 */
function groups_get_group_by_id($id_group)
{
    $result_group = db_get_row('tgrupo', 'id_grupo', $id_group);

    return $result_group;
}


/**
 * Create new group
 *
 * @param string Group name
 * @param array Rest of the fields of the group
 *
 * @return mixed Return group_id or false if something goes wrong
 */
function groups_create_group($group_name, $rest_values)
{
    if ($group_name == '') {
        return false;
    }

    $array_tmp = ['nombre' => $group_name];

    $values = array_merge($rest_values, $array_tmp);

    if (isset($values['propagate']) === false) {
        $values['propagate'] = 0;
    }

    if (isset($values['disabled']) === false) {
        $values['disabled'] = 0;
    }

    if (isset($values['max_agents']) === false) {
        $values['max_agents'] = 0;
    }

    $check = db_get_value('nombre', 'tgrupo', 'nombre', $group_name);

    if (!$check) {
        $result = db_process_sql_insert('tgrupo', $values);
    } else {
        $result = false;
    }

    return $result;
}


/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'status': (mixed) Agent status. Single or grouped into an array. e.g.: AGENT_STATUS_CRITICAL.
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_agents_counter($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    if (empty($group)) {
        return 0;
    } else if (is_array($group)) {
        $groups = $group;
    } else {
        $groups = [$group];
    }

    $group_str = implode(',', $groups);
    $groups_clause = "AND ta.id_grupo IN ($group_str)";

    $tags_clause = '';

    $agent_name_filter = '';
    $agent_status = AGENT_STATUS_ALL;
    if (!empty($agent_filter)) {
        // Name
        if (isset($agent_filter['name']) && !empty($agent_filter['name'])) {
            $agent_name_filter = "AND ta.nombre LIKE '%".$agent_filter['name']."%'";
        }

        // Status
        if (isset($agent_filter['status'])) {
            if (is_array($agent_filter['status'])) {
                $agent_status = array_unique($agent_filter['status']);
            } else {
                $agent_status = $agent_filter['status'];
            }
        }
    }

    $module_name_filter = '';
    $module_status_filter = '';
    $module_status_array = [];
    if (!empty($module_filter)) {
        // IMPORTANT: The module filters will force the realtime search
        $realtime = true;

        // Name
        if (isset($module_filter['name']) && !empty($module_filter['name'])) {
            $module_name_filter = "AND tam.nombre LIKE '%".$module_filter['name']."%'";
        }

        // Status
        if (isset($module_filter['status'])) {
            $module_status = $module_filter['status'];
            if (is_array($module_status)) {
                $module_status = array_unique($module_status);
            } else {
                $module_status = [$module_status];
            }

            foreach ($module_status as $status) {
                switch ($status) {
                    case AGENT_MODULE_STATUS_ALL:
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING;
                        $module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                        $module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                    break;

                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case AGENT_MODULE_STATUS_WARNING:
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_WARNING;
                    break;

                    case AGENT_MODULE_STATUS_UNKNOWN:
                        $module_status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                    break;

                    case AGENT_MODULE_STATUS_NO_DATA:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                        $module_status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $module_status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                    break;

                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case AGENT_MODULE_STATUS_NORMAL:
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $module_status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;
                }
            }

            if (!empty($module_status_array)) {
                $module_status_array = array_unique($module_status_array);
                $status_str = implode(',', $module_status_array);

                $module_status_filter = "INNER JOIN tagente_estado tae
											ON tam.id_agente_modulo = tae.id_agente_modulo
												AND tae.estado IN ($status_str)";
            }
        }
    }

    $count = 0;
    // Realtime
    if ($realtime) {
        $sql = "SELECT DISTINCT ta.id_agente
				FROM tagente ta
				INNER JOIN tagente_modulo tam
					ON ta.id_agente = tam.id_agente
						AND tam.disabled = 0
						$module_name_filter
				$module_status_filter
				WHERE ta.disabled = 0
					$agent_name_filter
					$groups_clause
					$tags_clause";
        $agents = db_get_all_rows_sql($sql);

        if ($agents === false) {
            return $count;
        }

        if ($agent_status == AGENT_STATUS_ALL) {
            return count($agents);
        }

        foreach ($agents as $agent) {
            $agent_filter['id'] = $agent['id_agente'];

            $total = 0;
            $critical = 0;
            $warning = 0;
            $unknown = 0;
            $not_init = 0;
            $normal = 0;
            if (empty($module_status_array)) {
                $total = (int) groups_get_total_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                $critical = (int) groups_get_critical_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                $warning = (int) groups_get_warning_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                $unknown = (int) groups_get_unknown_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                $not_init = (int) groups_get_not_init_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                $normal = (int) groups_get_normal_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
            } else {
                foreach ($module_status_array as $status) {
                    switch ($status) {
                        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                        case AGENT_MODULE_STATUS_CRITICAL_BAD:
                            $critical = (int) groups_get_critical_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                        break;

                        case AGENT_MODULE_STATUS_WARNING_ALERT:
                        case AGENT_MODULE_STATUS_WARNING:
                            $warning = (int) groups_get_warning_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                        break;

                        case AGENT_MODULE_STATUS_UNKNOWN:
                            $unknown = (int) groups_get_unknown_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                        break;

                        case AGENT_MODULE_STATUS_NO_DATA:
                        case AGENT_MODULE_STATUS_NOT_INIT:
                            $not_init = (int) groups_get_not_init_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                        break;

                        case AGENT_MODULE_STATUS_NORMAL_ALERT:
                        case AGENT_MODULE_STATUS_NORMAL:
                            $normal = (int) groups_get_normal_monitors($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
                        break;
                    }
                }

                $total = ($critical + $warning + $unknown + $not_init + $normal);
            }

            if (!is_array($agent_status)) {
                switch ($agent_status) {
                    case AGENT_STATUS_CRITICAL:
                        if ($critical > 0) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_WARNING:
                        if (($total > 0) && ($critical == 0) && ($warning > 0)) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_UNKNOWN:
                        if ($critical == 0 && $warning == 0 && $unknown > 0) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_NOT_INIT:
                        if ($total == 0 || $total == $not_init) {
                            $count++;
                        }
                    break;

                    case AGENT_STATUS_NORMAL:
                        if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0) {
                            $count++;
                        }
                    break;

                    default:
                        // The status doesn't exist
                    return 0;
                }
            } else {
                if (array_search(AGENT_STATUS_CRITICAL, $agent_status) !== false) {
                    if ($critical > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_WARNING, $agent_status) !== false) {
                    if ($total > 0 && $critical = 0 && $warning > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_UNKNOWN, $agent_status) !== false) {
                    if ($critical == 0 && $warning == 0 && $unknown > 0) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_NOT_INIT, $agent_status) !== false) {
                    if ($total == 0 || $total == $not_init) {
                        $count++;
                    }
                } else if (array_search(AGENT_STATUS_NORMAL, $agent_status) !== false) {
                    if ($critical == 0 && $warning == 0 && $unknown == 0 && $normal > 0) {
                        $count++;
                    }
                }
                // Invalid status
                else {
                    return 0;
                }
            }
        }
    }
    // Server processed
    else {
        $status_filter = '';
        // Transform the element into a one element array
        if (!is_array($agent_status)) {
            $agent_status = [$agent_status];
        }

        // Support for multiple status. It counts the agents for each status and sum the result
        foreach ($agent_status as $status) {
            switch ($agent_status) {
                case AGENT_STATUS_ALL:
                    $status_filter = '';
                break;

                case AGENT_STATUS_CRITICAL:
                    $status_filter = 'AND ta.critical_count > 0';
                break;

                case AGENT_STATUS_WARNING:
                    $status_filter = 'AND ta.total_count > 0
									AND ta.critical_count = 0
									AND ta.warning_count > 0';
                break;

                case AGENT_STATUS_UNKNOWN:
                    $status_filter = 'AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count > 0';
                break;

                case AGENT_STATUS_NOT_INIT:
                    $status_filter = 'AND (ta.total_count = 0
										OR ta.total_count = ta.notinit_count)';
                break;

                case AGENT_STATUS_NORMAL:
                    $status_filter = 'AND ta.critical_count = 0
									AND ta.warning_count = 0
									AND ta.unknown_count = 0
									AND ta.normal_count > 0';
                break;

                default:
                    // The type doesn't exist
                return 0;
            }

            $sql = "SELECT COUNT(DISTINCT ta.id_agente) 
					FROM tagente ta
					WHERE ta.disabled = 0
						$agent_name_filter
						$status_filter
						$groups_clause";

            $res = db_get_sql($sql);
            if ($res !== false) {
                $count += $res;
            }
        }
    }

    return $count;
}


/**
 * Get the number of the agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_total_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_ALL;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the normal agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_normal_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_NORMAL;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the critical agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_critical_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_CRITICAL;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the warning agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_warning_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_WARNING;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the unknown agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_unknown_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_UNKNOWN;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the not init agents that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of agents.
 */
function groups_get_not_init_agents($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false)
{
    // Always modify the agent status filter
    $agent_filter['status'] = AGENT_STATUS_NOT_INIT;
    return groups_get_agents_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime);
}


/**
 * Get the number of the monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (mixed) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'status': (mixed) Module status. Single or grouped into an array. e.g.: AGENT_MODULE_STATUS_CRITICAL.
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_monitors_counter($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    if (empty($group)) {
        return 0;
    } else if (is_array($group)) {
        $groups = $group;
    } else {
        $groups = [$group];
    }

    if ($strict_user) {
        $realtime = true;
    }

    $group_str = implode(',', $groups);
    if ($secondary_group === true) {
        $groups_clause = "AND (ta.id_grupo IN ($group_str) OR tasg.id_group IN ($group_str))";
    } else {
        $groups_clause = "AND (ta.id_grupo IN ($group_str))";
    }

    $tags_clause = '';

    $agent_name_filter = '';
    $agents_clause = '';
    if (!empty($agent_filter)) {
        // Name
        if (isset($agent_filter['name']) && !empty($agent_filter['name'])) {
            $agent_name_filter = "AND ta.nombre LIKE '%".$agent_filter['name']."%'";
        }

        // ID
        if (isset($agent_filter['id'])) {
            if (is_array($agent_filter['id'])) {
                $agents = array_unique($agent_filter['id']);
            } else {
                $agents = [$agent_filter['id']];
            }

            $agents_str = implode(',', $agents);
            $agents_clause = "AND ta.id_agente IN ($agents_str)";
        }
    }

    $module_name_filter = '';
    $module_status_array = '';
    $modules_clause = '';
    if (!empty($module_filter)) {
        // Name
        if (isset($module_filter['name']) && !empty($module_filter['name'])) {
            // IMPORTANT: The module filters will force the realtime search
            $realtime = true;

            $module_name_filter = "AND tam.nombre LIKE '%".$module_filter['name']."%'";
        }

        // Status
        if (isset($module_filter['status'])) {
            if (is_array($module_filter['status'])) {
                $module_status = array_unique($module_filter['status']);
            } else {
                $module_status = [$module_filter['status']];
            }

            $status_array = [];
            foreach ($module_status as $status) {
                switch ($status) {
                    case AGENT_MODULE_STATUS_ALL:
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING;
                        $status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                        $status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                    case AGENT_MODULE_STATUS_CRITICAL_BAD:
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_CRITICAL_BAD;
                    break;

                    case AGENT_MODULE_STATUS_WARNING_ALERT:
                    case AGENT_MODULE_STATUS_WARNING:
                        $status_array[] = AGENT_MODULE_STATUS_WARNING_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_WARNING;
                    break;

                    case AGENT_MODULE_STATUS_UNKNOWN:
                        $status_array[] = AGENT_MODULE_STATUS_UNKNOWN;
                    break;

                    case AGENT_MODULE_STATUS_NO_DATA:
                    case AGENT_MODULE_STATUS_NOT_INIT:
                        $status_array[] = AGENT_MODULE_STATUS_NO_DATA;
                        $status_array[] = AGENT_MODULE_STATUS_NOT_INIT;
                    break;

                    case AGENT_MODULE_STATUS_NORMAL_ALERT:
                    case AGENT_MODULE_STATUS_NORMAL:
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL_ALERT;
                        $status_array[] = AGENT_MODULE_STATUS_NORMAL;
                    break;

                    default:
                        // The status doesn't exist
                    return 0;
                }
            }

            if (!empty($status_array)) {
                $status_array = array_unique($status_array);
                $status_str = implode(',', $status_array);

                $modules_clause = "AND tae.estado IN ($status_str)";
            }
        }
    }

    if ($realtime) {
        $sql = "SELECT COUNT(DISTINCT tam.id_agente_modulo)
				FROM tagente_modulo tam
				INNER JOIN tagente_estado tae
					ON tam.id_agente_modulo = tae.id_agente_modulo
						$modules_clause
				INNER JOIN tagente ta
					ON tam.id_agente = ta.id_agente";
        if ($secondary_group === true) {
            $sql .= ' LEFT JOIN tagent_secondary_group tasg ON ta.id_agente = tasg.id_agent';
        }

        $sql .= "AND ta.disabled = 0
						$agent_name_filter
						$agents_clause
				WHERE tam.disabled = 0
					$module_name_filter
					$groups_clause
					$tags_clause";
    } else {
        $status_columns_array = [];
        foreach ($status_array as $status) {
            switch ($status) {
                case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                case AGENT_MODULE_STATUS_CRITICAL_BAD:
                    $status_columns_array['critical'] = 'critical_count';
                break;

                case AGENT_MODULE_STATUS_WARNING_ALERT:
                case AGENT_MODULE_STATUS_WARNING:
                    $status_columns_array['warn'] = 'warning_count';
                break;

                case AGENT_MODULE_STATUS_UNKNOWN:
                    $status_columns_array['unk'] = 'unknown_count';
                break;

                case AGENT_MODULE_STATUS_NO_DATA:
                case AGENT_MODULE_STATUS_NOT_INIT:
                    $status_columns_array['notinit'] = 'notinit_count';
                break;

                case AGENT_MODULE_STATUS_NORMAL_ALERT:
                case AGENT_MODULE_STATUS_NORMAL:
                    $status_columns_array['normal'] = 'normal_count';
                break;

                default:
                    // The type doesn't exist
                return 0;
            }
        }

        if (empty($status_columns_array)) {
            return 0;
        }

        $status_columns_str = implode(',', $status_columns_array);
        $status_columns_str_sum = implode('+', $status_columns_array);

        $sql = "SELECT SUM($status_columns_str_sum) FROM
			(SELECT DISTINCT(ta.id_agente), $status_columns_str
				FROM tagente ta LEFT JOIN tagent_secondary_group tasg
					ON ta.id_agente = tasg.id_agent
				WHERE ta.disabled = 0
					$agent_name_filter
					$agents_clause
					$groups_clause
					$tags_clause
			) AS t1";
    }

    $count = (int) db_get_sql($sql);

    return $count;
}


/**
 * Get the number of the monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_total_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_ALL;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


/**
 * Get the number of the normal monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_normal_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_NORMAL;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


/**
 * Get the number of the critical monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_critical_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_CRITICAL_BAD;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


/**
 * Get the number of the warning monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_warning_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_WARNING;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


/**
 * Get the number of the unknown monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_unknown_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_UNKNOWN;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


/**
 * Get the number of the not init monitors that pass the filters.
 *
 * @param mixed   $group           Id in integer or a set of ids into an array.
 * @param array   $agent_filter    Filter of the agents.
 *      This filter support the following fields:
 *      -'name': (string) Agent name. e.g.: "agent_1".
 *      -'id': (int/array) Agent id. e.g.: "1".
 * @param array   $module_filter   Filter of the modules.
 *     This filter support the following fields:
 *     -'name': (string) Module name. e.g.: "module_1".
 * @param boolean $strict_user     If the user has enabled the strict ACL mode or not.
 * @param array   $groups_and_tags Array with strict ACL rules.
 * @param boolean $realtime        Search realtime values or the values processed by the server.
 *
 * @return integer Number of monitors.
 */
function groups_get_not_init_monitors($group, $agent_filter=[], $module_filter=[], $strict_user=false, $groups_and_tags=false, $realtime=false, $secondary_group=true)
{
    // Always modify the module status filter
    $module_filter['status'] = AGENT_MODULE_STATUS_NOT_INIT;
    return groups_get_monitors_counter($group, $agent_filter, $module_filter, $strict_user, $groups_and_tags, $realtime, $secondary_group);
}


// Get alerts defined for a given group, except disabled
function groups_monitor_alerts($group_array)
{
    $total = groups_monitor_alerts_total_counters($group_array);
    return $total['total'];
}


// Get alert configured currently FIRED, except disabled
function groups_monitor_fired_alerts($group_array)
{
    $total = groups_monitor_alerts_total_counters($group_array);
    return $total['fired'];
}


function groups_monitor_alerts_total_counters($group_array, $secondary_group=true)
{
    // If there are not groups to query, we jump to nextone
    $default_total = [
        'total' => 0,
        'fired' => 0,
    ];
    if (empty($group_array)) {
        return $default_total;
    } else if (!is_array($group_array)) {
        $group_array = [$group_array];
    }

    $group_clause = implode(',', $group_array);
    if ($secondary_group === true) {
        $group_clause = "(tasg.id_group IN ($group_clause) OR ta.id_grupo IN ($group_clause))";
    } else {
        $group_clause = "(ta.id_grupo IN ($group_clause))";
    }

    $sql = 'SELECT
                COUNT(tatm.id) AS total,
                SUM(IF(tatm.times_fired > 0, 1, 0)) AS fired
            FROM talert_template_modules tatm
            INNER JOIN tagente_modulo tam
                ON tatm.id_agent_module = tam.id_agente_modulo
            INNER JOIN tagente ta
                ON ta.id_agente = tam.id_agente
            WHERE ta.id_agente IN (
                SELECT ta.id_agente
                FROM tagente ta';
    if ($secondary_group === true) {
        $sql .= ' LEFT JOIN tagent_secondary_group tasg ON ta.id_agente = tasg.id_agent';
    }

    $sql .= " WHERE ta.disabled = 0
                    AND $group_clause
            ) AND tam.disabled = 0";

    $alerts = db_get_row_sql($sql);

    return ($alerts === false) ? $default_total : $alerts;
}


function groups_monitor_total_counters($group_array, $search_in_testado=false)
{
    $default_total = [
        'ok'       => 0,
        'critical' => 0,
        'warning'  => 0,
        'unknown'  => 0,
        'not_init' => 0,
        'total'    => 0,
    ];
    if (empty($group_array)) {
        return $default_total;
    } else if (!is_array($group_array)) {
        $group_array = [$group_array];
    }

    $group_clause = implode(',', $group_array);
    $group_clause = "(tasg.id_group IN ($group_clause) OR ta.id_grupo IN ($group_clause))";

    if ($search_in_testado) {
        $condition_critical = modules_get_state_condition(AGENT_MODULE_STATUS_CRITICAL_ALERT);
        $condition_warning = modules_get_state_condition(AGENT_MODULE_STATUS_WARNING_ALERT);
        $condition_unknown = modules_get_state_condition(AGENT_MODULE_STATUS_UNKNOWN);
        $condition_not_init = modules_get_state_condition(AGENT_MODULE_STATUS_NO_DATA);
        $condition_normal = modules_get_state_condition(AGENT_MODULE_STATUS_NORMAL);
        $sql = "SELECT SUM(IF($condition_normal, 1, 0)) AS ok,
				SUM(IF($condition_critical, 1, 0)) AS critical,
				SUM(IF($condition_warning, 1, 0)) AS warning,
				SUM(IF($condition_unknown, 1, 0)) AS unknown,
				SUM(IF($condition_not_init, 1, 0)) AS not_init,
				COUNT(tam.id_agente_modulo) AS total
			FROM tagente ta
			INNER JOIN tagente_modulo tam
				ON ta.id_agente = tam.id_agente
			INNER JOIN tagente_estado tae
				ON tam.id_agente_modulo = tae.id_agente_modulo
			WHERE ta.disabled = 0 AND tam.disabled = 0
				AND ta.id_agente IN (
					SELECT ta.id_agente FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					WHERE ta.disabled = 0
						AND $group_clause
					GROUP BY ta.id_agente
				)
			";
    } else {
        $sql = "SELECT SUM(ta.normal_count) AS ok,
				SUM(ta.critical_count) AS critical,
				SUM(ta.warning_count) AS warning,
				SUM(ta.unknown_count) AS unknown,
				SUM(ta.notinit_count) AS not_init,
				SUM(ta.total_count) AS total
			FROM tagente ta
			WHERE ta.disabled = 0
				AND ta.id_agente IN (
					SELECT ta.id_agente FROM tagente ta
					LEFT JOIN tagent_secondary_group tasg
						ON ta.id_agente = tasg.id_agent
					WHERE ta.disabled = 0
						AND $group_clause
					GROUP BY ta.id_agente
				)
		";
    }

    $monitors = db_get_row_sql($sql);

    return ($monitors === false) ? $default_total : $monitors;
}


function groups_agents_total_counters($group_array, $secondary_groups=true)
{
    $default_total = [
        'ok'       => 0,
        'critical' => 0,
        'warning'  => 0,
        'unknown'  => 0,
        'not_init' => 0,
        'total'    => 0,
    ];
    if (empty($group_array)) {
        return $default_total;
    } else if (!is_array($group_array)) {
        $group_array = [$group_array];
    }

    $group_clause = implode(',', $group_array);
    if ($secondary_groups === true) {
        $group_clause = "(tasg.id_group IN ($group_clause) OR ta.id_grupo IN ($group_clause))";
    } else {
        $group_clause = "(ta.id_grupo IN ($group_clause))";
    }

    $condition_critical = agents_get_status_clause(AGENT_STATUS_CRITICAL);
    $condition_warning = agents_get_status_clause(AGENT_STATUS_WARNING);
    $condition_unknown = agents_get_status_clause(AGENT_STATUS_UNKNOWN);
    $condition_not_init = agents_get_status_clause(AGENT_STATUS_NOT_INIT);
    $condition_normal = agents_get_status_clause(AGENT_STATUS_NORMAL);

    $sql = "SELECT SUM(IF($condition_normal, 1, 0)) AS ok,
			SUM(IF($condition_critical, 1, 0)) AS critical,
			SUM(IF($condition_warning, 1, 0)) AS warning,
			SUM(IF($condition_unknown, 1, 0)) AS unknown,
			SUM(IF($condition_not_init, 1, 0)) AS not_init,
			COUNT(ta.id_agente) AS total
            FROM tagente ta
            WHERE ta.disabled = 0
			AND ta.id_agente IN (
            SELECT ta.id_agente FROM tagente ta";
    if ($secondary_groups === true) {
        $sql .= ' LEFT JOIN tagent_secondary_group tasg ON ta.id_agente = tasg.id_agent';
    }

    $sql .= " WHERE ta.disabled = 0
			  AND $group_clause
			  GROUP BY ta.id_agente
			)";

    $agents = db_get_row_sql($sql);

    return ($agents === false) ? $default_total : $agents;
}


/**
 * Return an array with the groups hierarchy (Recursive)
 *
 * @param array Groups array passed by reference
 * @param mixed The id of the parent to search or false to begin the search from the first hierarchy level
 *
 * @return array The groups reordered by its hierarchy
 */
function groups_get_tree(&$groups, $parent=false)
{
    $return = [];

    foreach ($groups as $id => $group) {
        if ($parent === false && (!isset($group['parent']) || $group['parent'] == 0 || !in_array($group['parent'], $groups))) {
            $return[$id] = $group;
            unset($groups[$id]);
            $children = groups_get_tree($groups, $id);

            if (!empty($children)) {
                $return[$id]['children'] = $children;
            } else {
                $return[$id]['children'] = [];
            }
        } else if ($parent && isset($group['parent']) && $group['parent'] == $parent) {
            $return[$id] = $group;
            unset($groups[$id]);
            $children = groups_get_tree($groups, $id);

            if (!empty($children)) {
                $return[$id]['children'] = $children;
            } else {
                $return[$id]['children'] = [];
            }
        } else {
            continue;
        }
    }

    return $return;
}


function groups_get_tree_good(&$groups, $parent, &$childs)
{
    if (isset($parent) === false) {
        $parent = false;
    }

    $return = [];

    foreach ($groups as $id => $group) {
        if ($group['parent'] != 0) {
            $childs[$id] = $id;
        }

        if ($parent === false && (!isset($group['parent']) || $group['parent'] == 0 || !in_array($group['parent'], $groups))) {
            $return[$id] = $group;
            // unset($groups[$id]);
            $children = groups_get_tree_good($groups, $id, $noUse);

            if (!empty($children)) {
                $return[$id]['children'] = $children;
            } else {
                $return[$id]['children'] = [];
            }
        } else if ($parent && isset($group['parent']) && $group['parent'] == $parent) {
            $return[$id] = $group;
            // unset($groups[$id]);
            $children = groups_get_tree_good($groups, $id, $noUse);

            if (!empty($children)) {
                $return[$id]['children'] = $children;
            } else {
                $return[$id]['children'] = [];
            }
        } else {
            continue;
        }
    }

    return $return;
}


function groups_get_tree_keys($groups, &$group_keys)
{
    foreach ($groups as $id => $group) {
        $group_keys[$id] = $id;
        if (isset($group['children'])) {
            groups_get_tree_keys($groups[$id]['children'], $group_keys);
        }
    }
}


function group_get_data(
    $id_user=false,
    $user_strict=false,
    $acltags=[],
    $returnAllGroup=false,
    $mode='group',
    $agent_filter=[],
    $module_filter=[]
) {
    global $config;
    if ($id_user == false) {
        $id_user = $config['id_user'];
    }

    $user_groups = [];
    $user_tags = [];
    foreach ($acltags as $group => $tags) {
        $user_groups[$group] = groups_get_name($group);
        if ($tags != '') {
            $tags_group = explode(',', $tags);

            foreach ($tags_group as $tag) {
                $user_tags[$tag] = tags_get_name($tag);
            }
        }
    }

    $user_groups_ids = implode(',', array_keys($acltags));

    if (!empty($user_groups_ids)) {
        $list_groups = db_get_all_rows_sql(
            '
			SELECT *
			FROM tgrupo
			WHERE id_grupo IN ('.$user_groups_ids.')
			ORDER BY nombre ASC'
        );
    }

    $list = [];

    if ($list_groups == false) {
        $list_groups = [];
    }

    if ($returnAllGroup) {
        $i = 1;
        $list[0]['_id_'] = 0;
        $list[0]['_name_'] = __('All');

        $list[0]['_agents_unknown_'] = 0;
        $list[0]['_monitors_alerts_fired_'] = 0;
        $list[0]['_total_agents_'] = 0;
        $list[0]['_monitors_ok_'] = 0;
        $list[0]['_monitors_critical_'] = 0;
        $list[0]['_monitors_warning_'] = 0;
        $list[0]['_monitors_unknown_'] = 0;
        $list[0]['_monitors_not_init_'] = 0;
        $list[0]['_agents_not_init_'] = 0;

        if ($mode == 'tactical') {
            $list[0]['_agents_ok_'] = 0;
            $list[0]['_agents_warning_'] = 0;
            $list[0]['_agents_critical_'] = 0;
            $list[0]['_monitors_alerts_'] = 0;
        }
    } else {
        $i = 0;
    }

    /*
     * Agent cache for metaconsole.
     * Retrieve the statistic data from the cache table.
     */
    if (is_metaconsole() && !empty($list_groups)) {
        $cache_table = 'tmetaconsole_agent';

        $sql_stats = "SELECT id_grupo, COUNT(id_agente) AS agents_total,
				SUM(total_count) AS monitors_total,
				SUM(normal_count) AS monitors_ok,
				SUM(warning_count) AS monitors_warning,
				SUM(critical_count) AS monitors_critical,
				SUM(unknown_count) AS monitors_unknown,
				SUM(notinit_count) AS monitors_not_init,
				SUM(fired_count) AS alerts_fired
			FROM $cache_table
			WHERE disabled = 0
				AND id_grupo IN ($user_groups_ids)
			GROUP BY id_grupo";
        $data_stats = db_get_all_rows_sql($sql_stats);

        $sql_stats_unknown = "SELECT id_grupo, COUNT(id_agente) AS agents_unknown
			FROM $cache_table
			WHERE disabled = 0
				AND id_grupo IN ($user_groups_ids)
				AND critical_count = 0
				AND warning_count = 0
				AND unknown_count > 0
			GROUP BY id_grupo";
        $data_stats_unknown = db_get_all_rows_sql($sql_stats_unknown);

        $sql_stats_not_init = "SELECT id_grupo, COUNT(id_agente) AS agents_not_init
			FROM $cache_table
			WHERE disabled = 0
				AND id_grupo IN ($user_groups_ids)
				AND (total_count = 0 OR total_count = notinit_count)
			GROUP BY id_grupo";
        $data_stats_not_init = db_get_all_rows_sql($sql_stats_not_init);

        if ($mode == 'tactical' || $mode == 'tree') {
            $sql_stats_ok = "SELECT id_grupo, COUNT(id_agente) AS agents_ok
				FROM $cache_table
				WHERE disabled = 0
					AND id_grupo IN ($user_groups_ids)
					AND critical_count = 0
					AND warning_count = 0
					AND unknown_count = 0
					AND normal_count > 0
				GROUP BY id_grupo";
            $data_stats_ok = db_get_all_rows_sql($sql_stats_ok);

            $sql_stats_warning = "SELECT id_grupo, COUNT(id_agente) AS agents_warning
				FROM $cache_table
				WHERE disabled = 0
					AND id_grupo IN ($user_groups_ids)
					AND critical_count = 0
					AND warning_count > 0
				GROUP BY id_grupo";
            $data_stats_warning = db_get_all_rows_sql($sql_stats_warning);

            $sql_stats_critical = "SELECT id_grupo, COUNT(id_agente) AS agents_critical
				FROM $cache_table
				WHERE disabled = 0
					AND id_grupo IN ($user_groups_ids)
					AND critical_count > 0
				GROUP BY id_grupo";
            $data_stats_critical = db_get_all_rows_sql($sql_stats_critical);
        }

        $stats_by_group = [];
        if (empty($data_stats) === false) {
            foreach ($data_stats as $value) {
                $group_id = (int) $value['id_grupo'];

                $stats = [];
                $stats['agents_total'] = (int) $value['agents_total'];
                $stats['monitors_total'] = (int) $value['monitors_total'];
                $stats['monitors_ok'] = (int) $value['monitors_ok'];
                $stats['monitors_warning'] = (int) $value['monitors_warning'];
                $stats['monitors_critical'] = (int) $value['monitors_critical'];
                $stats['monitors_unknown'] = (int) $value['monitors_unknown'];
                $stats['monitors_not_init'] = (int) $value['monitors_not_init'];
                $stats['alerts_fired'] = (int) $value['alerts_fired'];
                $stats_by_group[$group_id] = $stats;
            }

            if (empty($stats_by_group) === false) {
                if (empty($data_stats_unknown) === false) {
                    foreach ($data_stats_unknown as $value) {
                        $group_id = (int) $value['id_grupo'];
                        if (isset($stats_by_group[$group_id]) === true) {
                            $stats_by_group[$group_id]['agents_unknown'] = (int) $value['agents_unknown'];
                        }
                    }
                }

                if (empty($data_stats_not_init) === false) {
                    foreach ($data_stats_not_init as $value) {
                        $group_id = (int) $value['id_grupo'];
                        if (isset($stats_by_group[$group_id]) === true) {
                            $stats_by_group[$group_id]['agents_not_init'] = (int) $value['agents_not_init'];
                        }
                    }
                }

                if (empty($data_stats_ok) === false) {
                    foreach ($data_stats_ok as $value) {
                        $group_id = (int) $value['id_grupo'];
                        if (isset($stats_by_group[$group_id]) === true) {
                            $stats_by_group[$group_id]['agents_ok'] = (int) $value['agents_ok'];
                        }
                    }
                }

                if (empty($data_stats_warning) === false) {
                    foreach ($data_stats_warning as $value) {
                        $group_id = (int) $value['id_grupo'];
                        if (isset($stats_by_group[$group_id]) === true) {
                            $stats_by_group[$group_id]['agents_warning'] = (int) $value['agents_warning'];
                        }
                    }
                }

                if (empty($data_stats_critical) === false) {
                    foreach ($data_stats_critical as $value) {
                        $group_id = (int) $value['id_grupo'];
                        if (isset($stats_by_group[$group_id]) === true) {
                            $stats_by_group[$group_id]['agents_critical'] = (int) $value['agents_critical'];
                        }
                    }
                }
            }
        }
    }

    foreach ($list_groups as $key => $item) {
        $id = $item['id_grupo'];

        if (is_metaconsole() === true) {
            // Agent cache.
            $group_stat = [];
            if (isset($stats_by_group[$id]) === true) {
                $group_stat = $stats_by_group[$id];
            }

            $list[$i]['_id_'] = $id;
            $list[$i]['_name_'] = $item['nombre'];
            $list[$i]['_iconImg_'] = html_print_image('images/'.groups_get_icon($item['id_grupo']).'.png', true, ['style' => 'vertical-align: middle;']);

            if ($mode === 'tree' && empty($item['parent']) === false) {
                $list[$i]['_parent_id_'] = $item['parent'];
            }

            $list[$i]['_agents_unknown_'] = isset($group_stat['agents_unknown']) ? $group_stat['agents_unknown'] : 0;
            $list[$i]['_monitors_alerts_fired_'] = isset($group_stat['alerts_fired']) ? $group_stat['alerts_fired'] : 0;
            $list[$i]['_total_agents_'] = isset($group_stat['agents_total']) ? $group_stat['agents_total'] : 0;

            // This fields are not in database.
            $list[$i]['_monitors_ok_'] = isset($group_stat['monitors_ok']) ? $group_stat['monitors_ok'] : 0;
            $list[$i]['_monitors_critical_'] = isset($group_stat['monitors_critical']) ? $group_stat['monitors_critical'] : 0;
            $list[$i]['_monitors_warning_'] = isset($group_stat['monitors_warning']) ? $group_stat['monitors_warning'] : 0;
            $list[$i]['_monitors_unknown_'] = isset($group_stat['monitors_unknown']) ? $group_stat['monitors_unknown'] : 0;
            $list[$i]['_monitors_not_init_'] = isset($group_stat['monitors_not_init']) ? $group_stat['monitors_not_init'] : 0;
            $list[$i]['_agents_not_init_'] = isset($group_stat['agents_not_init']) ? $group_stat['agents_not_init'] : 0;

            if ($mode === 'tactical' || $mode === 'tree') {
                $list[$i]['_agents_ok_'] = isset($group_stat['agents_ok']) ? $group_stat['agents_ok'] : 0;
                $list[$i]['_agents_warning_'] = isset($group_stat['agents_warning']) ? $group_stat['agents_warning'] : 0;
                $list[$i]['_agents_critical_'] = isset($group_stat['agents_critical']) ? $group_stat['agents_critical'] : 0;
                $list[$i]['_monitors_alerts_'] = isset($group_stat['alerts']) ? $group_stat['alerts'] : 0;
                ;

                $list[$i]['_monitor_alerts_fire_count_'] = $group_stat[0]['alerts_fired'];
                $list[$i]['_total_checks_'] = $group_stat[0]['modules'];
                $list[$i]['_total_alerts_'] = $group_stat[0]['alerts'];
            }

            if ($mode === 'tactical') {
                // Get total count of monitors for this group, except disabled.
                $list[$i]['_monitor_checks_'] = ($list[$i]['_monitors_not_init_'] + $list[$i]['_monitors_unknown_'] + $list[$i]['_monitors_warning_'] + $list[$i]['_monitors_critical_'] + $list[$i]['_monitors_ok_']);

                // Calculate not_normal monitors.
                $list[$i]['_monitor_not_normal_'] = ($list[$i]['_monitor_checks_'] - $list[$i]['_monitors_ok_']);

                if ($list[$i]['_monitor_not_normal_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_monitor_health_'] = format_numeric((100 - ($list[$i]['_monitor_not_normal_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_monitor_health_'] = 100;
                }

                if ($list[$i]['_monitors_not_init_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_module_sanity_'] = format_numeric((100 - ($list[$i]['_monitors_not_init_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_module_sanity_'] = 100;
                }

                if (isset($list[$i]['_alerts_']) === true) {
                    if ($list[$i]['_monitors_alerts_fired_'] > 0 && $list[$i]['_alerts_'] > 0) {
                        $list[$i]['_alert_level_'] = format_numeric((100 - ($list[$i]['_monitors_alerts_fired_'] / ($list[$i]['_alerts_'] / 100))), 1);
                    } else {
                        $list[$i]['_alert_level_'] = 100;
                    }
                } else {
                    $list[$i]['_alert_level_'] = 100;
                    $list[$i]['_alerts_'] = 0;
                }

                $list[$i]['_monitor_bad_'] = ($list[$i]['_monitors_critical_'] + $list[$i]['_monitors_warning_']);

                if ($list[$i]['_monitor_bad_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_global_health_'] = format_numeric((100 - ($list[$i]['_monitor_bad_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_global_health_'] = 100;
                }

                $list[$i]['_server_sanity_'] = format_numeric((100 - $list[$i]['_module_sanity_']), 1);
            }

            if ($returnAllGroup === true) {
                $list[0]['_agents_unknown_'] += $list[$i]['_agents_unknown_'];
                $list[0]['_monitors_alerts_fired_'] += $list[$i]['_monitors_alerts_fired_'];
                $list[0]['_total_agents_'] += $list[$i]['_total_agents_'];
                $list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
                $list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
                $list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
                $list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
                $list[0]['_monitors_not_init_'] += $list[$i]['_monitors_not_init_'];
                $list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];

                if ($mode === 'tactical' || $mode === 'tree') {
                    $list[0]['_agents_ok_'] += $list[$i]['_agents_ok_'];
                    $list[0]['_agents_warning_'] += $list[$i]['_agents_warning_'];
                    $list[0]['_agents_critical_'] += $list[$i]['_agents_critical_'];
                    $list[0]['_monitors_alerts_'] += $list[$i]['_monitors_alerts_'];
                }
            }

            if ($mode === 'group') {
                if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0)
                    && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0)
                    && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)
                ) {
                    unset($list[$i]);
                }
            }
        } else if (((int) $config['realtimestats'] === 0)) {
            $group_stat = db_get_all_rows_sql(
                "SELECT *
				FROM tgroup_stat, tgrupo
				WHERE tgrupo.id_grupo = tgroup_stat.id_group
					AND tgroup_stat.id_group = $id
				ORDER BY nombre"
            );

            $list[$i]['_id_'] = $id;
            $list[$i]['_name_'] = $item['nombre'];
            $list[$i]['_iconImg_'] = html_print_image('images/'.groups_get_icon($item['id_grupo']).'.png', true, ['style' => 'vertical-align: middle;']);

            if ($mode === 'tree' && empty($item['parent']) === false) {
                $list[$i]['_parent_id_'] = $item['parent'];
            }

            $list[$i]['_agents_unknown_'] = $group_stat[0]['unknown'];
            $list[$i]['_monitors_alerts_fired_'] = $group_stat[0]['alerts_fired'];
            $list[$i]['_total_agents_'] = $group_stat[0]['agents'];

            // This fields are not in database.
            $list[$i]['_monitors_ok_'] = (int) groups_get_normal_monitors($id);
            $list[$i]['_monitors_critical_'] = (int) groups_get_critical_monitors($id);
            $list[$i]['_monitors_warning_'] = (int) groups_get_warning_monitors($id);
            $list[$i]['_monitors_unknown_'] = (int) groups_get_unknown_monitors($id);
            $list[$i]['_monitors_not_init_'] = (int) groups_get_not_init_monitors($id);
            $list[$i]['_agents_not_init_'] = (int) groups_get_not_init_agents($id);

            if ($mode == 'tactical' || $mode == 'tree') {
                $list[$i]['_agents_ok_'] = $group_stat[0]['normal'];
                $list[$i]['_agents_warning_'] = $group_stat[0]['warning'];
                $list[$i]['_agents_critical_'] = $group_stat[0]['critical'];
                $list[$i]['_monitors_alerts_'] = $group_stat[0]['alerts'];

                $list[$i]['_monitor_alerts_fire_count_'] = $group_stat[0]['alerts_fired'];
                $list[$i]['_total_checks_'] = $group_stat[0]['modules'];
                $list[$i]['_total_alerts_'] = $group_stat[0]['alerts'];
            }

            if ($mode == 'tactical') {
                // Get total count of monitors for this group, except disabled.
                $list[$i]['_monitor_checks_'] = ($list[$i]['_monitors_not_init_'] + $list[$i]['_monitors_unknown_'] + $list[$i]['_monitors_warning_'] + $list[$i]['_monitors_critical_'] + $list[$i]['_monitors_ok_']);

                // Calculate not_normal monitors
                $list[$i]['_monitor_not_normal_'] = ($list[$i]['_monitor_checks_'] - $list[$i]['_monitors_ok_']);

                if ($list[$i]['_monitor_not_normal_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_monitor_health_'] = format_numeric((100 - ($list[$i]['_monitor_not_normal_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_monitor_health_'] = 100;
                }

                if ($list[$i]['_monitors_not_init_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_module_sanity_'] = format_numeric((100 - ($list[$i]['_monitors_not_init_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_module_sanity_'] = 100;
                }

                if (isset($list[$i]['_alerts_'])) {
                    if ($list[$i]['_monitors_alerts_fired_'] > 0 && $list[$i]['_alerts_'] > 0) {
                        $list[$i]['_alert_level_'] = format_numeric((100 - ($list[$i]['_monitors_alerts_fired_'] / ($list[$i]['_alerts_'] / 100))), 1);
                    } else {
                        $list[$i]['_alert_level_'] = 100;
                    }
                } else {
                    $list[$i]['_alert_level_'] = 100;
                    $list[$i]['_alerts_'] = 0;
                }

                $list[$i]['_monitor_bad_'] = ($list[$i]['_monitors_critical_'] + $list[$i]['_monitors_warning_']);

                if ($list[$i]['_monitor_bad_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_global_health_'] = format_numeric((100 - ($list[$i]['_monitor_bad_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_global_health_'] = 100;
                }

                $list[$i]['_server_sanity_'] = format_numeric((100 - $list[$i]['_module_sanity_']), 1);
            }

            if ($returnAllGroup) {
                $list[0]['_agents_unknown_'] += $group_stat[0]['unknown'];
                $list[0]['_monitors_alerts_fired_'] += $group_stat[0]['alerts_fired'];
                $list[0]['_total_agents_'] += $group_stat[0]['agents'];
                $list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
                $list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
                $list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
                $list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
                $list[0]['_monitors_not_init_'] += $list[$i]['_monitors_not_init_'];
                $list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];

                if ($mode == 'tactical' || $mode == 'tree') {
                    $list[0]['_agents_ok_'] += $group_stat[0]['normal'];
                    $list[0]['_agents_warning_'] += $group_stat[0]['warning'];
                    $list[0]['_agents_critical_'] += $group_stat[0]['critical'];
                    $list[0]['_monitors_alerts_'] += $group_stat[0]['alerts'];
                }
            }

            if ($mode == 'group') {
                if (! defined('METACONSOLE')) {
                    if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
                        unset($list[$i]);
                    }
                } else {
                    if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
                        unset($list[$i]);
                    }
                }
            }
        } else {
            $list[$i]['_id_'] = $id;
            $list[$i]['_name_'] = $item['nombre'];
            $list[$i]['_iconImg_'] = html_print_image('images/'.groups_get_icon($item['id_grupo']).'.png', true, ['style' => 'vertical-align: middle;']);

            if ($mode == 'tree' && !empty($item['parent'])) {
                $list[$i]['_parent_id_'] = $item['parent'];
            }

            $list[$i]['_monitors_ok_'] = (int) groups_get_normal_monitors($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_monitors_critical_'] = (int) groups_get_critical_monitors($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_monitors_warning_'] = (int) groups_get_warning_monitors($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_monitors_unknown_'] = (int) groups_get_unknown_monitors($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_monitors_not_init_'] = (int) groups_get_not_init_monitors($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_monitors_alerts_fired_'] = groups_monitor_fired_alerts($id);
            $list[$i]['_total_agents_'] = (int) groups_get_total_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_agents_unknown_'] = (int) groups_get_unknown_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
            $list[$i]['_agents_not_init_'] = (int) groups_get_not_init_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);

            if ($mode == 'tactical' || $mode == 'tree') {
                $list[$i]['_agents_ok_'] = (int) groups_get_normal_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
                $list[$i]['_agents_warning_'] = (int) groups_get_warning_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
                $list[$i]['_agents_critical_'] = (int) groups_get_critical_agents($id, $agent_filter, $module_filter, $user_strict, $acltags, $config['realtimestats']);
                $list[$i]['_monitors_alerts_'] = groups_monitor_alerts($id);

                // TODO
                // ~ $list[$i]["_total_checks_"]
                // ~ $list[$i]["_total_alerts_"]
                // Get total count of monitors for this group, except disabled.
                $list[$i]['_monitor_checks_'] = ($list[$i]['_monitors_not_init_'] + $list[$i]['_monitors_unknown_'] + $list[$i]['_monitors_warning_'] + $list[$i]['_monitors_critical_'] + $list[$i]['_monitors_ok_']);

                // Calculate not_normal monitors
                $list[$i]['_monitor_not_normal_'] = ($list[$i]['_monitor_checks_'] - $list[$i]['_monitors_ok_']);

                if ($list[$i]['_monitor_not_normal_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_monitor_health_'] = format_numeric((100 - ($list[$i]['_monitor_not_normal_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_monitor_health_'] = 100;
                }

                if ($list[$i]['_monitors_not_init_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_module_sanity_'] = format_numeric((100 - ($list[$i]['_monitors_not_init_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_module_sanity_'] = 100;
                }

                if (isset($list[$i]['_alerts_'])) {
                    if ($list[$i]['_monitors_alerts_fired_'] > 0 && $list[$i]['_alerts_'] > 0) {
                        $list[$i]['_alert_level_'] = format_numeric((100 - ($list[$i]['_monitors_alerts_fired_'] / ($list[$i]['_alerts_'] / 100))), 1);
                    } else {
                        $list[$i]['_alert_level_'] = 100;
                    }
                } else {
                    $list[$i]['_alert_level_'] = 100;
                    $list[$i]['_alerts_'] = 0;
                }

                $list[$i]['_monitor_bad_'] = ($list[$i]['_monitors_critical_'] + $list[$i]['_monitors_warning_']);

                if ($list[$i]['_monitor_bad_'] > 0 && $list[$i]['_monitor_checks_'] > 0) {
                    $list[$i]['_global_health_'] = format_numeric((100 - ($list[$i]['_monitor_bad_'] / ($list[$i]['_monitor_checks_'] / 100))), 1);
                } else {
                    $list[$i]['_global_health_'] = 100;
                }

                $list[$i]['_server_sanity_'] = format_numeric((100 - $list[$i]['_module_sanity_']), 1);
            }

            if ($returnAllGroup) {
                $list[0]['_agents_unknown_'] += $list[$i]['_agents_unknown_'];
                $list[0]['_monitors_alerts_fired_'] += $list[$i]['_monitors_alerts_fired_'];
                $list[0]['_total_agents_'] += $list[$i]['_total_agents_'];
                $list[0]['_monitors_ok_'] += $list[$i]['_monitors_ok_'];
                $list[0]['_monitors_critical_'] += $list[$i]['_monitors_critical_'];
                $list[0]['_monitors_warning_'] += $list[$i]['_monitors_warning_'];
                $list[0]['_monitors_unknown_'] += $list[$i]['_monitors_unknown_'];
                $list[0]['_monitors_not_init_'] = $list[$i]['_monitors_not_init_'];
                $list[0]['_agents_not_init_'] += $list[$i]['_agents_not_init_'];

                if ($mode == 'tactical' || $mode == 'tree') {
                    $list[0]['_agents_ok_'] += $list[$i]['_agents_ok_'];
                    $list[0]['_agents_warning_'] += $list[$i]['_agents_warning_'];
                    $list[0]['_agents_critical_'] += $list[$i]['_agents_critical_'];
                    $list[0]['_monitors_alerts_'] += $list[$i]['_monitors_alerts_'];
                }
            }

            if ($mode == 'group') {
                if (! defined('METACONSOLE')) {
                    if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0) && ($list[$i]['_monitors_unknown_'] == 0) && ($list[$i]['_monitors_not_init_'] == 0) && ($list[$i]['_agents_not_init_'] == 0)) {
                        unset($list[$i]);
                    }
                } else {
                    if (($list[$i]['_agents_unknown_'] == 0) && ($list[$i]['_monitors_alerts_fired_'] == 0) && ($list[$i]['_total_agents_'] == 0) && ($list[$i]['_monitors_ok_'] == 0) && ($list[$i]['_monitors_critical_'] == 0) && ($list[$i]['_monitors_warning_'] == 0)) {
                        unset($list[$i]);
                    }
                }
            }
        }

        $i++;
    }

    return $list;
}


function groups_get_group_deep($id_group)
{
    global $config;

    $groups = users_get_groups(false, 'AR', true, true);

    $parents = groups_get_parents($id_group, false, $groups);

    if (empty($parents)) {
        $deep = '';
    } else {
        $deep = str_repeat('&nbsp;&nbsp;', count($parents));
    }

    return $deep;
}


/**
 * Heat map from agents by group
 *
 * @param array   $id_group
 * @param integer $width
 * @param integer $height
 *
 * @return string Html Graph.
 */
function groups_get_heat_map_agents(array $id_group, float $width=0, float $height=0)
{
    ui_require_css_file('heatmap');

    if (is_array($id_group) === false) {
        $id_group = [$id_group];
    }

    $sql = 'SELECT * FROM tagente WHERE id_grupo IN('.implode(',', $id_group).')';

    $all_agents = db_get_all_rows_sql($sql);
    if (empty($all_agents)) {
        return null;
    }

    $total_agents = count($all_agents);

    // Best square.
    $high = (float) max($width, $height);
    $low = 0.0;

    while (abs($high - $low) > 0.000001) {
        $mid = (($high + $low) / 2.0);
        $midval = (floor($width / $mid) * floor($height / $mid));
        if ($midval >= $total_agents) {
            $low = $mid;
        } else {
            $high = $mid;
        }
    }

    $square_length = min(($width / floor($width / $low)), ($height / floor($height / $low)));

    // Print starmap.
    $html = sprintf(
        '<svg id="svg_%s" style="width: %spx; height: %spx;">',
        $id_group,
        $width,
        $height
    );

    $html .= '<g>';
    $row = 0;
    $column = 0;
    $x = 0;
    $y = 0;
    $cont = 1;

    foreach ($all_agents as $key => $value) {
        // Colour by status.
        $status = agents_get_status_from_counts($value);

        switch ($status) {
            case 5:
                // Not init status.
                $status = 'notinit';
            break;

            case 1:
                // Critical status.
                $status = 'critical';
            break;

            case 2:
                // Warning status.
                $status = 'warning';
            break;

            case 0:
                // Normal status.
                $status = 'normal';
            break;

            case 3:
            case -1:
            default:
                // Unknown status.
                $status = 'unknown';
            break;
        }

        $html .= sprintf(
            '<rect id="%s" x="%s" y="%s" row="%s" col="%s" width="%s" height="%s" class="%s_%s" onclick="showInfoAgent('.$value['id_agente'].')"></rect>',
            'rect_'.$cont,
            $x,
            $y,
            $row,
            $column,
            $square_length,
            $square_length,
            $status,
            random_int(1, 10)
        );

        $y += $square_length;
        $row++;
        if ((int) ($y + $square_length) > (int) $height) {
            $y = 0;
            $x += $square_length;
            $row = 0;
            $column++;
        }

        if ((int) ($x + $square_length) > (int) $width) {
            $x = 0;
            $y += $square_length;
            $column = 0;
            $row++;
        }

        $cont++;
    }
    ?>
    <script type="text/javascript">
        $(document).ready(function() {
            const total_agents = '<?php echo $total_agents; ?>';

            function getRandomInteger(min, max) {
                return Math.floor(Math.random() * max) + min;
            }

            function oneSquare(solid, time) {
                var randomPoint = getRandomInteger(1, total_agents);
                let target = $(`#rect_${randomPoint}`);
                let class_name = target.attr('class');
                class_name = class_name.split('_')[0];
                setTimeout(function() {
                    target.removeClass();
                    target.addClass(`${class_name}_${solid}`);
                    oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                }, time);
            }

            let cont = 0;
            while (cont < Math.ceil(total_agents / 3)) {
                oneSquare(getRandomInteger(1, 10), getRandomInteger(100, 900));
                cont ++;
            }
        });
    </script>
    <?php
    $html .= '</g>';
    $html .= '</svg>';

    return $html;
}


/**
 * Return html count from agents and monitoring by group.
 *
 * @param [type] $id_groups
 *
 * @return string Html
 */
function tactical_groups_get_agents_and_monitoring($id_groups)
{
    global $config;

    $data = [
        'total_agents'  => groups_agents_total_counters($id_groups, false)['total'],
        'monitor_total' => groups_get_total_monitors($id_groups, [], [], false, false, false, false),
    ];

    // Link URLS
    $urls = [];
    $urls['total_agents'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&group_id='.$id_groups[0].'&recursion=1';
    $urls['monitor_total'] = $config['homeurl'].'index.php?sec=view&amp;sec2=operation/agentes/status_monitor&amp;refr=60&amp;status=-1&ag_group='.$id_groups[0].'&recursion=1';

    $table_am = html_get_predefined_table();
    $tdata = [];
    $tdata[0] = html_print_image('images/agent.png', true, ['title' => __('Total agents'), 'class' => 'invert_filter'], false, false, false, true);
    $tdata[1] = $data['total_agents'] <= 0 ? '-' : $data['total_agents'];
    $tdata[1] = '<a class="big_data" href="'.$urls['total_agents'].'">'.$tdata[1].'</a>';

    if ($data['total_agents'] > 500 && !enterprise_installed()) {
        $tdata[2] = "<div id='agentsmodal' class='publienterprise' title='".__('Enterprise version not installed')."'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
    }

    $tdata[3] = html_print_image('images/module.png', true, ['title' => __('Monitor checks'), 'class' => 'invert_filter'], false, false, false, true);
    $tdata[4] = $data['monitor_total'] <= 0 ? '-' : $data['monitor_total'];
    $tdata[4] = '<a class="big_data" href="'.$urls['monitor_total'].'">'.$tdata[4].'</a>';

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */
    if ($data['total_agents']) {
        if (($data['monitor_total'] / $data['total_agents'] > 100) && !enterprise_installed()) {
            $tdata[5] = "<div id='monitorcheckmodal' class='publienterprise' title='Community version' ><img data-title='".__('Enterprise version not installed')."' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
        }
    }

    $table_am->rowclass[] = '';
    $table_am->data[] = $tdata;

    $output = '<fieldset class="databox tactical_set">
                <legend>'.__('Total agents and monitors').'</legend>'.html_print_table($table_am, true).'</fieldset>';

    return $output;
}


/**
 * Return html count from stats alerts by group.
 *
 * @param  [type] $id_groups
 * @return string Html.
 */
function tactical_groups_get_stats_alerts($id_groups)
{
    global $config;

    $alerts = groups_monitor_alerts_total_counters($id_groups, false);
    $data = [
        'monitor_alerts'       => $alerts['total'],
        'monitor_alerts_fired' => $alerts['fired'],

    ];

    $urls = [];
    $urls['monitor_alerts'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&ag_group='.$id_groups[0];
    $urls['monitor_alerts_fired'] = $config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/alerts_status&amp;refr=60&disabled=fired&ag_group='.$id_groups[0];

    // Alerts table.
    $table_al = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/bell.png', true, ['title' => __('Defined alerts'), 'class' => 'invert_filter'], false, false, false, true);
    $tdata[1] = $data['monitor_alerts'] <= 0 ? '-' : $data['monitor_alerts'];
    $tdata[1] = '<a class="big_data" href="'.$urls['monitor_alerts'].'">'.$tdata[1].'</a>';

    /*
        Hello there! :)
        We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
        You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
    */

    if ($data['monitor_alerts'] > $data['total_agents'] && !enterprise_installed()) {
        $tdata[2] = "<div id='alertagentmodal' class='publienterprise' title='Community version' ><img data-title='".__('Enterprise version not installed')."' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>";
    }

    $tdata[3] = html_print_image(
        'images/bell_error.png',
        true,
        [
            'title' => __('Fired alerts'),
            'class' => 'invert_filter',
        ],
        false,
        false,
        false,
        true
    );
    $tdata[4] = $data['monitor_alerts_fired'] <= 0 ? '-' : $data['monitor_alerts_fired'];
    $tdata[4] = '<a style="color: '.COL_ALERTFIRED.';" class="big_data" href="'.$urls['monitor_alerts_fired'].'">'.$tdata[4].'</a>';
    $table_al->rowclass[] = '';
    $table_al->data[] = $tdata;

    if (!is_metaconsole()) {
        $output = '<fieldset class="databox tactical_set">
                    <legend>'.__('Defined and fired alerts').'</legend>'.html_print_table($table_al, true).'</fieldset>';
    } else {
        // Remove the defined alerts cause with the new cache table is difficult to retrieve them.
        unset($table_al->data[0][0], $table_al->data[0][1]);

        $table_al->class = 'tactical_view';
        $table_al->style = [];
        $output = '<fieldset class="tactical_set">
                    <legend>'.__('Fired alerts').'</legend>'.html_print_table($table_al, true).'</fieldset>';
    }

    return $output;
}


/**
 * Return html count from stats modules by group.
 *
 * @param  [type]  $id_groups
 * @param  integer $graph_width
 * @param  integer $graph_height
 * @param  boolean $links
 * @param  boolean $data_agents
 * @return void
 */
function groups_get_stats_modules_status($id_groups, $graph_width=250, $graph_height=150, $links=false, $data_agents=false)
{
    global $config;

    $data = [
        'monitor_critical' => groups_get_critical_monitors($id_groups, [], [], false, false, false, false),
        'monitor_warning'  => groups_get_warning_monitors($id_groups, [], [], false, false, false, false),
        'monitor_ok'       => groups_get_normal_monitors($id_groups, [], [], false, false, false, false),
        'monitor_unknown'  => groups_get_unknown_monitors($id_groups, [], [], false, false, false, false),
        'monitor_not_init' => groups_get_not_init_monitors($id_groups, [], [], false, false, false, false),
    ];

    // Link URLS.
    if ($links === false) {
        $urls = [];
        $urls['monitor_critical'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_CRITICAL_BAD.'&pure='.$config['pure'].'&recursion=1&ag_group='.$id_groups[0];
        $urls['monitor_warning'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_WARNING.'&pure='.$config['pure'].'&recursion=1&ag_group='.$id_groups[0];
        $urls['monitor_ok'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_NORMAL.'&pure='.$config['pure'].'&recursion=1&ag_group='.$id_groups[0];
        $urls['monitor_unknown'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_UNKNOWN.'&pure='.$config['pure'].'&recursion=1&ag_group='.$id_groups[0];
        $urls['monitor_not_init'] = $config['homeurl'].'index.php?'.'sec=view&amp;sec2=operation/agentes/status_monitor&amp;'.'refr=60&amp;status='.AGENT_MODULE_STATUS_NOT_INIT.'&pure='.$config['pure'].'&recursion=1&ag_group='.$id_groups[0];
    } else {
        $urls = [];
        $urls['monitor_critical'] = $links['monitor_critical'];
        $urls['monitor_warning'] = $links['monitor_warning'];
        $urls['monitor_ok'] = $links['monitor_ok'];
        $urls['monitor_unknown'] = $links['monitor_unknown'];
        $urls['monitor_not_init'] = $links['monitor_not_init'];
    }

    // Fixed width non interactive charts
    $status_chart_width = $graph_width;

    // Modules by status table
    $table_mbs = html_get_predefined_table();

    $tdata = [];
    $tdata[0] = html_print_image('images/module_critical.png', true, ['title' => __('Monitor critical')], false, false, false, true);
    $tdata[1] = $data['monitor_critical'] <= 0 ? '-' : $data['monitor_critical'];
    $tdata[1] = '<a style="color: '.COL_CRITICAL.';" class="big_data line_heigth_initial" href="'.$urls['monitor_critical'].'">'.$tdata[1].'</a>';

    $tdata[2] = html_print_image('images/module_warning.png', true, ['title' => __('Monitor warning')], false, false, false, true);
    $tdata[3] = $data['monitor_warning'] <= 0 ? '-' : $data['monitor_warning'];
    $tdata[3] = '<a style="color: '.COL_WARNING_DARK.';" class="big_data line_heigth_initial" href="'.$urls['monitor_warning'].'">'.$tdata[3].'</a>';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image('images/module_ok.png', true, ['title' => __('Monitor normal')], false, false, false, true);
    $tdata[1] = $data['monitor_ok'] <= 0 ? '-' : $data['monitor_ok'];
    $tdata[1] = '<a style="color: '.COL_NORMAL.';" class="big_data" href="'.$urls['monitor_ok'].'">'.$tdata[1].'</a>';

    $tdata[2] = html_print_image('images/module_unknown.png', true, ['title' => __('Monitor unknown')], false, false, false, true);
    $tdata[3] = $data['monitor_unknown'] <= 0 ? '-' : $data['monitor_unknown'];
    $tdata[3] = '<a style="color: '.COL_UNKNOWN.';" class="big_data line_heigth_initial" href="'.$urls['monitor_unknown'].'">'.$tdata[3].'</a>';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    $tdata = [];
    $tdata[0] = html_print_image('images/module_notinit.png', true, ['title' => __('Monitor not init')], false, false, false, true);
    $tdata[1] = $data['monitor_not_init'] <= 0 ? '-' : $data['monitor_not_init'];
    $tdata[1] = '<a style="color: '.COL_NOTINIT.';" class="big_data line_heigth_initial" href="'.$urls['monitor_not_init'].'">'.$tdata[1].'</a>';

    $tdata[2] = $tdata[3] = '';
    $table_mbs->rowclass[] = '';
    $table_mbs->data[] = $tdata;

    if ($data['monitor_checks'] > 0) {
        $tdata = [];
        $table_mbs->colspan[count($table_mbs->data)][0] = 4;
        $table_mbs->cellstyle[count($table_mbs->data)][0] = 'text-align: center;';
        $tdata[0] = '<div id="outter_status_pie" style="height: '.$graph_height.'px">'.'<div id="status_pie" style="margin: auto; width: '.$status_chart_width.'px;">'.graph_agent_status(false, $graph_width, $graph_height, true, true, $data_agents).'</div></div>';
        $table_mbs->rowclass[] = '';
        $table_mbs->data[] = $tdata;
    }

    if (!is_metaconsole()) {
        $output = '
            <fieldset class="databox tactical_set">
                <legend>'.__('Monitors by status').'</legend>'.html_print_table($table_mbs, true).'</fieldset>';
    } else {
        $table_mbs->class = 'tactical_view';
        $table_mbs->style = [];
        $output = '
            <fieldset class="tactical_set">
                <legend>'.__('Monitors by status').'</legend>'.html_print_table($table_mbs, true).'</fieldset>';
    }

    return $output;
}