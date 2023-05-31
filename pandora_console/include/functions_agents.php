<?php
/**
 * Agents Functions.
 *
 * @category   Agents functions.
 * @package    Pandora FMS
 * @subpackage User interface.
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
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';

use PandoraFMS\Enterprise\RCMDFile as RCMDFile;
use PandoraFMS\Event;


/**
 * Return the agent if exists in the DB.
 *
 * @param integer $id_agent      The agent id.
 * @param boolean $show_disabled Show the agent found althought it is disabled. By default false.
 * @param boolean $force_meta
 *
 * @return boolean The result to check if the agent is in the DB.
 */
function agents_get_agent($id_agent, $show_disabled=true, $force_meta=false)
{
    $agent = db_get_row_filter(
        $force_meta ? 'tmetaconsole_agent' : 'tagente',
        [
            'id_agente' => $id_agent,
            'disabled'  => !$show_disabled,
        ]
    );

    return $agent;
}


/**
 * Check the agent exists in the DB.
 *
 * @param integer $id_agent      The agent id.
 * @param boolean $show_disabled Show the agent found althought it is disabled. By default false.
 * @param boolean $force_meta
 *
 * @return boolean The result to check if the agent is in the DB.
 */
function agents_check_agent_exists($id_agent, $show_disabled=true, $force_meta=false)
{
    $agent = db_get_value_filter(
        'id_agente',
        $force_meta ? 'tmetaconsole_agent' : 'tagente',
        [
            'id_agente' => $id_agent,
            'disabled'  => !$show_disabled,
        ]
    );

    if (!empty($agent)) {
        return true;
    } else {
        return false;
    }
}


/**
 * Get agent id from a module id that it has.
 *
 * @param integer $id_module Id module is list modules this agent.
 *
 * @return integer Id from the agent of the given id module.
 */
function agents_get_agent_id_by_module_id($id_agente_modulo)
{
    return (int) db_get_value(
        'id_agente',
        'tagente_modulo',
        'id_agente_modulo',
        $id_agente_modulo
    );
}


/**
 * Search for agent data anywhere.
 *
 * Note: This method matches with server (perl) locate_agent.
 * Do not change order!
 *
 * @param string $field Alias, name or IP address of searchable agent.
 *
 * @return array Agent of false if not found.
 */
function agents_locate_agent(string $field)
{
    global $config;

    $table = 'tagente';
    if (is_metaconsole()) {
        $table = 'tmetaconsole_agent';
    }

    // Alias.
    $sql = sprintf(
        'SELECT *
         FROM %s
         WHERE alias = "%s"',
        $table,
        $field
    );
    $agent = db_get_row_sql($sql);

    if ($agent !== false) {
        return $agent;
    }

    // Addr.
    $agent = agents_get_agent_with_ip($field);
    if ($agent !== false) {
        return $agent;
    }

    // Name.
    $sql = sprintf(
        'SELECT *
         FROM %s
         WHERE nombre = "%s"',
        $table,
        $field
    );
    return db_get_row_sql($sql);
}


/**
 * Get agent id from an agent alias.
 *
 * @param string $alias Agent alias.
 *
 * @return array|boolean Agents ids or false if error.
 */
function agents_get_agent_id_by_alias($alias, $is_metaconsole=false)
{
    if ($is_metaconsole === true) {
        return db_get_all_rows_sql("SELECT id_tagente FROM tmetaconsole_agent WHERE upper(alias) LIKE upper('%$alias%')");
    } else {
        return db_get_all_rows_sql("SELECT id_agente FROM tagente WHERE upper(alias) LIKE upper('%$alias%')");
    }
}


/**
 * Return seconds left to contact again with agent.
 *
 * @param integer $id_agente Target agent
 *
 * @return integer|null Seconds left.
 */
function agents_get_next_contact_time_left(int $id_agente)
{
    $last_contact = false;

    if ($id_agente > 0) {
        $last_contact = db_get_value_sql(
            sprintf(
                'SELECT CAST(intervalo AS SIGNED) - (UNIX_TIMESTAMP() - UNIX_TIMESTAMP(IF(ultimo_contacto >= ultimo_contacto_remoto, ultimo_contacto, ultimo_contacto_remoto))) as "val"
                    FROM `tagente`
                    WHERE id_agente = %d ',
                $id_agente
            )
        );
    }

    return $last_contact;
}


/**
 * Creates an agent.
 *
 * @param string  $name          Agent name.
 * @param string  $id_group      Group to be included.
 * @param integer $interval      Agent interval.
 * @param string  $ip_address    Agent IP.
 * @param mixed   $values        Other tagente fields.
 * @param boolean $alias_as_name True to not assign an alias as name.
 *
 * @return integer New agent id if created. False if it could not be created.
 */
function agents_create_agent(
    $name,
    $id_group,
    $interval,
    $ip_address,
    $values=false,
    $alias_as_name=false
) {
    global $config;

    if (empty($name) === true) {
        return false;
    }

    if (empty($id_group) === true && (int) $id_group !== 0) {
        return false;
    }

    // Check interval greater than zero.
    if ($interval < 0) {
        $interval = false;
    }

    if (empty($interval) === true) {
        return false;
    }

    if (is_array($values) === false) {
        $values = [];
    }

    $values['alias'] = $name;
    $values['nombre'] = ($alias_as_name === false) ? hash('sha256', $name.'|'.$ip_address.'|'.time().'|'.sprintf('%04d', rand(0, 10000))) : $name;
    $values['id_grupo'] = $id_group;
    $values['intervalo'] = $interval;

    if (empty($ip_address) === false) {
            $values['direccion'] = $ip_address;
    }

    // Check if group has limit or overrides the agent limit.
    if (group_allow_more_agents($id_group, true, 'create') === false) {
        return false;
    }

    if (has_metaconsole() === true
        && (bool) $config['metaconsole_agent_cache'] === true
    ) {
        // Force an update of the agent cache.
        $values['update_module_count'] = 1;
    }

    $id_agent = db_process_sql_insert('tagente', $values);
    if ($id_agent === false) {
        return false;
    }

    // Create address for this agent in taddress.
    if (empty($ip_address) === false) {
        agents_add_address($id_agent, $ip_address);
    }

    db_pandora_audit(
        AUDIT_LOG_AGENT_MANAGEMENT,
        'New agent '.$name.' created'
    );

    return $id_agent;
}


/**
 * Get all the simple alerts of an agent.
 *
 * @param int Agent id
 * @param string Filter on "fired", "notfired" or "disabled". Any other value
 * will not do any filter.
 * @param array Extra filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 * @param boolean                                            $allModules
 *
 * @return array All simple alerts defined for an agent. Empty array if no
 * alerts found.
 */
function agents_get_alerts_simple($id_agent=false, $filter='', $options=false, $where='', $allModules=false, $orderby=false, $idGroup=false, $count=false, $strict_user=false, $tag=false)
{
    global $config;

    if (is_array($filter)) {
        $disabled = $filter['disabled'];
        if (isset($filter['standby'])) {
            $filter = ' AND talert_template_modules.standby = "'.$filter['standby'].'"';
        } else {
            $filter = '';
        }
    } else {
        $filter = '';
        $disabled = $filter;
    }

    switch ($disabled) {
        case 'notfired':
            $filter .= ' AND times_fired = 0 AND talert_template_modules.disabled = 0';
        break;

        case 'fired':
            $filter .= ' AND times_fired > 0 AND talert_template_modules.disabled = 0';
        break;

        case 'disabled':
            $filter .= ' AND talert_template_modules.disabled = 1';
        break;

        case 'all_enabled':
            $filter .= ' AND talert_template_modules.disabled = 0';
        break;

        default:
            $filter .= '';
        break;
    }

    if ($tag) {
        $filter .= ' AND (id_agent_module IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN ('.$tag.')))';
    }

    if (isset($options['offset'])) {
        $offset = $options['offset'];
        unset($options['offset']);
    }

    if (isset($options['limit'])) {
        $limit = $options['limit'];
        unset($options['limit']);
    }

    if (is_array($options)) {
        $filter .= db_format_array_where_clause_sql($options);
    }

    if (($id_agent !== false) && ($idGroup !== false)) {
        if ($idGroup != 0) {
            $has_secondary = enterprise_hook('agents_is_using_secondary_groups');
            // All group
            $subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo
                WHERE delete_pending = 0 AND id_agente IN (SELECT id_agente FROM tagente WHERE id_grupo = '.$idGroup;

            if ($has_secondary) {
                $subQuery .= ' OR tasg.id_group = '.$idGroup;
            }

            $subQuery .= ')';
        } else {
            $subQuery = 'SELECT id_agente_modulo
				FROM tagente_modulo WHERE delete_pending = 0';
        }

        // Filter by agents id.
        if (is_array($id_agent) === true && empty($id_agent) === false) {
            $id_agents_list = implode(',', $id_agent);
        } else {
            $id_agents_list = $id_agent;
        }

        if ($id_agents_list === '') {
            $id_agents_list = '0';
        }

        $subQuery .= ' AND id_agente in ('.$id_agents_list.')';
    } else if ($id_agent === false || empty($id_agent)) {
        if ($allModules) {
            $disabled = '';
        } else {
            $disabled = 'WHERE disabled = 0';
        }

        $subQuery = 'SELECT id_agente_modulo
			FROM tagente_modulo '.$disabled;
    } else {
        $id_agent = (array) $id_agent;
        $id_modules = array_keys(agents_get_modules($id_agent, false, ['delete_pending' => 0]));

        if (empty($id_modules)) {
            return [];
        }

        $subQuery = implode(',', $id_modules);
    }

    $orderbyText = '';
    if ($orderby !== false) {
        if (is_array($orderby)) {
            $orderbyText = sprintf('ORDER BY %s', $orderby['field'], $orderby['order']);
        } else {
            $orderbyText = sprintf('ORDER BY %s', $orderby);
        }
    }

    $selectText = 'talert_template_modules.*, t2.nombre AS agent_module_name, t3.alias AS agent_name, t4.name AS template_name';
    if ($count !== false) {
        $selectText = 'COUNT(talert_template_modules.id) AS count';
    }

    $secondary_join = '';
    if ($idGroup) {
        if (isset($has_secondary) && $has_secondary) {
            $secondary_join = sprintf(
                'LEFT JOIN tagent_secondary_group tasg
                ON t3.id_agente = tasg.id_agent
                AND tasg.id_group = %d',
                $idGroup
            );
        }
    }

    $sql = sprintf(
        'SELECT %s
		FROM talert_template_modules
        INNER JOIN tagente_modulo t2
            ON talert_template_modules.id_agent_module = t2.id_agente_modulo
        INNER JOIN tagente t3
            ON t2.id_agente = t3.id_agente %s
        %s
        INNER JOIN talert_templates t4
            ON talert_template_modules.id_alert_template = t4.id
		WHERE id_agent_module in (%s) %s %s %s',
        $selectText,
        ($id_agent !== false && is_array($id_agent)) ? 'AND t3.id_agente IN ('.implode(',', $id_agent).')' : '',
        $secondary_join,
        $subQuery,
        $where,
        $filter,
        $orderbyText
    );

    $limit_sql = '';
    if (isset($offset) && isset($limit)) {
        $limit_sql = " LIMIT $offset, $limit ";
    }

    $sql = sprintf('%s %s', $sql, $limit_sql);
    $alerts = db_get_all_rows_sql($sql);

    if ($alerts === false) {
        return [];
    }

    if ($count !== false) {
        return $alerts[0]['count'];
    } else {
        return $alerts;
    }
}


/**
 * Get a list of agents.
 *
 * By default, it will return all the agents where the user has reading access.
 *
 * @param array   $filter         Filter options in an indexed array.
 * See db_format_array_where_clause_sql().
 * @param array   $fields         DB fields to get.
 * @param string  $access         ACL level needed in the agents groups.
 * @param array   $order          The order of agents, by default is upward
 *            for field nombre.
 * @param boolean $return         Whether to return array with agents or
 * the sql string statement.
 * @param boolean $disabled_agent Whether to return only the enabled agents
 * or not.
 * @param boolean $use_meta_table Whether to use the regular or the meta table
 * to retrieve the agents.
 *
 * @return mixed An array with all alerts defined for an agent
 * or false in case no allowed groups are specified.
 */
function agents_get_agents(
    $filter=false,
    $fields=false,
    $access='AR',
    $order=[
        'field' => 'nombre',
        'order' => 'ASC',
    ],
    $return=false,
    $disabled_agent=0,
    $use_meta_table=false
) {
    global $config;

    if (! is_array($filter)) {
        $filter = [];
    }

    if (isset($filter['search'])) {
        $search = $filter['search'];
        unset($filter['search']);
    } else {
        $search = '';
    }

    if (isset($filter['search_custom'])) {
        $search_custom = $filter['search_custom'];
        unset($filter['search_custom']);
    } else {
        $search_custom = '';
    }

    if (isset($filter['id_os'])) {
        $id_os = $filter['id_os'];
        unset($filter['id_os']);
    } else {
        $id_os = '';
    }

    if (isset($filter['policies'])) {
        $policies = $filter['policies'];
        unset($filter['policies']);
    } else {
        $policies = '';
    }

    if (isset($filter['other_condition'])) {
        $other_condition = $filter['other_condition'];
        unset($filter['other_condition']);
    } else {
        $other_condition = '';
    }

    if (isset($filter['offset'])) {
        $offset = $filter['offset'];
        unset($filter['offset']);
    }

    if (isset($filter['limit'])) {
        $limit = $filter['limit'];
        unset($filter['limit']);
    }

    $status_sql = ' 1 = 1';
    if (isset($filter['status'])) {
        switch ($filter['status']) {
            case AGENT_STATUS_NORMAL:
                $status_sql = '(
					critical_count = 0
					AND warning_count = 0
					AND unknown_count = 0 
					AND normal_count > 0)';
            break;

            case AGENT_STATUS_WARNING:
                $status_sql = '(
					critical_count = 0 
					AND warning_count > 0
					AND total_count > 0)';
            break;

            case AGENT_STATUS_CRITICAL:
                $status_sql = 'critical_count > 0';
            break;

            case AGENT_STATUS_UNKNOWN:
                $status_sql = '(
					critical_count = 0 
					AND warning_count = 0 
					AND unknown_count > 0)';
            break;

            case AGENT_STATUS_NOT_NORMAL:
                $status_sql = '(
					normal_count <> total_count
					OR total_count = notinit_count)';
                // The AGENT_STATUS_NOT_NORMAL filter must show all agents that are not in normal status
                    /*
                        "(
                        normal_count <> total_count
                        AND
                        (normal_count + notinit_count) <> total_count)";*/
            break;

            case AGENT_STATUS_NOT_INIT:
                $status_sql = '(
					total_count = 0
					OR total_count = notinit_count)';
            break;
        }

        unset($filter['status']);
    }

    unset($filter['order']);

    $filter_nogroup = $filter;

    // Get user groups
    $groups = array_keys(users_get_groups($config['id_user'], $access, false));

    // If no group specified, get all user groups
    if (empty($filter['id_grupo'])) {
        $all_groups = true;
        $filter['id_grupo'] = $groups;
    } else if (! is_array($filter['id_grupo'])) {
        $all_groups = false;
        // If group is specified but not allowed, return false
        if (! in_array($filter['id_grupo'], $groups)) {
            return false;
        }

        $filter['id_grupo'] = (array) $filter['id_grupo'];
        // Make an array
    } else {
        $all_groups = true;
        // Check each group specified to the user groups, remove unwanted groups
        foreach ($filter['id_grupo'] as $key => $id_group) {
            if (! in_array($id_group, $groups)) {
                unset($filter['id_grupo'][$key]);
            }
        }

        // If no allowed groups are specified return false
        if (count($filter['id_grupo']) == 0) {
            return false;
        }
    }

    $filter['id_group'] = $filter['id_grupo'];

    if (in_array(0, $filter['id_grupo'])) {
        unset($filter['id_grupo']);
        unset($filter['id_group']);
    }

    if (!is_array($fields)) {
        $fields = [];
        $fields[0] = 'id_agente';
        $fields[1] = 'nombre';
    }

    if (isset($order['field'])) {
        if (!isset($order['order'])) {
            $order['order'] = 'ASC';
        }

        if (!isset($order['field2'])) {
            $order = 'ORDER BY '.$order['field'].' '.$order['order'];
        } else {
            $order = 'ORDER BY '.$order['field'].' '.$order['order'].', '.$order['field2'];
        }
    }

    // Fix for postgresql
    if (empty($filter['id_agente'])) {
        unset($filter['id_agente']);
    }

    // Group filter with secondary groups
    $where_secondary = '';
    if (isset($filter['id_group']) && isset($filter['id_grupo'])) {
        $where_secondary .= db_format_array_where_clause_sql(
            [
                'tagent_secondary_group.id_group' => $filter['id_group'],
                'id_grupo'                        => $filter['id_grupo'],
            ],
            'OR',
            ''
        );
        unset($filter['id_group']);
        unset($filter['id_grupo']);
        unset($filter_nogroup['id_grupo']);
        unset($filter_nogroup['id_group']);
    }

    // Add the group filter to
    $where = db_format_array_where_clause_sql($filter, 'AND', '('.$where_secondary.') AND ');
    if ($where == '' && $where_secondary != '') {
        $where = '('.$where_secondary.')';
    }

    $where_nogroup = db_format_array_where_clause_sql(
        $filter_nogroup,
        'AND',
        ''
    );

    if ($where_nogroup == '') {
        $where_nogroup = '1 = 1';
    }

    if ($disabled_agent == 1) {
        $disabled = 'disabled = 0';
    } else {
        $disabled = '1 = 1';
    }

    $extra = false;

    // TODO: CLEAN extra_sql
    $sql_extra = '';
    if ($all_groups) {
        $where_nogroup = '1 = 1';
    }

    $policy_join = '';

    if ($policies !== '') {
        $policy_join = 'INNER JOIN tpolicy_agents
            ON tpolicy_agents.id_agent=tagente.id_agente';
    }

    if ($extra) {
        $where = sprintf(
            '(%s OR (%s)) AND (%s) AND (%s) %s AND %s %s %s %s',
            $sql_extra,
            $where,
            $where_nogroup,
            $status_sql,
            $search,
            $disabled,
            $id_os,
            $policies,
            $other_condition
        );
    } else {
        $where = sprintf(
            '%s AND %s AND (%s) %s AND %s %s %s %s %s',
            $where,
            $where_nogroup,
            $status_sql,
            $search,
            $disabled,
            $search_custom,
            $id_os,
            $policies,
            $other_condition
        );
    }

    $table_name = ($use_meta_table === true) ? 'tmetaconsole_agent' : 'tagente';
    $sql = sprintf(
        'SELECT DISTINCT %s
		FROM `%s` tagente
        LEFT JOIN tagent_secondary_group
            ON tagent_secondary_group.id_agent=tagente.id_agente
        %s
		WHERE %s %s',
        implode(',', $fields),
        $table_name,
        $policy_join,
        $where,
        $order
    );

    $limit_sql = '';
    if (isset($offset) && isset($limit)) {
        $limit_sql = " LIMIT $offset, $limit ";
    }

    $sql = sprintf('%s %s', $sql, $limit_sql);

    if ($return) {
        return $sql;
    } else {
        $agents = db_get_all_rows_sql($sql);
    }

    return $agents;
}


function agents_get_agents_selected($group)
{
    if (is_metaconsole() === true) {
        $all = agents_get_agents(
            ['id_grupo' => $group],
            [
                'id_tagente',
                'id_tmetaconsole_setup',
                'id_agente',
                'alias',
                'server_name',
            ],
            'AR',
            [
                'field' => 'alias',
                'order' => 'ASC',
            ],
            false,
            0,
            true
        );

        $all = array_reduce(
            $all,
            function ($carry, $item) {
                $carry[$item['id_tmetaconsole_setup'].'|'.$item['id_tagente']] = $item['server_name'].' &raquo; '.$item['alias'];
                return $carry;
            },
            []
        );
    } else {
        $all = agents_get_agents(
            ['id_grupo' => $group],
            [
                'id_agente',
                'alias',
            ],
            'AR',
            [
                'field' => 'alias',
                'order' => 'ASC',
            ]
        );

        $all = array_reduce(
            (empty($all) === true) ? [] : $all,
            function ($carry, $item) {
                $carry[$item['id_agente']] = $item['alias'];
                return $carry;
            },
            []
        );
    }

    return $all;
}


/**
 * Get all the alerts of an agent, simple and combined.
 *
 * @param integer                                        $id_agent Agent id
 * @param string Special filter. Can be: "notfired", "fired" or "disabled".
 * @param array Extra filter options in an indexed array. See
 * db_format_array_where_clause_sql()
 *
 * @return array An array with all alerts defined for an agent.
 */
function agents_get_alerts($id_agent=false, $filter=false, $options=false)
{
    $simple_alerts = agents_get_alerts_simple($id_agent, $filter, $options);

    return ['simple' => $simple_alerts];
}


/**
 * Copy the agents config from one agent to the other
 *
 * @param int Agent id
 * @param mixed Agent id or id's (array) to copy to
 * @param bool Whether to copy modules as well (defaults to $_REQUEST['copy_modules'])
 * @param bool Whether to copy alerts as well
 * @param array Which modules to copy.
 * @param array Which alerts to copy. Only will be used if target_modules is empty.
 *
 * @return boolean True in case of good, false in case of bad
 */
function agents_process_manage_config($source_id_agent, $destiny_id_agents, $copy_modules=false, $copy_alerts=false, $target_modules=false, $target_alerts=false)
{
    global $config;

    if (empty($source_id_agent)) {
        ui_print_error_message(__('No source agent to copy'));
        return false;
    }

    if (empty($destiny_id_agents)) {
        ui_print_error_message(__('No destiny agent(s) to copy'));
        return false;
    }

    if ($copy_modules == false) {
        $copy_modules = (bool) get_parameter('copy_modules', $copy_modules);
    }

    if ($copy_alerts == false) {
        $copy_alerts = (bool) get_parameter('copy_alerts', $copy_alerts);
    }

    if (! $copy_modules && ! $copy_alerts) {
        return false;
    }

    if (empty($target_modules)) {
        $target_modules = (array) get_parameter('target_modules', []);
    }

    if (empty($target_alerts)) {
        $target_alerts = (array) get_parameter('target_alerts', []);
    }

    if (empty($target_modules)) {
        if (! $copy_alerts) {
            ui_print_error_message(__('No modules have been selected'));
            return false;
        }

        $target_modules = [];

        foreach ($target_alerts as $id_alert) {
            $alert = alerts_get_alert_agent_module($id_alert);
            if ($alert === false) {
                continue;
            }

            // Check if some alerts which doesn't belong to the agent was given
            if (modules_get_agentmodule_agent($alert['id_agent_module']) != $source_id_agent) {
                continue;
            }

            array_push($target_modules, $alert['id_agent_module']);
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            db_process_sql('SET AUTOCOMMIT = 0');
            db_process_sql('START TRANSACTION');
        break;

        case 'oracle':
            db_process_sql_begin();
        break;
    }

    $error = false;

    $repeated_modules = [];
    foreach ($destiny_id_agents as $id_destiny_agent) {
        foreach ($target_modules as $id_agent_module) {
            // Check the module name exists in target
            $module = modules_get_agentmodule($id_agent_module);
            if ($module === false) {
                return false;
            }

            $modules = agents_get_modules(
                $id_destiny_agent,
                false,
                [
                    'nombre'   => $module['nombre'],
                    'disabled' => false,
                ],
                true,
                true,
                false,
                false
            );

            // Keep all modules repeated
            if (! empty($modules)) {
                $modules_repeated = array_pop(array_keys($modules));
                $result = $modules_repeated;
                $repeated_modules[] = $modules_repeated;
            } else {
                $result = modules_copy_agent_module_to_agent(
                    $id_agent_module,
                    $id_destiny_agent
                );

                if ($result === false) {
                    $error = true;
                    break;
                }
            }

            // Check if all modules are repeated and no alerts are copied, if YES then error
            if (empty($target_alerts) and count($repeated_modules) == count($target_modules)) {
                $error = true;
                break;
            }

            $id_destiny_module = $result;

            if (! $copy_alerts) {
                continue;
            }

            /*
                If the alerts were given, copy afterwards. Otherwise, all the
            alerts for the module will be copied */
            if (! empty($target_alerts)) {
                foreach ($target_alerts as $id_alert) {
                    $alert = alerts_get_alert_agent_module($id_alert);
                    if ($alert === false) {
                        continue;
                    }

                    if ($alert['id_agent_module'] != $id_agent_module) {
                        continue;
                    }

                    $result = alerts_copy_alert_module_to_module(
                        $alert['id'],
                        $id_destiny_module
                    );
                    if ($result === false) {
                        $error = true;
                        break;
                    }
                }

                continue;
            }

            $alerts = alerts_get_alerts_agent_module($id_agent_module, true);

            if ($alerts === false) {
                continue;
            }

            foreach ($alerts as $alert) {
                $result = alerts_copy_alert_module_to_module(
                    $alert['id'],
                    $id_destiny_module
                );
                if ($result === false) {
                    $error = true;
                    break;
                }
            }
        }

        if ($error) {
            break;
        }
    }

    if ($error) {
        ui_print_error_message(
            __('There was an error copying the agent configuration, the copy has been cancelled')
        );
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                db_process_sql('ROLLBACK');
            break;

            case 'oracle':
                db_process_sql_rollback();
            break;
        }
    } else {
        ui_print_success_message(__('Successfully copied'));
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                db_process_sql('COMMIT');
            break;

            case 'oracle':
                db_process_sql_commit();
            break;
        }
    }

    switch ($config['dbtype']) {
        case 'mysql':
        case 'postgresql':
            db_process_sql('SET AUTOCOMMIT = 1');
        break;
    }

    if ($error) {
        return false;
    } else {
        return true;
    }
}


function agents_get_next_contact($idAgent, $maxModules=false)
{
    $agent = db_get_row('tagente', 'id_agente', $idAgent);
    $last_contact = time_w_fixed_tz($agent['ultimo_contacto']);
    $difference = (time() - $last_contact);

    return ($agent['intervalo'] > 0 && $last_contact > 0) ? round($difference / ($agent['intervalo'] / 100)) : 0;
}


/**
 * Get all the modules common in various agents. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected. A special
 * character "*" will select all the values.
 * @param mixed Aditional filters to the modules. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword).
 * @param bool Wheter to return the modules indexed by the id_agente_modulo or
 * not. Default is indexed.
 * Example:
 <code>
 Both are similars:
 $modules = agents_get_modules ($id_agent, false, array ('disabled' => 0));
 $modules = agents_get_modules ($id_agent, false, 'disabled = 0');

 Both are similars:
 $modules = agents_get_modules ($id_agent, '*', array ('disabled' => 0, 'history_data' => 0));
 $modules = agents_get_modules ($id_agent, '*', 'disabled = 0 AND history_data = 0');
 </code>
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function agents_common_modules($id_agent, $filter=false, $indexed=true, $get_not_init_modules=true)
{
    $id_agent = safe_int($id_agent, 1);

    $where = '';
    if (! empty($id_agent)) {
        $where = sprintf(
            ' WHERE delete_pending = 0 AND id_agente IN (%s)
			AND (
				SELECT count(nombre)
				FROM tagente_modulo t2
				WHERE delete_pending = 0 AND t1.nombre = t2.nombre
					AND id_agente IN (%s)) = (%s)',
            implode(',', (array) $id_agent),
            implode(',', (array) $id_agent),
            count($id_agent)
        );
    }

    if (! empty($filter)) {
        $where .= ' AND ';
        if (is_array($filter)) {
            $fields = [];
            foreach ($filter as $field => $value) {
                array_push($fields, $field.'="'.$value.'"');
            }

            $where .= implode(' AND ', $fields);
        } else {
            $where .= $filter;
        }
    }

    $sql = sprintf(
        'SELECT DISTINCT(t1.id_agente_modulo) as id_agente_modulo
		FROM tagente_modulo t1, talert_template_modules t2
		%s
		ORDER BY nombre',
        $where
    );
    $result = db_get_all_rows_sql($sql);

    if (empty($result)) {
        return [];
    }

    if (! $indexed) {
        return $result;
    }

    $modules = [];
    foreach ($result as $module) {
        if ($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
            $modules[$module['id_agente_modulo']] = $module['id_agente_modulo'];
        }
    }

    return $modules;
}


/**
 * Get all the agents within a group(s).
 *
 * @param mixed   $id_group          Group id or an array of ID's. If nothing is selected, it will select all
 * @param mixed   $search            to add Default: False. If True will return disabled agents as well. If searching array (disabled => (bool), string => (string))
 * @param string  $case              Which case to return the agentname as (lower, upper, none)
 * @param boolean $noACL             jump the ACL test.
 * @param boolean $childGroups       The flag to get agents in the child group of group parent passed. By default false.
 * @param boolean $serialized        Only in metaconsole. Return the key as <server id><SEPARATOR><agent id>. By default false.
 * @param string  $separator         Only in metaconsole. Separator for the serialized data. By default |.
 * @param boolean $add_alert_bulk_op //TODO documentation
 * @param boolean $force_serialized. If the agent has not id_server (typically in node) put 0 as <server_id>.
 * @param boolean $meta_fields       If true, then id_agente is returned instead id_tagente.
 *
 * @return array An array with all agents in the group or an empty array
 */
function agents_get_group_agents(
    $id_group=0,
    $search=false,
    $case='lower',
    $noACL=false,
    $childGroups=false,
    $serialized=false,
    $separator='|',
    $add_alert_bulk_op=false,
    $force_serialized=false,
    $meta_fields=false
) {
    global $config;

    $filter = [];

    // Check available groups for target user only if asking for 'All' group.
    if (!$noACL && $id_group == 0) {
        $id_group = ($id_group == 0) ? array_keys(users_get_groups(false, 'AR', false)) : groups_safe_acl($config['id_user'], $id_group, 'AR');
        if (empty($id_group)) {
            // An empty array means the user doesn't have access.
            return [];
        }
    }

    if ($childGroups) {
        if (is_array($id_group) === true) {
            foreach ($id_group as $parent) {
                $id_group = array_merge(
                    $id_group,
                    groups_get_children_ids($parent, $noACL)
                );
            }
        } else {
            $id_group = array_merge(
                [$id_group],
                groups_get_children_ids($id_group, $noACL)
            );
        }

        // Check available groups for target user only if asking for 'All' group.
        if (!$noACL && $id_group == 0) {
            $id_group = array_keys(
                users_get_groups(false, 'AR', true, false, (array) $id_group)
            );
        }
    }

    // Search for primary and secondary groups.
    if (empty($id_group) === false) {
        $filter[] = '('.db_format_array_where_clause_sql(
            [
                'id_group' => $id_group,
                'id_grupo' => $id_group,
            ],
            'OR'
        ).')';
    }

    if (is_array($search) === true) {
        if (!$search['all_agents']) {
            $filter['disabled'] = 0;
            if (isset($search['disabled']) === true) {
                $filter['disabled'] = (int) $search['disabled'];
                unset($search['disabled']);
            }
        }

        if ((isset($search['disabled']) === true
            && $search['disabled'] === 2)
            || (isset($filter['disabled']) === true
            && $filter['disabled'] === 2)
        ) {
            unset($search['disabled']);
            unset($filter['disabled']);
        }

        if (isset($search['all_agents'])) {
            unset($search['all_agents']);
        }

        if (isset($search['string']) === true) {
            $string = io_safe_input($search['string']);
            $filter[] = "(nombre LIKE '%$string%' OR direccion LIKE '%$string%')";
            unset($search['string']);
        }

        if (isset($search['matchIds']) === true && is_array($search['matchIds']) === true) {
            $filter[] = sprintf('id_agente IN (%s)', implode(', ', $search['matchIds']));
            unset($search['matchIds']);
        }

        if (isset($search['name']) === true) {
            $name = io_safe_input($search['name']);
            $filter[] = "nombre LIKE '$name'";
            unset($search['name']);
        }

        if (isset($search['alias']) === true) {
            $name = io_safe_input($search['alias']);
            $filter[] = "alias LIKE '$name'";
            unset($search['alias']);
        }

        if (isset($search['aliasRegex']) === true) {
            $name = io_safe_input($search['aliasRegex']);
            $filter[] = sprintf(
                'alias REGEXP "%s"',
                $name
            );
            unset($search['aliasRegex']);
        }

        if (isset($search['id_os']) === true) {
            $filter['id_os'] = $search['id_os'];
        }

        if (isset($search['status']) === true) {
            switch ($search['status']) {
                case AGENT_STATUS_NORMAL:
                    $filter[] = '(
						critical_count = 0
						AND warning_count = 0
						AND unknown_count = 0
						AND normal_count > 0)';
                break;

                case AGENT_STATUS_WARNING:
                    $filter[] = '(
						critical_count = 0
						AND warning_count > 0
						AND total_count > 0)';
                break;

                case AGENT_STATUS_CRITICAL:
                    $filter[] = 'critical_count > 0';
                break;

                case AGENT_STATUS_UNKNOWN:
                    $filter[] = '(
						critical_count = 0
						AND warning_count = 0
						AND unknown_count > 0)';
                break;

                case AGENT_STATUS_NOT_NORMAL:
                    $filter[] = '(
						critical_count > 0
						OR warning_count > 0
						OR unknown_count > 0
						OR total_count = 0
						OR total_count = notinit_count)';
                break;

                case AGENT_STATUS_NOT_INIT:
                    $filter[] = '(
						total_count = 0
						OR total_count = notinit_count)';
                break;

                default:
                    // Not posible.
                break;
            }

            unset($search['status']);
        }

        if ($add_alert_bulk_op) {
            if (isset($search['id_agente'])) {
                $filter['id_agente'] = $search['id_agente'];
            }
        }

        if (is_metaconsole() === true
            && isset($search['id_server']) === true
            && empty($search['id_server']) === false
        ) {
            $filter['ta.id_tmetaconsole_setup'] = $search['id_server'];

            if ($filter['id_tmetaconsole_setup'] == 0) {
                // All nodes.
                unset($filter['id_tmetaconsole_setup']);
            }
        }

        unset($search['id_server']);

        if (!$add_alert_bulk_op) {
            // Add the rest of the filter from the search array.
            foreach ($search as $key => $value) {
                $filter[$key] = $value;
            }
        }
    } else if ($filter !== true) {
        $filter['disabled'] = 0;
    }

    $filter['order'] = 'alias';

    if (is_metaconsole() === true) {
        $table_name = 'tmetaconsole_agent ta LEFT JOIN tmetaconsole_agent_secondary_group tasg ON ta.id_agente = tasg.id_agent';

        if ($meta_fields === true) {
            $fields = [
                'id_agente',
                'alias',
                'ta.id_tmetaconsole_setup AS id_server',
                'ta.disabled',
            ];
        } else {
            $fields = [
                'ta.id_tagente AS id_agente',
                'alias',
                'ta.id_tmetaconsole_setup AS id_server',
                'ta.disabled',
            ];
        }
    } else {
        $table_name = 'tagente LEFT JOIN tagent_secondary_group ON id_agente=id_agent';

        $fields = [
            'id_agente',
            'alias',
            'disabled',
        ];
    }

    $result = db_get_all_rows_filter($table_name, $filter, $fields);

    if ($result === false) {
        return [];
        // Return an empty array
    }

    $agents = [];
    foreach ($result as $row) {
        if (!isset($row['id_agente']) || !isset($row['alias'])) {
            continue;
        }

        if ($serialized && isset($row['id_server'])) {
            $key = $row['id_server'].$separator.$row['id_agente'];
        } else if ($force_serialized) {
            $key = '0'.$separator.$row['id_agente'];
        } else {
            $key = $row['id_agente'];
        }

        if (($row['id_server'] ?? '') !== '') {
            if (is_metaconsole()) {
                $server_name = db_get_row_filter(
                    'tmetaconsole_setup',
                    'id = '.$row['id_server'].'',
                    'server_name'
                );
                $row['alias'] .= ' ('.$server_name['server_name'].')';
            }
        }

        switch ($case) {
            case 'lower':
                $value = mb_strtolower($row['alias'], 'UTF-8');
            break;

            case 'upper':
                $value = mb_strtoupper($row['alias'], 'UTF-8');
            break;

            case 'disabled':
                $value = $row['alias'];
                if ($row['disabled'] == 1) {
                    $value .= ' ('.__('Disabled').')';
                }
            break;

            default:
                $value = $row['alias'];
            break;
        }

        $agents[$key] = $value;
    }

    return ($agents);
}


/**
 * @deprecated use \PandoraFMS\Agent::searchModules
 *
 *
 * Get all the modules in an agent. If an empty list is passed it will select all
 *
 * @param mixed Agent id to get modules. It can also be an array of agent id's, by default is null and this mean that use the ids of agents in user's groups.
 * @param mixed Array, comma delimited list or singular value of rows to
 * select. If nothing is specified, nombre will be selected. A special
 * character "*" will select all the values.
 * @param mixed Aditional filters to the modules. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator) or a string, including any SQL clause (without
 * the WHERE keyword).
 * @param bool Wheter to return the modules indexed by the id_agente_modulo or
 * not. Default is indexed.
 * Example:
 <code>
 Both are similars:
 $modules = agents_get_modules ($id_agent, false, array ('disabled' => 0));
 $modules = agents_get_modules ($id_agent, false, 'disabled = 0');

 Both are similars:
 $modules = agents_get_modules ($id_agent, '*', array ('disabled' => 0, 'history_data' => 0));
 $modules = agents_get_modules ($id_agent, '*', 'disabled = 0 AND history_data = 0');
 </code>
 *
 * @return array An array with all modules in the agent.
 * If multiple rows are selected, they will be in an array
 */
function agents_get_modules(
    $id_agent=null,
    $details=false,
    $filter=false,
    $indexed=true,
    $get_not_init_modules=true,
    $force_tags=false,
    $filter_include_sql=true
) {
    global $config;

    $userGroups = users_get_groups($config['id_user'], 'AR', false);
    if (empty($userGroups)) {
        return [];
    }

    $id_groups = array_keys($userGroups);
    $id_groups_sql = implode(',', $id_groups);

    // =================================================================
    // When there is not a agent id. Get a agents of groups that the
    // user can read the agents.
    // =================================================================
    if ($id_agent === null) {
        $sql = 'SELECT id_agente
			FROM tagente
			WHERE id_grupo IN ('.implode(',', $id_groups).')';
        $id_agent = db_get_all_rows_sql($sql);

        if ($id_agent == false) {
            $id_agent = [];
        }

        $temp = [];
        foreach ($id_agent as $item) {
            $temp[] = $item['id_agente'];
        }

        $id_agent = $temp;
    }

    // =================================================================
    // Fixed strange values. Only array of ids or id as int
    // =================================================================
    if (!is_array($id_agent)) {
        $id_agent = safe_int($id_agent, 1);
    }

    $where = '1 = 1 ';
    // Groups ACL only when user is not empty
    if (!users_can_manage_group_all('AR')) {
        $where = "(
			tagente.id_grupo IN ($id_groups_sql) OR tasg.id_group IN ($id_groups_sql)
		)";
    }

    if (! empty($id_agent)) {
        $id_agent_sql = implode(',', (array) $id_agent);
        $where .= " AND tagente.id_agente IN ($id_agent_sql) ";
    }

    if (! empty($filter)) {
        $where .= ' AND ';
        if (is_array($filter)) {
            $fields = [];

            // ----------------------------------------------------------
            // Code for filters as array of arrays
            // for example:
            // $filter =  array(
            // 'id_modulo' => 2, // networkmap type
            // 'id_tipo_modulo' => array(
            // '<>2', // != generic_proc
            // '<>6', // != remote_icmp_proc
            // '<>9'));
            // ----------------------------------------------------------
            $list_filter = [];
            foreach ($filter as $field => $value) {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $list_filter[] = [
                            'field' => $field,
                            'value' => $v,
                        ];
                    }
                } else {
                    $list_filter[] = [
                        'field' => $field,
                        'value' => $value,
                    ];
                }
            }

            // ----------------------------------------------------------
            foreach ($list_filter as $item) {
                $field = $item['field'];
                $value = (string) $item['value'];

                // Check <> operator
                $operatorDistin = false;
                if (strlen($value) > 2) {
                    if ($value[0].$value[1] == '<>') {
                        $operatorDistin = true;
                    }
                }

                if ($value[0] == '%' && $filter_include_sql === true) {
                    array_push(
                        $fields,
                        $field.' LIKE "'.$value.'"'
                    );
                } else if ($operatorDistin && $filter_include_sql === true) {
                    array_push($fields, $field.' <> '.substr($value, 2));
                } else if (substr($value, -1) == '%' && $filter_include_sql === true) {
                    array_push($fields, $field.' LIKE "'.$value.'"');
                } else if (strncmp($value, '666=666', 7) == 0) {
                    array_push($fields, ' '.$value);
                } else if (preg_match('/\bin\b/i', $field) && $filter_include_sql === true) {
                    array_push($fields, $field.' '.$value);
                } else {
                    array_push($fields, 'tagente_modulo.'.$field.' = "'.$value.'"');
                }
            }

            $where .= implode(' AND ', $fields);
        } else {
            $where .= $filter;
        }
    }

    $stored_details = $details;
    if (empty($details)) {
        $details = 'tagente_modulo.nombre';
        $stored_details = 'nombre';
    } else {
        $details = (array) $details;
        $details = io_safe_input($details);
        $details = array_map(
            function ($a) {
                return preg_match('/tagente_modulo./i', $a) ? $a : 'tagente_modulo.'.$a;
            },
            $details
        );
    }

    $sql_tags_join = '';
    if (tags_has_user_acl_tags($config['id_user']) || $force_tags) {
        $where_tags = tags_get_acl_tags(
            $config['id_user'],
            $id_groups,
            'AR',
            'module_condition',
            'AND',
            'tagente_modulo',
            false,
            [],
            true
        );
        $where .= "\n\n".$where_tags;
        $sql_tags_join = 'INNER JOIN ttag_module
			ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo';
    }

    $sql = sprintf(
        'SELECT %s%s
					FROM tagente_modulo
					%s
					INNER JOIN tagente
						ON tagente.id_agente = tagente_modulo.id_agente
					LEFT JOIN tagent_secondary_group tasg
						ON tagente.id_agente = tasg.id_agent
					WHERE tagente_modulo.delete_pending = 0
						AND %s
					GROUP BY 1
					ORDER BY tagente_modulo.nombre',
        ($details != 'tagente_modulo.*' && $indexed) ? 'tagente_modulo.id_agente_modulo,' : '',
        io_safe_output(implode(',', (array) $details)),
        $sql_tags_join,
        $where
    );
    $result = db_get_all_rows_sql($sql);

    if (empty($result)) {
        return [];
    }

    if (! $indexed) {
        return $result;
    }

    $modules = [];
    foreach ($result as $module) {
        if ($get_not_init_modules || modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
            if (is_array($stored_details) || $stored_details == '*') {
                // Just stack the information in array by ID
                $modules[$module['id_agente_modulo']] = $module;
            } else {
                $modules[$module['id_agente_modulo']] = $module[$stored_details];
            }
        }
    }

    return $modules;
}


/**
 * Get agent id from a module id that it has.
 *
 * @param integer $id_module Id module is list modules this agent.
 *
 * @return integer Id from the agent of the given id module.
 */
function agents_get_module_id($id_agente_modulo)
{
    return (int) db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agente_modulo);
}


/**
 * Get agent id from an agent name.
 *
 * @param string  $agent_name    Agent name to get its id.
 * @param boolean $io_safe_input If it is true transform to safe string, by default false.
 *
 * @return integer Id from the agent of the given name.
 */
function agents_get_agent_id($agent_name, $io_safe_input=false)
{
    if ($io_safe_input) {
        $agent_name = io_safe_input($agent_name);
    }

    return (int) db_get_value('id_agente', 'tagente', 'nombre', $agent_name);
}


/**
 * Get name of an agent.
 *
 * @param integer $id_agent Agent id.
 * @param string  $case     Case (upper, lower, none)
 *
 * @return string Name of the given agent.
 */
function agents_get_name($id_agent, $case='none')
{
    $agent = (string) db_get_value(
        'nombre',
        'tagente',
        'id_agente',
        (int) $id_agent
    );

    // Version 3.0 has enforced case sensitive agent names
    // so we always should show real case names.
    switch ($case) {
        case 'upper':
        return mb_strtoupper($agent, 'UTF-8');

        case 'lower':
        return mb_strtolower($agent, 'UTF-8');

        case 'none':
        default:
        return ($agent);
    }
}


/**
 * Get the agents names of an agent.
 *
 * @param array $array_ids Agents ids.
 *
 * @return array Id => name.
 */
function agents_get_alias_array($array_ids)
{
    if (is_array($array_ids) === false || empty($array_ids) === true) {
        return [];
    }

    if ((bool) is_metaconsole() === true) {
        $agents = array_reduce(
            $array_ids,
            function ($carry, $item) {
                $explode = explode('|', $item);

                $carry[$explode[0]][] = $explode[1];
                return $carry;
            }
        );

        $result = [];
        foreach ($agents as $tserver => $id_agents) {
            $sql = sprintf(
                'SELECT id_tagente as id, alias as `name`
                FROM tmetaconsole_agent
                WHERE id_tagente IN (%s) AND id_tmetaconsole_setup = %d',
                implode(',', $id_agents),
                $tserver
            );

            $data_server = db_get_all_rows_sql($sql);

            if ($data_server === false) {
                $data_server = [];
            }

            $data_server = array_reduce(
                $data_server,
                function ($carry, $item) {
                    $carry[$item['id']] = $item['name'];
                    return $carry;
                },
                []
            );

            $result[$tserver] = $data_server;
        }
    } else {
        $sql = sprintf(
            'SELECT id_agente as id, alias as `name`
            FROM tagente
            WHERE id_agente IN (%s)',
            implode(',', $array_ids)
        );

        $result = db_get_all_rows_sql($sql);

        if ($result === false) {
            $result = [];
        }

        $result = array_reduce(
            $result,
            function ($carry, $item) {
                $carry[$item['id']] = $item['name'];
                return $carry;
            },
            []
        );
    }

    return $result;
}


/**
 * Get alias of an agent (cached function).
 *
 * @param integer|array $id_agent Agent id or array or box, also a boat.
 * @param string        $case     Case (upper, lower, none).
 *
 * @return string Alias of the given agent.
 */
function agents_get_alias($id_agent, string $case='none')
{
    // Prepare cache.
    static $cache = [];
    if (empty($case) === true) {
        $case = 'none';
    }

    $agent_alias = '';
    if (is_array($id_agent) === true) {
        foreach ($id_agent as $agg) {
            $agent_alias .= agents_get_alias($agg, $case);
        }

        return $agent_alias;
    }

    if (isset($cache[$case]) === false) {
        $cache[$case] = [];
    }

    // Check cache.
    if (is_metaconsole() === false) {
        if (is_numeric($id_agent) === true && isset($cache[$case]) === true
            && isset($cache[$case][$id_agent]) === true
        ) {
            return $cache[$case][$id_agent];
        }
    }

    $alias = (string) db_get_value(
        'alias',
        'tagente',
        'id_agente',
        (int) $id_agent
    );

    switch ($case) {
        case 'upper':
            $alias = mb_strtoupper($alias, 'UTF-8');
        break;

        case 'lower':
            $alias = mb_strtolower($alias, 'UTF-8');
        break;

        default:
            // Not posible.
        break;
    }

    if (is_metaconsole() === false) {
        $cache[$case][$id_agent] = $alias;
    }

    return $alias;
}


/**
 * Get alias of an agent in metaconsole (cached function).
 *
 * @param integer $id_agent  Agent id.
 * @param string  $case      Case (upper, lower, none).
 * @param integer $id_server server id.
 *
 * @return string Alias of the given agent.
 */
function agents_get_alias_metaconsole($id_agent, $case='none', $id_server=false)
{
    global $config;
    // Prepare cache.
    static $cache = [];
    if (empty($case)) {
        $case = 'none';
    }

    // Check cache.
    if (isset($cache[$case][$id_server][$id_agent])) {
        return $cache[$case][$id_server][$id_agent];
    }

    $alias = (string) db_get_value_filter(
        'alias',
        'tmetaconsole_agent',
        [
            'id_tagente'            => $id_agent,
            'id_tmetaconsole_setup' => $id_server,
        ]
    );

    switch ($case) {
        case 'upper':
            $alias = mb_strtoupper($alias, 'UTF-8');
        break;

        case 'lower':
            $alias = mb_strtolower($alias, 'UTF-8');
        break;

        default:
            // Not posible.
        break;
    }

    $cache[$case][$id_server][$id_agent] = $alias;
    return $alias;
}


function agents_get_alias_by_name($name, $case='none')
{
    if (is_metaconsole()) {
        $table = 'tmetaconsole_agent';
    } else {
        $table = 'tagente';
    }

    $alias = (string) db_get_value('alias', $table, 'nombre', $name);

    switch ($case) {
        case 'upper':
        return mb_strtoupper($alias, 'UTF-8');

        case 'lower':
        return mb_strtolower($alias, 'UTF-8');

        case 'none':
        default:
        return ($alias);
    }
}


/**
 * Check if an agent has alerts fired.
 *
 * @param int Agent id.
 *
 * @return boolean True if the agent has fired alerts.
 */
function agents_check_alert_fired($id_agent)
{
    $sql = sprintf(
        'SELECT COUNT(*)
		FROM talert_template_modules, tagente_modulo
		WHERE talert_template_modules.id_agent_module = tagente_modulo.id_agente_modulo
			AND times_fired > 0 AND id_agente = %d',
        $id_agent
    );

    $value = db_get_sql($sql);
    if ($value > 0) {
        return true;
    }

    return false;
}


/**
 * Get the interval of an agent.
 *
 * @param int Agent id.
 *
 * @return integer The interval value of a given agent
 */
function agents_get_interval($id_agent)
{
    return (int) db_get_value('intervalo', 'tagente', 'id_agente', $id_agent);
}


/**
 * Get all data of agent.
 *
 * @param Agent object.
 *
 * @return The interval value and status of last contact or True /False
 */
function agents_get_interval_status($agent, $return_html=true)
{
    $return = '';
    $last_time = time_w_fixed_tz($agent['ultimo_contacto']);
    $now = time();
    $diferencia = ($now - $last_time);
    $time = ui_print_timestamp($last_time, true, ['style' => 'font-size:6.5pt']);
    $min_interval = modules_get_agentmodule_mininterval_no_async($agent['id_agente']);
    if ($return_html) {
        $return = $time;
    } else {
        $return = true;
    }

    if ($diferencia > ($min_interval['min_interval'] * 2) && $min_interval['num_interval'] > 0) {
        if ($return_html) {
            $return = '<b><span style="color: #ff0000;">'.$time.'</span></b>';
        } else {
            $return = false;
        }
    }

    return $return;
}


/**
 * Get the operating system of an agent.
 *
 * @param int Agent id.
 *
 * @return integer The interval value of a given agent
 */
function agents_get_os($id_agent)
{
    return (int) db_get_value('id_os', 'tagente', 'id_agente', $id_agent);
}


/**
 * Get the flag value of an agent module.
 *
 * @param int Agent module id.
 *
 * @return boolean The flag value of an agent module.
 */
function agents_give_agentmodule_flag($id_agent_module)
{
    return db_get_value('flag', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}


/**
 * Assign an IP address to an agent.
 *
 * @param int Agent id
 * @param string IP address to assign
 */
function agents_add_address($id_agent, $ip_address)
{
    global $config;

    // Check if already is attached to agent
    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT COUNT(`ip`)
				FROM taddress_agent, taddress
				WHERE taddress_agent.id_a = taddress.id_a
					AND ip = '%s' AND id_agent = %d",
                $ip_address,
                $id_agent
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                "SELECT COUNT(ip)
				FROM taddress_agent, taddress
				WHERE taddress_agent.id_a = taddress.id_a
					AND ip = '%s' AND id_agent = %d",
                $ip_address,
                $id_agent
            );
        break;
    }

    $current_address = db_get_sql($sql);
    if ($current_address > 0) {
        return;
    }

    // Look for a record with this IP Address
    $id_address = (int) db_get_value('id_a', 'taddress', 'ip', $ip_address);

    if ($id_address === 0) {
        // Create IP address in tadress table
        $id_address = db_process_sql_insert('taddress', ['ip' => $ip_address]);
    }

    // Add address to agent
    $values = [
        'id_a'     => $id_address,
        'id_agent' => $id_agent,
    ];
    db_process_sql_insert('taddress_agent', $values);
}


/**
 * Unassign an IP address from an agent.
 *
 * @param int Agent id
 * @param string IP address to unassign
 */
function agents_delete_address($id_agent, $ip_address, $return=false)
{
    global $config;

    $sql = sprintf(
        "SELECT id_ag
		FROM taddress_agent, taddress
		WHERE taddress_agent.id_a = taddress.id_a
			AND ip = '%s'
			AND id_agent = %d",
        $ip_address,
        $id_agent
    );
    $id_ag = db_get_sql($sql);
    if ($id_ag !== false) {
        db_process_sql_delete('taddress_agent', ['id_ag' => $id_ag]);
    }

    $agent_name = agents_get_name($id_agent, '');
    db_pandora_audit(
        AUDIT_LOG_AGENT_MANAGEMENT,
        "Deleted IP $ip_address from agent '$agent_name'"
    );

    // Need to change main address?
    if (agents_get_address($id_agent) == $ip_address) {
        $new_ips = agents_get_addresses($id_agent);
        if (empty($new_ips)) {
            $new_ip = '';
        } else {
            $new_ip = reset($new_ips);
        }

        // Change main address in agent to first one in the list.
        db_process_sql_update(
            'tagente',
            ['direccion' => $new_ip],
            ['id_agente' => $id_agent]
        );
    } else {
        $new_ip = agents_get_address($id_agent);
        if (empty($new_ip)) {
            $new_ip = '';
        }
    }

    if ($return === true) {
        return $new_ip;
    }
}


/**
 * Get address of an agent.
 *
 * @param int Agent id
 *
 * @return string The address of the given agent
 */
function agents_get_address($id_agent)
{
    return (string) db_get_value('direccion', 'tagente', 'id_agente', (int) $id_agent);
}


/**
 * Get description of an agent.
 *
 * @param int Agent id
 *
 * @return string The address of the given agent
 */
function agents_get_description($id_agent)
{
    return (string) db_get_value('comentarios', 'tagente', 'id_agente', (int) $id_agent);
}


/**
 * Get the agent that matches an IP address
 *
 * @param string IP address to get the agents.
 *
 * @return mixed The agent that has the IP address given. False if none were found.
 */
function agents_get_agent_with_ip($ip_address)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                'SELECT tagente.*
				FROM tagente, taddress, taddress_agent
				WHERE tagente.id_agente = taddress_agent.id_agent
					AND taddress_agent.id_a = taddress.id_a
					AND ip = "%s"',
                $ip_address
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                'SELECT tagente.*
				FROM tagente, taddress, taddress_agent
				WHERE tagente.id_agente = taddress_agent.id_agent
					AND taddress_agent.id_a = taddress.id_a
					AND ip = \'%s\'',
                $ip_address
            );
        break;
    }

    return db_get_row_sql($sql);
}


/**
 * Get all IP addresses of an agent
 *
 * @param int Agent id
 *
 * @return array Array with the IP address of the given agent or an empty array.
 */
function agents_get_addresses($id_agent)
{
    if (is_array($id_agent)) {
        $sql = sprintf(
            'SELECT ip
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent IN (%s)',
            implode(',', $id_agent)
        );
    } else {
        $sql = sprintf(
            'SELECT ip
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent = %d',
            $id_agent
        );
    }

    $ips = db_get_all_rows_sql($sql);

    if ($ips === false) {
        $ips = [];
    }

    $ret_arr = [];
    foreach ($ips as $row) {
        $ret_arr[$row['ip']] = $row['ip'];
    }

    return $ret_arr;
}


/**
 * Get the worst status of all modules of a given agent from the counts.
 *
 * @param array agent to check.
 *
 * @return integer Worst status of an agent for all of its modules.
 * return -1 if the data are wrong
 */
function agents_get_status_from_counts($agent)
{
    // Check if in the data there are all the necessary values
    if (isset($agent['normal_count']) === false
        && isset($agent['warning_count']) === false
        && isset($agent['critical_count']) === false
        && isset($agent['unknown_count']) === false
        && isset($agent['notinit_count']) === false
        && isset($agent['total_count']) === false
    ) {
        return -1;
    }

    // Juanma (05/05/2014) Fix:  This status is not init! 0 modules or all not init.
    if ($agent['notinit_count'] == $agent['total_count']) {
        return AGENT_STATUS_NOT_INIT;
    }

    if ($agent['critical_count'] > 0) {
        return AGENT_STATUS_CRITICAL;
    } else if ($agent['warning_count'] > 0) {
        return AGENT_STATUS_WARNING;
    } else if ($agent['unknown_count'] > 0) {
        return AGENT_STATUS_UNKNOWN;
    } else if ($agent['normal_count'] == $agent['total_count']) {
        return AGENT_STATUS_NORMAL;
    } else if (($agent['normal_count'] + $agent['notinit_count']) == $agent['total_count']) {
        return AGENT_STATUS_NORMAL;
    }

    return -1;
}


/**
 * Get the worst status of all modules of a given agent.
 *
 * @param int Id agent to check.
 * @param bool Whether the call check ACLs or not
 *
 * @return integer Worst status of an agent for all of its modules.
 * The value -1 is returned in case the agent has exceed its interval.
 */
function agents_get_status($id_agent=0, $noACLs=false)
{
    global $config;

    if (!$noACLs) {
        $modules = agents_get_modules(
            $id_agent,
            'id_agente_modulo',
            ['disabled' => 0],
            true,
            false
        );
    } else {
        $filter_modules['id_agente'] = $id_agent;
        $filter_modules['disabled'] = 0;
        $filter_modules['delete_pending'] = 0;
        // Get all non disabled modules of the agent
        $all_modules = db_get_all_rows_filter(
            'tagente_modulo',
            $filter_modules,
            'id_agente_modulo'
        );
        if ($all_modules === false) {
            $all_modules = [];
        }

        $result_modules = [];
        // Skip non init modules
        foreach ($all_modules as $module) {
            if (modules_get_agentmodule_is_init($module['id_agente_modulo'])) {
                $modules[] = $module['id_agente_modulo'];
            }
        }
    }

    if (!isset($modules) || empty($modules) || count($modules) == 0) {
        return AGENT_MODULE_STATUS_NOT_INIT;
    }

    $modules_status = [];
    $modules_async = 0;
    foreach ($modules as $module) {
        $modules_status[] = modules_get_agentmodule_status($module);

        $module_type = modules_get_agentmodule_type($module);
        if (($module_type >= 21 && $module_type <= 23)
            || $module_type == 100
        ) {
            $modules_async++;
        }
    }

    // If all the modules are asynchronous or keep alive, the group cannot be unknown
    if ($modules_async < count($modules)) {
        $time = get_system_time();

        switch ($config['dbtype']) {
            case 'mysql':
                $status = db_get_value_filter(
                    'COUNT(*)',
                    'tagente',
                    [
                        'id_agente' => (int) $id_agent,
                        'UNIX_TIMESTAMP(ultimo_contacto) + intervalo * 2 > '.$time
                    ]
                );
            break;

            case 'postgresql':
                $status = db_get_value_filter(
                    'COUNT(*)',
                    'tagente',
                    [
                        'id_agente' => (int) $id_agent,
                        'ceil(date_part(\'epoch\', ultimo_contacto)) + intervalo * 2 > '.$time
                    ]
                );
            break;

            case 'oracle':
                $status = db_get_value_filter(
                    'count(*)',
                    'tagente',
                    [
                        'id_agente' => (int) $id_agent,
                        'ceil((to_date(ultimo_contacto, \'YYYY-MM-DD HH24:MI:SS\') - to_date(\'19700101000000\',\'YYYYMMDDHH24MISS\')) * ('.SECONDS_1DAY.')) > '.$time
                    ]
                );
            break;
        }

        if (! $status) {
            return AGENT_MODULE_STATUS_UNKNOWN;
        }
    }

    // Checking if any module has alert fired
    if (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_ALERT, $modules_status))) {
        return AGENT_MODULE_STATUS_CRITICAL_ALERT;
    }
    // Checking if any module has alert fired
    else if (is_int(array_search(AGENT_MODULE_STATUS_WARNING_ALERT, $modules_status))) {
        return AGENT_MODULE_STATUS_WARNING_ALERT;
    }
    // Checking if any module has critical status
    else if (is_int(array_search(AGENT_MODULE_STATUS_CRITICAL_BAD, $modules_status))) {
        return AGENT_MODULE_STATUS_CRITICAL_BAD;
    }
    // Checking if any module has critical status
    else if (is_int(array_search(AGENT_MODULE_STATUS_NORMAL_ALERT, $modules_status))) {
        return AGENT_STATUS_ALERT_FIRED;
    }
    // Checking if any module has warning status
    else if (is_int(array_search(AGENT_MODULE_STATUS_WARNING, $modules_status))) {
        return AGENT_MODULE_STATUS_WARNING;
    }
    // Checking if any module has unknown status
    else if (is_int(array_search(AGENT_MODULE_STATUS_UNKNOWN, $modules_status))) {
        return AGENT_MODULE_STATUS_UNKNOWN;
    } else {
        return AGENT_MODULE_STATUS_NORMAL;
    }
}


/**
 * Delete an agent from the database.
 *
 * @param mixed An array of agents ids or a single integer id to be erased
 * @param bool Disable the ACL checking, for default false.
 *
 * @return boolean False if error, true if success.
 */
function agents_delete_agent($id_agents, $disableACL=false)
{
    global $config;

    $error = false;

    // Convert single values to an array
    if (! is_array($id_agents)) {
        $id_agents = (array) $id_agents;
    }

    foreach ($id_agents as $id_agent) {
        $id_agent = (int) $id_agent;
        // Cast as integer
        if ($id_agent < 1) {
            continue;
        }

        $agent_name = agents_get_name($id_agent, '');
        $agent_alias = io_safe_output(agents_get_alias($id_agent));

        // Check for deletion permissions
        $all_groups = agents_get_all_groups_agent($id_agent);
        if ((! check_acl_one_of_groups($config['id_user'], $all_groups, 'AW')) && !$disableACL) {
            return false;
        }

        // A variable where we store that long subquery thing for
        // modules
        $where_modules = 'ANY(SELECT id_agente_modulo FROM tagente_modulo WHERE id_agente = '.$id_agent.')';

        // IP address
        $sql = sprintf(
            'SELECT id_ag
			FROM taddress_agent, taddress
			WHERE taddress_agent.id_a = taddress.id_a
				AND id_agent = %d',
            $id_agent
        );
        $addresses = db_get_all_rows_sql($sql);

        if ($addresses === false) {
            $addresses = [];
        }

        foreach ($addresses as $address) {
            db_process_delete_temp(
                'taddress_agent',
                'id_ag',
                $address['id_ag']
            );
        }

        // We cannot delete tagente_datos and tagente_datos_string here
        // because it's a huge ammount of time. tagente_module has a special
        // field to mark for delete each module of agent deleted and in
        // daily maintance process, all data for that modules are deleted
        // Alert
        db_process_delete_temp(
            'talert_template_modules',
            'id_agent_module',
            $where_modules,
            true
        );

        // Events (up/down monitors)
        // Dont delete here, could be very time-exausting, let the daily script
        // delete them after XXX days
        // db_process_delete_temp ("tevento", "id_agente", $id_agent);
        // Graphs, layouts, reports & networkmapenterprise
        db_process_delete_temp(
            'tgraph_source',
            'id_agent_module',
            $where_modules,
            true
        );
        db_process_delete_temp(
            'tlayout_data',
            'id_agente_modulo',
            $where_modules,
            true
        );
        db_process_delete_temp(
            'treport_content',
            'id_agent_module',
            $where_modules,
            true
        );
        if (enterprise_installed()) {
            $nodes = db_get_all_rows_filter(
                'titem',
                [
                    'source_data' => $id_agent,
                    'type'        => 0,
                ]
            );
            if (empty($nodes)) {
                $nodes = [];
            }

            foreach ($nodes as $node) {
                db_process_delete_temp(
                    'tnetworkmap_ent_rel_nodes',
                    'parent',
                    $node['id']
                );
                db_process_delete_temp(
                    'tnetworkmap_ent_rel_nodes',
                    'child',
                    $node['id']
                );
            }

            db_process_delete_temp(
                'titem',
                'source_data',
                $id_agent
            );
        }

        // Planned Downtime
        db_process_delete_temp(
            'tplanned_downtime_agents',
            'id_agent',
            $id_agent
        );

        // Process a controlled module ellimination, keeping the old behaviour
        // a couple of lines below this section.
        try {
            $filter = ['id_agente' => $id_agent];
            $modules = [];
            $rows = \db_get_all_rows_filter(
                'tagente_modulo',
                $filter
            );
            if (is_array($rows) === true) {
                foreach ($rows as $row) {
                    $modules[] = PandoraFMS\Module::build($row, '\PandoraFMS\Module', true);
                }
            }

            foreach ($modules as $module) {
                $module->delete();
            }
        } catch (Exception $e) {
            // Ignore.
            error_log($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        }

        // The status of the module.
        db_process_delete_temp('tagente_estado', 'id_agente', $id_agent);

        // The actual modules, don't put anything based on
        // DONT Delete this, just mark for deletion
        // db_process_delete_temp ("tagente_modulo", "id_agente", $id_agent);.
        db_process_sql_update(
            'tagente_modulo',
            [
                'delete_pending' => 1,
                'disabled'       => 1,
                'nombre'         => 'pendingdelete',
            ],
            'id_agente = '.$id_agent
        );

        // Access entries
        // Dont delete here, this records are deleted in daily script
        // db_process_delete_temp ("tagent_access", "id_agent", $id_agent);
        // Delete agent policies.
        enterprise_include_once('include/functions_policies.php');
        enterprise_hook('policies_delete_agent', [$id_agent]);

        if (enterprise_installed() === true) {
            // Delete agent in networkmap.
            enterprise_include_once('include/functions_networkmap.php');
            networkmap_delete_nodes_by_agent([$id_agent]);

            // Delete command targets with agent.
            enterprise_include_once('include/lib/RCMDFile.class.php');

            $target_filter = ['id_agent' => $id_agent];

            // Retrieve all commands that have targets with specific agent id.
            $commands = RCMDFile::getAll(
                ['rct.rcmd_id'],
                $target_filter
            );

            if (is_array($commands) === true) {
                foreach ($commands as $command) {
                    $rcmd_id = $command['rcmd_id'];
                    $rcmd = new RCMDFile($rcmd_id);

                    $command_targets = [];

                    $command_targets = $rcmd->getTargets(false, $target_filter);
                    $rcmd->deleteTargets(array_keys($command_targets));
                }
            }

            // Remove agents from service child list.
            enterprise_include_once('include/functions_services.php');
            \enterprise_hook(
                'service_elements_removal_tool',
                [
                    $id_agent,
                    SERVICE_ELEMENT_AGENT,
                ]
            );
        }

        // Tagente_datos_inc.
        // Dont delete here, this records are deleted later, in database script.
        // db_process_delete_temp ("tagente_datos_inc", "id_agente_modulo", $where_modules, true);
        // Delete remote configuration.
        if (enterprise_installed() === true) {
            if (isset($config['remote_config']) === true) {
                enterprise_include_once('include/functions_config_agents.php');
                if (enterprise_hook('config_agents_has_remote_configuration', [$id_agent])) {
                    $agent_name = agents_get_name($id_agent);
                    $agent_name = io_safe_output($agent_name);
                    $agent_alias = io_safe_output(agents_get_alias($id_agent));
                    $agent_md5 = md5($agent_name, false);

                    // Agent remote configuration editor.
                    $file_name = $config['remote_config'].'/conf/'.$agent_md5.'.conf';

                    $error = !@unlink($file_name);

                    if ((bool) $error === false) {
                        $file_name = $config['remote_config'].'/md5/'.$agent_md5.'.md5';
                        $error = !@unlink($file_name);
                    } else {
                        db_pandora_audit(
                            AUDIT_LOG_AGENT_MANAGEMENT,
                            sprintf('Error: Deleted agent %s, the error is in the delete conf or md5.', $agent_alias)
                        );
                    }
                }
            }
        }

        // And at long last, the agent.
        db_process_delete_temp('tagente', 'id_agente', $id_agent);

        db_process_sql('delete from ttag_module where id_agente_modulo in (select id_agente_modulo from tagente_modulo where id_agente = '.$id_agent.')');

        db_pandora_audit(
            AUDIT_LOG_AGENT_MANAGEMENT,
            sprintf('Deleted agent %s', $agent_alias)
        );

        // Delete the agent from the metaconsole cache.
        enterprise_include_once('include/functions_agents.php');
        enterprise_hook('agent_delete_from_cache', [$id_agent]);

        // Delete agent from fav menu.
        db_process_sql_delete(
            'tfavmenu_user',
            [
                'id_element' => $id_agent,
                'section'    => 'Agents',
                'id_user'    => $config['id_user'],
            ]
        );

        // Break the loop on error.
        if ((bool) $error === true) {
            break;
        }
    }

    if ((bool) $error === true) {
        return false;
    } else {
        return true;
    }
}


/**
 * This function gets the agent group for a given agent module
 *
 * @param int The agent module id
 *
 * @return integer The group id
 */
function agents_get_agentmodule_group($id_module)
{
    $agent = (int) modules_get_agentmodule_agent((int) $id_module);
    return (int) agents_get_agent_group($agent);
}


/**
 * This function gets the group for a given agent
 *
 * @param int The agent id
 * @param bool True to use the metaconsole tables
 *
 * @return integer The group id
 */
function agents_get_agent_group($id_agent, $force_meta=false)
{
    return (int) db_get_value(
        'id_grupo',
        $force_meta ? 'tmetaconsole_agent' : 'tagente',
        'id_agente',
        (int) $id_agent
    );
}


/**
 * This function gets the count of incidents attached to the agent
 *
 * @param int The agent id
 *
 * @return mixed The incidents attached or false
 */
function agents_get_count_incidents($id_agent)
{
    if (empty($id_agent)) {
        return false;
    }

    return db_get_value(
        'count(*)',
        'tincidencia',
        'id_agent',
        $id_agent
    );
}


/**
 * Get critical monitors by using the status code in modules.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed The incidents attached or false
 */
function agents_monitor_critical($id_agent, $filter='')
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "SELECT critical_count
		FROM tagente
		WHERE id_agente = $id_agent".$filter
    );
}


// Get warning monitors by using the status code in modules.
function agents_monitor_warning($id_agent, $filter='')
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "SELECT warning_count
		FROM tagente
		WHERE id_agente = $id_agent".$filter
    );
}


// Get unknown monitors by using the status code in modules.
function agents_monitor_unknown($id_agent, $filter='')
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "SELECT unknown_count
		FROM tagente
		WHERE id_agente = $id_agent".$filter
    );
}


// Get ok monitors by using the status code in modules.
function agents_monitor_ok($id_agent, $filter='')
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "SELECT normal_count
		FROM tagente
		WHERE id_agente = $id_agent".$filter
    );
}


/**
 * Get all monitors disabled of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed Total module count or false
 */
function agents_monitor_disabled($id_agent, $filter='')
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "
		SELECT COUNT( DISTINCT tagente_modulo.id_agente_modulo)
		FROM tagente, tagente_modulo
		WHERE tagente_modulo.id_agente = tagente.id_agente
			AND tagente_modulo.disabled = 1
			AND tagente.id_agente = $id_agent".$filter
    );
}


/**
 * Get all monitors notinit of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 *
 * @return mixed Total module count or false
 */
function agents_monitor_notinit($id_agent, $filter='')
{
    if (!empty($filter)) {
        $filter = ' AND '.$filter;
    }

    return db_get_sql(
        "SELECT notinit_count
		FROM tagente
		WHERE id_agente = $id_agent".$filter
    );
}


/**
 * Get all monitors of an specific agent.
 *
 * @param int The agent id
 * @param string Additional filters
 * @param bool Whether to retrieve disabled modules or not
 *
 * @return mixed Total module count or false
 */
function agents_monitor_total($id_agent, $filter='', $disabled=false)
{
    if ($filter) {
        $filter = ' AND '.$filter;
    }

    $sql = "SELECT COUNT( DISTINCT tagente_modulo.id_agente_modulo) 
		FROM tagente_estado, tagente, tagente_modulo 
		WHERE tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
			AND tagente_estado.id_agente = tagente.id_agente 
			AND tagente.id_agente = $id_agent".$filter;

    if (!$disabled) {
        $sql .= ' AND tagente.disabled = 0 AND tagente_modulo.disabled = 0';
    }

    return db_get_sql($sql);
}


// Returns the alert image to display tree view
function agents_tree_view_alert_img($alert_fired)
{
    if ($alert_fired) {
        return ui_print_status_image(STATUS_ALERT_FIRED, __('Alert fired'), true);
    } else {
        return ui_print_status_image(STATUS_ALERT_NOT_FIRED, __('Alert not fired'), true);
    }
}


// Returns the alert ball image to display tree view
function agents_tree_view_alert_img_ball($alert_fired)
{
    if ($alert_fired) {
        return ui_print_status_image(STATUS_ALERT_FIRED_BALL, __('Alert fired'), true);
    } else {
        return ui_print_status_image(STATUS_ALERT_NOT_FIRED_BALL, __('Alert not fired'), true);
    }
}


// Returns the status image to display tree view
function agents_tree_view_status_img($critical, $warning, $unknown, $total, $notinit)
{
    if ($total == 0 || $total == $notinit) {
        return ui_print_status_image(
            STATUS_AGENT_NO_MONITORS,
            __('No Monitors'),
            true
        );
    }

    if ($critical > 0) {
        return ui_print_status_image(
            STATUS_AGENT_CRITICAL,
            __('At least one module in CRITICAL status'),
            true
        );
    } else if ($warning > 0) {
        return ui_print_status_image(
            STATUS_AGENT_WARNING,
            __('At least one module in WARNING status'),
            true
        );
    } else if ($unknown > 0) {
        return ui_print_status_image(
            STATUS_AGENT_DOWN,
            __('At least one module is in UKNOWN status'),
            true
        );
    } else {
        return ui_print_status_image(
            STATUS_AGENT_OK,
            __('All Monitors OK'),
            true
        );
    }
}


// Returns the status ball image to display tree view
function agents_tree_view_status_img_ball($critical, $warning, $unknown, $total, $notinit, $alerts)
{
    if ($total == 0 || $total == $notinit) {
        return ui_print_status_image(
            STATUS_AGENT_NO_MONITORS_BALL,
            __('No Monitors'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    }

    if ($alerts > 0) {
        return ui_print_status_image(
            STATUS_ALERT_FIRED_BALL,
            __('Alert fired on agent'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    }

    if ($critical > 0) {
        return ui_print_status_image(
            STATUS_AGENT_CRITICAL_BALL,
            __('At least one module in CRITICAL status'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    } else if ($warning > 0) {
        return ui_print_status_image(
            STATUS_AGENT_WARNING_BALL,
            __('At least one module in WARNING status'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    } else if ($unknown > 0) {
        return ui_print_status_image(
            STATUS_AGENT_DOWN_BALL,
            __('At least one module is in UKNOWN status'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    } else {
        return ui_print_status_image(
            STATUS_AGENT_OK_BALL,
            __('All Monitors OK'),
            true,
            [
                'is_tree_view',
                true,
            ],
            false,
            // Use CSS shape instead of image.
            true
        );
    }
}


// Returns the status image to display agent detail view
function agents_detail_view_status_img($critical, $warning, $unknown, $total, $notinit)
{
    if ($total == 0 || $total == $notinit) {
        return ui_print_status_image(
            STATUS_AGENT_NOT_INIT,
            __('No Monitors'),
            true,
            false,
            'images'
        );
    } else if ($critical > 0) {
        return ui_print_status_image(
            STATUS_AGENT_CRITICAL,
            __('At least one module in CRITICAL status'),
            true,
            false,
            'images'
        );
    } else if ($warning > 0) {
        return ui_print_status_image(
            STATUS_AGENT_WARNING,
            __('At least one module in WARNING status'),
            true,
            false,
            'images'
        );
    } else if ($unknown > 0) {
        return ui_print_status_image(
            STATUS_AGENT_UNKNOWN,
            __('At least one module is in UKNOWN status'),
            true,
            false,
            'images'
        );
    } else {
        return ui_print_status_image(
            STATUS_AGENT_OK,
            __('All Monitors OK'),
            true,
            false,
            'images'
        );
    }
}


function agents_update_gis(
    $idAgente,
    $latitude,
    $longitude,
    $altitude,
    $ignore_new_gis_data,
    $manual_placement,
    $start_timestamp,
    $end_timestamp,
    $number_of_packages,
    $description_save_history,
    $description_update_gis,
    $description_first_insert
) {
    $previusAgentGISData = db_get_row_sql(
        '
		SELECT *
		FROM tgis_data_status
		WHERE tagente_id_agente = '.$idAgente
    );

    db_process_sql_update(
        'tagente',
        ['update_gis_data' => $updateGisData],
        ['id_agente' => $idAgente]
    );

    $return = false;

    if ($previusAgentGISData !== false) {
        $return = db_process_sql_insert(
            'tgis_data_history',
            [
                'longitude'          => $previusAgentGISData['stored_longitude'],
                'latitude'           => $previusAgentGISData['stored_latitude'],
                'altitude'           => $previusAgentGISData['stored_altitude'],
                'start_timestamp'    => $previusAgentGISData['start_timestamp'],
                'end_timestamp'      => $end_timestamp,
                'description'        => $description_save_history,
                'manual_placement'   => $previusAgentGISData['manual_placement'],
                'number_of_packages' => $previusAgentGISData['number_of_packages'],
                'tagente_id_agente'  => $previusAgentGISData['tagente_id_agente'],
            ]
        );
        $return = db_process_sql_update(
            'tgis_data_status',
            [
                'tagente_id_agente'  => $idAgente,
                'current_longitude'  => $longitude,
                'current_latitude'   => $latitude,
                'current_altitude'   => $altitude,
                'stored_longitude'   => $longitude,
                'stored_latitude'    => $latitude,
                'stored_altitude'    => $altitude,
                'start_timestamp'    => $start_timestamp,
                'manual_placement'   => $manual_placement,
                'description'        => $description_update_gis,
                'number_of_packages' => $number_of_packages,
            ],
            ['tagente_id_agente' => $idAgente]
        );
    } else {
        // The table "tgis_data_status" have not a autonumeric
        // then the mysql_insert_id function return 0
        $prev_count = db_get_num_rows('SELECT * FROM tgis_data_status');

        $return = db_process_sql_insert(
            'tgis_data_status',
            [
                'tagente_id_agente'  => $idAgente,
                'current_longitude'  => $longitude,
                'current_latitude'   => $latitude,
                'current_altitude'   => $altitude,
                'stored_longitude'   => $longitude,
                'stored_latitude'    => $latitude,
                'stored_altitude'    => $altitude,
                'start_timestamp'    => $start_timestamp,
                'manual_placement'   => $manual_placement,
                'description'        => $description_first_insert,
                'number_of_packages' => $number_of_packages,
            ]
        );

        $count = db_get_num_rows('SELECT * FROM tgis_data_status');

        if ($return === 0) {
            if ($prev_count < $count) {
                $return = true;
            }
        }
    }

    return (bool) $return;
}


/**
 * Returns a list with network interfaces data by agent
 *
 * @param array Agents with the columns 'id_agente', 'nombre' and 'id_grupo'.
 * @param mixed A filter to search the agents if the first parameter is false.
 *
 * @return array A list of network interfaces information by agents.
 */
function agents_get_network_interfaces($agents=false, $agents_filter=false)
{
    global $config;

    if ($agents === false) {
        $filter = false;
        if ($agents_filter !== false) {
            $filter = $agents_filter;
        }

        $fields = [
            'id_agente',
            'alias',
            'id_grupo',
        ];
        $agents = agents_get_agents($filter, $fields);
    }

    $ni_by_agents = [];
    foreach ($agents as $agent) {
        $agent_id = (isset($agent['id_agente'])) ? $agent['id_agente'] : $agent;
        $agent_group_id = (isset($agent['id_grupo']) === true) ? $agent['id_grupo'] : agents_get_agent_group($agent_id);
        $agent_name = (isset($agent['alias']) === true) ? $agent['alias'] : agents_get_alias($agent_id);
        $agent_interfaces = [];

        $accepted_module_types = [];
        $remote_snmp_proc = (int) db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'remote_snmp_proc'
        );
        if ($remote_snmp_proc) {
            $accepted_module_types[] = $remote_snmp_proc;
        }

        $remote_icmp_proc = (int) db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'remote_icmp_proc'
        );
        if ($remote_icmp_proc) {
            $accepted_module_types[] = $remote_icmp_proc;
        }

        $remote_tcp_proc = (int) db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'remote_tcp_proc'
        );
        if ($remote_tcp_proc) {
            $accepted_module_types[] = $remote_tcp_proc;
        }

        $generic_proc = (int) db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'generic_proc'
        );
        if ($generic_proc) {
            $accepted_module_types[] = $generic_proc;
        }

        $remote_snmp = (int) db_get_value(
            'id_tipo',
            'ttipo_modulo',
            'nombre',
            'remote_snmp'
        );
        if ($remote_snmp) {
            $accepted_module_types[] = $remote_snmp;
        }

        if (empty($accepted_module_types)) {
            $accepted_module_types[] = 0;
            // No modules will be returned
        }

        $columns = [
            'id_agente_modulo',
            'nombre',
            'ip_target',
        ];

        if ($config['dbtype'] == 'oracle') {
            $columns[] = 'TO_CHAR(descripcion) AS descripcion';
        } else {
            $columns[] = 'descripcion';
        }

        $filter = " tagente_modulo.id_agente = $agent_id AND tagente_modulo.disabled = 0 AND tagente_modulo.id_tipo_modulo IN (".implode(',', $accepted_module_types).") AND (tagente_modulo.nombre LIKE '%_ifOperStatus' OR tagente_modulo.nombre LIKE 'ifOperStatus_%')";
        $modules = agents_get_modules(
            $agent_id,
            $columns,
            $filter,
            true,
            false
        );
        if (!empty($modules)) {
            $interfaces = [];

            foreach ($modules as $module) {
                $module_name = (string) $module['nombre'];

                // Trying to get the interface name from the module name
                if (preg_match('/^(.+)_if.+/', $module_name, $matches)) {
                    if ($matches[1]) {
                        $interface_name = $matches[1];
                        $interface_name_escaped = str_replace('/', '\/', $interface_name);
                        $interfaces[$interface_name] = $module;
                        $type_interface = 1;
                    }
                } else if (preg_match('/^if.+_(.+)$/', $module_name, $matches)) {
                    if ($matches[1]) {
                        $interface_name = $matches[1];
                        $interface_name_escaped = str_replace('/', '\/', $interface_name);
                        $interfaces[$interface_name] = $module;
                        $type_interface = 0;
                    }
                }
            }

            unset($modules);

            foreach ($interfaces as $interface_name => $module) {
                $interface_name_escaped = str_replace('/', '\/', $interface_name);

                $module_id = $module['id_agente_modulo'];
                $module_name = $module['nombre'];
                $module_description = $module['descripcion'];
                $db_status = modules_get_agentmodule_status($module_id);
                $module_value = modules_get_last_value($module_id);
                $last_contact = modules_get_last_contact($module_id);
                modules_get_status($module_id, $db_status, $module_value, $status, $title);
                $status_image = ui_print_status_image($status, $title, true);

                $ip_target = '--';
                // Trying to get something like an IP from the description
                if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $module_description, $matches)
                    || preg_match(
                        "/(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:?)|\2))(?4){5}((?4){2}|(25[0-5]|
							(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})/i",
                        $module_description,
                        $matches
                    ) && $matches[0]
                ) {
                    $ip_target = $matches[0];
                }

                // else if (isset($module['ip_target']) && !empty($module['ip_target'])) {
                // $ip_target = $module['ip_target'];
                // }
                $mac = '--';
                // Trying to get something like a mac from the description
                if (preg_match('/([0-9a-f]{1,2}[\.:-]){5}([0-9a-f]{1,2})/i', $module_description, $matches)) {
                    if ($matches[0]) {
                        $mac = $matches[0];
                    }
                }

                // Get the ifInOctets and ifOutOctets modules of the interface
                $columns = [
                    'id_agente_modulo',
                    'nombre',
                ];

                if ($type_interface) {
                    $interface_traffic_modules = agents_get_modules($agent_id, $columns, "tagente_modulo.nombre LIKE  '".$interface_name."_if%Octets'");
                } else {
                    $interface_traffic_modules = agents_get_modules($agent_id, $columns, "tagente_modulo.nombre LIKE 'if%Octets_$interface_name'");
                }

                if (!empty($interface_traffic_modules) && count($interface_traffic_modules) >= 2) {
                    $interface_traffic_modules_aux = [
                        'in'  => '',
                        'out' => '',
                    ];
                    foreach ($interface_traffic_modules as $interface_traffic_module) {
                        $interface_name_escaped = str_replace('/', '\/', $interface_name);
                        if ($type_interface) {
                            if (preg_match('/^'.$interface_name_escaped.'_if(.+)Octets$/i', $interface_traffic_module['nombre'], $matches)) {
                                if (strtolower($matches[1]) == 'in' || strtolower($matches[1]) == 'hcin') {
                                    $interface_traffic_modules_aux['in'] = $interface_traffic_module['id_agente_modulo'];
                                } else if (strtolower($matches[1]) == 'out' || strtolower($matches[1]) == 'hcout') {
                                    $interface_traffic_modules_aux['out'] = $interface_traffic_module['id_agente_modulo'];
                                }
                            }
                        } else {
                            if (preg_match("/^if(.+)Octets_$interface_name_escaped$/i", $interface_traffic_module['nombre'], $matches)) {
                                if (strtolower($matches[1]) == 'in' || strtolower($matches[1]) == 'hcin') {
                                    $interface_traffic_modules_aux['in'] = $interface_traffic_module['id_agente_modulo'];
                                } else if (strtolower($matches[1]) == 'out' || strtolower($matches[1]) == 'hcout') {
                                    $interface_traffic_modules_aux['out'] = $interface_traffic_module['id_agente_modulo'];
                                }
                            }
                        }
                    }

                    if (!empty($interface_traffic_modules_aux['in']) && !empty($interface_traffic_modules_aux['out'])) {
                        $interface_traffic_modules = $interface_traffic_modules_aux;
                    } else {
                        $interface_traffic_modules = false;
                    }
                } else {
                    $interface_traffic_modules = false;
                }

                $agent_interfaces[$interface_name] = [];
                $agent_interfaces[$interface_name]['status_image'] = $status_image;
                $agent_interfaces[$interface_name]['status_module_id'] = $module_id;
                $agent_interfaces[$interface_name]['status_module_name'] = $module_name;
                $agent_interfaces[$interface_name]['ip'] = $ip_target;
                $agent_interfaces[$interface_name]['mac'] = $mac;
                $agent_interfaces[$interface_name]['last_contact'] = $last_contact;

                if ($interface_traffic_modules !== false) {
                    $agent_interfaces[$interface_name]['traffic'] = [];
                    $agent_interfaces[$interface_name]['traffic']['in'] = $interface_traffic_modules['in'];
                    $agent_interfaces[$interface_name]['traffic']['out'] = $interface_traffic_modules['out'];
                }
            }
        }

        if (!empty($agent_interfaces)) {
            $ni_by_agents[$agent_id] = [];
            $ni_by_agents[$agent_id]['name'] = $agent_name;
            $ni_by_agents[$agent_id]['group'] = $agent_group_id;
            $ni_by_agents[$agent_id]['interfaces'] = $agent_interfaces;
        }
    }

    return $ni_by_agents;
}


/**
 * Returns the value of the custom field for the selected agent.
 *
 * @param integer Agent id.
 * @param string Name of the custom field.
 *
 * @return mixed The custom field value or false on error.
 */
function agents_get_agent_custom_field($agent_id, $custom_field_name)
{
    if (empty($agent_id) && empty($custom_field_name)) {
        return false;
    }

    $sql = sprintf(
        "SELECT tacd.description AS value
					FROM tagent_custom_data tacd
					INNER JOIN tagent_custom_fields tacf
						ON tacd.id_field = tacf.id_field
							AND tacf.name LIKE '%s'
					WHERE tacd.id_agent = %d",
        $custom_field_name,
        $agent_id
    );
    return db_get_value_sql($sql);
}


/**
 * Unverified documentation.
 *
 * @param integer $id_group         Module group.
 * @param array   $id_agents        Array of agent ids.
 * @param boolean $selection        Show common (false) or all modules (true).
 * @param boolean $return           Return (false) or dump to output (true).
 * @param boolean $index_by_name    Use module name as key.
 * @param boolean $pure_return      Return as retrieved from DB.
 * @param boolean $notStringModules Not string modules.
 *
 * @return array With modules or null if error.
 */
function select_modules_for_agent_group(
    $id_group,
    $id_agents,
    $selection,
    $return=true,
    $index_by_name=false,
    $pure_return=false,
    $notStringModules=false
) {
    global $config;
    $agents = (empty($id_agents)) ? [] : implode(',', $id_agents);

    $filter_agent_group = '';
    $filter_group = '';
    $filter_agent = '';
    $filter_not_string_modules = '';
    $selection_filter = '';
    $sql_conditions_tags = '';
    $sql_tags_inner = '';

    $groups = array_keys(users_get_groups(false, 'AR', false));

    if ($id_group != 0) {
        $filter_group = ' AND tagente_modulo.id_module_group = '.$id_group;
    }

    if ($agents != null) {
        $filter_agent = ' AND tagente.id_agente IN ('.$agents.')';
    }

    if ($notStringModules === true) {
        $filter_not_string_modules = sprintf(
            ' AND (tagente_modulo.id_tipo_modulo <> %d AND
                tagente_modulo.id_tipo_modulo <> %d AND
                tagente_modulo.id_tipo_modulo <> %d AND
                tagente_modulo.id_tipo_modulo <> %d AND
                tagente_modulo.id_tipo_modulo <> %d AND
                tagente_modulo.id_tipo_modulo <> %d)',
            MODULE_TYPE_GENERIC_DATA_STRING,
            MODULE_TYPE_REMOTE_TCP_STRING,
            MODULE_TYPE_REMOTE_SNMP_STRING,
            MODULE_TYPE_ASYNC_STRING,
            MODULE_TYPE_WEB_CONTENT_STRING,
            MODULE_TYPE_REMOTE_CMD_STRING
        );
    }

    if (!users_can_manage_group_all('AR')) {
        $group_string = implode(',', $groups);
        $filter_agent_group = " AND (
			tagente.id_grupo IN ($group_string)
			OR tasg.id_group IN ($group_string)
		)";
    }

    if (!$selection && $agents != null) {
        $number_agents = count($id_agents);
        $selection_filter = "HAVING COUNT(id_agente_modulo) = $number_agents";
    }

    if (tags_has_user_acl_tags(false)) {
        $sql_conditions_tags = tags_get_acl_tags(
            $config['id_user'],
            $groups,
            'AR',
            'module_condition',
            'AND',
            'tagente_modulo',
            true,
            [],
            false
        );
        $sql_tags_inner = 'INNER JOIN ttag_module
			ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo';
    }

    $sql = "SELECT * FROM
		(
			SELECT DISTINCT(tagente_modulo.id_agente_modulo), tagente_modulo.nombre
			FROM tagente_modulo
			$sql_tags_inner
			INNER JOIN tagente
				ON tagente.id_agente = tagente_modulo.id_agente
			LEFT JOIN tagent_secondary_group tasg
				ON tagente.id_agente = tasg.id_agent
			WHERE tagente.disabled = 0
				AND tagente_modulo.disabled = 0
				$filter_agent_group
				$filter_group
				$filter_agent
                $filter_not_string_modules
				$sql_conditions_tags
		) x
		GROUP BY nombre
		$selection_filter";

    $modules = db_get_all_rows_sql($sql);
    if ($modules === false) {
        $modules = [];
    }

    if ($return) {
        echo json_encode($modules);
        return;
    }

    if ($pure_return === true) {
        return $modules;
    }

    $modules_array = [];
    foreach ($modules as $value) {
        if ($index_by_name) {
            $modules_array[io_safe_output($value['nombre'])] = ui_print_truncate_text(
                io_safe_output($value['nombre']),
                'module_medium',
                false,
                true
            );
        } else {
            $modules_array[$value['id_agente_modulo']] = $value['nombre'];
        }
    }

    return $modules_array;
}


function select_agents_for_module_group(
    $module_names,
    $selection,
    $filter,
    $access='AR'
) {
    global $config;

    $default_filter = ['status' => null];

    $filter = array_merge($default_filter, $filter);

    $module_names_condition = '';
    $filter_agent_group = '';
    $selection_filter = '';
    $sql_conditions_tags = '';
    $sql_tags_inner = '';
    $status_filter = '';
    $module_type_filter = '';

    $groups = array_keys(users_get_groups(false, $access, false));

    // Name
    if (!users_can_manage_group_all($access)) {
        $group_string = implode(',', $groups);
        $filter_agent_group = " AND (
			tagente.id_grupo IN ($group_string)
			OR tasg.id_group IN ($group_string)
		)";
    }

    // Name filter
    if ($module_names) {
        $module_names_sql = implode("','", $module_names);
        $module_names_condition = " AND tagente_modulo.nombre IN ('$module_names_sql') ";
    }

    // Common or all modules filter
    if (!$selection) {
        $number_modules = count($module_names);
        $selection_filter = "HAVING COUNT(id_agente) = $number_modules";
    }

    // Status filter
    if ($filter['status'] != null) {
        $status_filter = ' AND '.modules_get_state_condition(
            $filter['status'],
            'tagente_estado'
        );
    }

    // Tags input and ACL conditions
    if (tags_has_user_acl_tags(false) || $filter['tags'] != null) {
        $sql_conditions_tags = tags_get_acl_tags(
            $config['id_user'],
            $groups,
            $access,
            'module_condition',
            'AND',
            'tagente_modulo',
            true,
            $filter['tags'],
            false
        );
        $sql_tags_inner = 'INNER JOIN ttag_module
			ON ttag_module.id_agente_modulo = tagente_modulo.id_agente_modulo';
    }

    $sql = "SELECT * FROM
		(
			SELECT tagente.id_agente, tagente.alias
			FROM tagente
			LEFT JOIN tagent_secondary_group tasg
				ON tagente.id_agente = tasg.id_agent
			INNER JOIN tagente_modulo
				ON tagente.id_agente = tagente_modulo.id_agente
			$sql_tags_inner
			LEFT JOIN tagente_estado
				ON tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
			WHERE tagente.disabled = 0
				AND tagente_modulo.disabled = 0
				$module_names_condition
				$filter_agent_group
				$sql_conditions_tags
				$status_filter
				$module_type_filter
			GROUP BY tagente_modulo.id_agente_modulo
		) x
		GROUP BY id_agente
		$selection_filter";
    $modules = db_get_all_rows_sql($sql);
    if ($modules === false) {
        return [];
    }

    return index_array(db_get_all_rows_sql($sql), 'id_agente', 'alias');
}


/**
 * Returns a random name identifier for an agent.
 *
 * @param string Descriptive name of the agent.
 * @param string Address of the agent.
 *
 * @return string Random identifier name.
 */
function agents_generate_name($alias, $address='')
{
    return hash('sha256', $alias.'|'.$address.'|'.time().'|'.sprintf('%04d', rand(0, 10000)));
}


/**
 * Returns all the groups related to an agent. It includes all secondary groups.
 *
 * @param integer                                 $id_agent
 * @param integer                                 $id_group. By default it will search for it in dtabase
 * @param bool True to use the metaconsole tables
 *
 * @return array with the main and secondary groups
 */
function agents_get_all_groups_agent($id_agent, $group=false, $force_meta=false)
{
    // Cache the agent id groups
    static $cache = [];
    if (isset($cache[$id_agent])) {
        return $cache[$id_agent];
    }

    // Get the group if is not defined
    if ($group === false) {
        $group = agents_get_agent_group($id_agent, $force_meta);
    }

    // If cannot retrieve the group, it means that agent does not exist
    if (!$group) {
        return [];
    }

    enterprise_include_once('include/functions_agents.php');
    $secondary_groups = enterprise_hook('agents_get_secondary_groups', [$id_agent, $force_meta]);

    // Return only an array with the group in open version
    if ($secondary_groups == ENTERPRISE_NOT_HOOK) {
        return [$group];
    }

    // Add a list of groups
    $secondary_groups['plain'][] = $group;
    $cache[$id_agent] = $secondary_groups['plain'];
    return $secondary_groups['plain'];
}


/**
 * @brief Get the total agents with a filter and an access bit
 *
 * @param Array filter agentes array. It is the same that agents_get_agents function
 * @param string ACL bit
 *
 * @return integer Total agents retrieved with the filter
 */
function agents_count_agents_filter($filter=[], $access='AR')
{
    $total_agents = agents_get_agents(
        $filter,
        ['COUNT(DISTINCT id_agente) as total'],
        $access
    );
    return ($total_agents !== false) ? $total_agents[0]['total'] : 0;
}


/**
 * @brief Check if an agent is accessible by the user
 *
 * @param int Id agent
 * @param string ACL access bit
 * @param boolean               $force_meta
 *
 * @return True if user has access, false if user has not permissions and
 *         null if id agent does not exist
 */
function agents_check_access_agent($id_agent, $access='AR', $force_meta=false)
{
    global $config;

    if (users_access_to_agent($id_agent, $access, false, $force_meta)) {
        return true;
    }

    // If agent exist return false
    if (agents_check_agent_exists($id_agent, true, $force_meta)) {
        return false;
    }

    // Return null otherwise
    return null;
}


function agents_get_status_clause($state, $show_not_init=true)
{
    switch ($state) {
        case AGENT_STATUS_CRITICAL:
        return '(ta.critical_count > 0)';

        case AGENT_STATUS_WARNING:
        return '(ta.warning_count > 0 AND ta.critical_count = 0)';

        case AGENT_STATUS_UNKNOWN:
        return '(
				ta.critical_count = 0 AND ta.warning_count = 0 AND ta.unknown_count > 0
			)';

        case AGENT_STATUS_NOT_INIT:
        return $show_not_init ? '(ta.total_count = ta.notinit_count OR ta.total_count = 0)' : '1=0';

        case AGENT_STATUS_NORMAL:
        return '(
				ta.critical_count = 0 AND ta.warning_count = 0
				AND ta.unknown_count = 0 AND ta.normal_count > 0
			)';

        case AGENT_STATUS_ALL:
        default:
        return $show_not_init ? '1=1' : '(ta.total_count <> ta.notinit_count)';
    }

    // If the state is not an expected state, return no condition
    return '1=1';
}


function agents_get_image_status($status)
{
    switch ($status) {
        case AGENT_STATUS_NORMAL:
            $image_status = html_print_image(
                'images/status_sets/default/agent_ok.png',
                true,
                [
                    'title' => __('Agents ok'),
                ]
            );
        break;

        case AGENT_STATUS_CRITICAL:
            $image_status = html_print_image(
                'images/status_sets/default/agent_critical.png',
                true,
                [
                    'title' => __('Agents critical'),
                ]
            );
        break;

        case AGENT_STATUS_WARNING:
            $image_status = html_print_image(
                'images/status_sets/default/agent_warning.png',
                true,
                [
                    'title' => __('Agents warning'),
                ]
            );
        break;

        case AGENT_STATUS_UNKNOWN:
            $image_status = html_print_image(
                'images/status_sets/default/agent_down.png',
                true,
                [
                    'title' => __('Agents unknown'),
                ]
            );
        break;

        case AGENT_STATUS_ALERT_FIRED:
            $image_status = 'alert';
        break;

        case AGENT_STATUS_NOT_INIT:
            $image_status = html_print_image(
                'images/status_sets/default/agent_no_data.png',
                true,
                [
                    'title' => __('Agents not init'),
                ]
            );
        break;

        default:
            $image_status = html_print_image(
                'images/status_sets/default/agent_ok.png',
                true,
                [
                    'title' => __('Agents ok'),
                ]
            );
        break;
    }

    return $image_status;
}


/**
 * Animation GIF to show agent's status.
 *
 * @return string HTML code with heartbeat image.
 */
function agents_get_status_animation($up=true)
{
    global $config;

    $red = 'images/heartbeat_green.gif';
    $green = 'images/heartbeat_green.gif';

    if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
        $red = 'images/heartbeat_green_black.gif';
        $green = 'images/heartbeat_green_black.gif';
    }

    // Gif with black background or white background
    switch ($up) {
        case true:
        default:
        return html_print_image(
            $green,
            true,
            [
                'width'  => '170',
                'height' => '40',
            ]
        );

        case false:
        return html_print_image(
            $red,
            true,
            [
                'width'  => '170',
                'height' => '40',
            ]
        );
    }
}


function agents_get_agent_id_by_alias_regex($alias_regex, $flag='i', $limit=0)
{
    $agents_id = [];
    if (is_metaconsole()) {
        $all_agents = agents_meta_get_agents('AR', '|');
    } else {
        $all_agents = agents_get_group_agents(0, true, 'lower', false, false, true, '|');
    }

    $agent_match = '/'.$alias_regex.'/'.$flag;

    foreach ($all_agents as $agent_id => $agent_alias) {
        $result_agent_match = preg_match($agent_match, $agent_alias);
        if ($result_agent_match) {
            $agents_id[] = $agent_id;
            $i++;
            if ($i === $limit) {
                break;
            }
        }
    }

    return $agents_id;
}


/**
 * Return if an agent is SAP or or an a agent SAP list.
 * If function receive false, you will return all SAP agents,
 * but if you receive an id agent, check if it is a sap agent
 * and return true or false.
 *
 * @param  integer $id_agent
 * @return boolean
 */
function agents_get_sap_agents($id_agent)
{
    global $config;

    // Available modules.
    // If you add more modules, please update SAP.pm.
    $sap_modules = [
        0   => 'SAP connection',
        160 => __('SAP Login OK'),
        109 => __('SAP Dumps'),
        111 => __('SAP lock entry list'),
        113 => __('SAP canceled Jobs'),
        121 => __('SAP Batch inputs erroneous'),
        104 => __('SAP IDOC erroneous'),
        105 => __('SAP IDOC OK'),
        150 => __('SAP WP without active restart'),
        151 => __('SAP WP stopped'),
        102 => __('Average time of SAPGUI response'),
        180 => __('Dialog response time'),
        103 => __('Dialog Logged users'),
        192 => __('TRFC in error'),
        195 => __('QRFC in error SMQ2'),
        116 => __('Number of Update WPs in error'),
    ];

    $array_agents = [];
    foreach ($sap_modules as $module => $key) {
        $sql = sprintf(
            'SELECT ta.id_agente,ta.alias, ta.id_grupo
            FROM tagente ta
            INNER JOIN tagente_modulo tam 
            ON tam.id_agente = ta.id_agente 
            WHERE tam.nombre  
            LIKE "%s" 
            GROUP BY ta.id_agente',
            io_safe_input($key)
        );

        // ACL groups.
        $agent_groups = array_keys(users_get_groups($config['id_user']));
        if (!empty($agent_groups)) {
            $sql .= sprintf(
                ' HAVING ta.id_grupo IN (%s)',
                implode(',', $agent_groups)
            );
        }

        $new_ones = db_get_all_rows_sql($sql);

        if ($new_ones === false) {
            continue;
        }

        $array_agents = array_merge(
            $array_agents,
            $new_ones
        );
    }

    $indexed_agents = index_array($array_agents, 'id_agente', false);

    if ($id_agent === false) {
        return $indexed_agents;
    }

    foreach ($indexed_agents as $agent => $key) {
        if ($agent === $id_agent) {
            return true;
        }
    }

    return false;
}


/**
 * Return time at which last status change of a module occured.
 *
 * @param  integer $id_agent.
 * @return string timestamp.
 */
function agents_get_last_status_change($id_agent)
{
    $sql = sprintf(
        'SELECT *
        FROM tagente_estado
        WHERE id_agente = %d
        ORDER BY last_status_change DESC',
        $id_agent
    );

    $row = db_get_row_sql($sql);

    return $row['last_status_change'];
}


/**
 * Checks if group allow more agents due itself limitation.
 *
 * @param integer $id_group      Id of the group.
 * @param boolean $generateEvent If true and the check fails, will generate an event.
 * @param string  $action        Action for perform (only if generateEvent is true).
 *
 * @return boolean True if allow more agents.
 */
function group_allow_more_agents(
    int $id_group,
    bool $generateEvent=false,
    string $action='create'
):bool {
    global $config;

    $groupMaxAgents   = (int) db_get_value('max_agents', 'tgrupo', sprintf('id_grupo = %d', $id_group));
    $groupCountAgents = (int) db_get_num_rows(sprintf('SELECT nombre FROM tagente WHERE id_grupo = "%s"', $id_group));

    // If `max_agents` is not defined or the count of agents in the group is below of max agents allowed.
    $output = ($groupMaxAgents === 0 || $groupCountAgents < $groupMaxAgents);

    if ($output === false && $generateEvent === true) {
        // Get the group name.
        $groupName = db_get_value(
            'nombre',
            'tgrupo',
            'id_grupo',
            $id_group
        );
        // New event.
        $evt = new Event;
        // Set parameters.
        $evt->evento(
            sprintf(
                'Agent cannot be %sd due to the maximum agent limit for group %s',
                $action,
                $groupName
            )
        );
        $evt->id_grupo($id_group);
        $evt->id_agente(0);
        $evt->id_agentmodule(0);
        $evt->id_usuario($config['id_user']);
        $evt->estado(EVENT_STATUS_NEW);
        $evt->event_type(EVENTS_SYSTEM);
        $evt->criticity(EVENT_CRIT_WARNING);
        $evt->timestamp(date('Y-m-d H:i:s'));
        $evt->utimestamp(time());
        $evt->data(0);
        $evt->source('agent_creation');
        // Any fields are only available in meta.
        if (is_metaconsole() === true) {
            $evt->id_source_event(0);
        }

        // Save the event.
        $evt->save();
    }

    return $output;
}


/**
 * Return the list of agents for a planned downtime
 *
 * @param integer $id_downtime   Id of planned downtime.
 * @param string  $filter_cond   String-based filters.
 * @param string  $id_groups_str String-based list of id group, separated with commas.
 *
 * @return array
 */
function get_planned_downtime_agents_list($id_downtime, $filter_cond, $id_groups_str):array
{
    $agents = [];

    $sql = sprintf(
        'SELECT tagente.id_agente, tagente.alias
                    FROM tagente
                    WHERE tagente.id_agente NOT IN (
                            SELECT tagente.id_agente
                            FROM tagente, tplanned_downtime_agents
                            WHERE tplanned_downtime_agents.id_agent = tagente.id_agente
                                AND tplanned_downtime_agents.id_downtime = %d
                        ) AND disabled = 0 %s
                        AND tagente.id_grupo IN (%s)
                    ORDER BY tagente.nombre',
        $id_downtime,
        $filter_cond,
        $id_groups_str
    );

    $agents = db_get_all_rows_sql($sql);

    if (empty($agents)) {
        $agents = [];
    }

    $agent_ids = extract_column($agents, 'id_agente');
    $agent_names = extract_column($agents, 'alias');

    $agents = array_combine($agent_ids, $agent_names);

    if ($agents === false) {
        $agents = [];
    }

    return $agents;
}


/**
 * Agent Module status and data
 *
 * @param integer $id_group Group
 * @param array   $agents   Agents filter.
 * @param array   $modules  Modules filter.
 *
 * @return array Result.
 */
function get_status_data_agent_modules($id_group, $agents=[], $modules=[])
{
    $slq_filter_group = '';
    if (empty($id_group) === false) {
        $slq_filter_group = sprintf(
            ' AND tagente.id_grupo = %d',
            $id_group
        );
    }

    $slq_filter_agent = '';
    if (empty($agents) === false) {
        $slq_filter_agent = sprintf(
            ' AND tagente_modulo.id_agente IN (%s)',
            implode(',', $agents)
        );
    }

    $slq_filter_module = '';
    if (empty($modules) === false) {
        $slq_filter_module = sprintf(
            ' AND tagente_modulo.id_agente_modulo IN (%s)',
            implode(',', $modules)
        );
    }

    $sql = sprintf(
        'SELECT tagente_modulo.id_agente_modulo as id_agent_module,
            tagente_modulo.nombre as name_module,
            tagente_modulo.unit as unit_module,
            tagente_modulo.id_agente as id_agent,
            tagente_estado.datos as data_module,
            tagente_estado.timestamp as data_time_module,
            tagente_estado.estado as status_module,
            tagente.alias as name_agent,
            tagente.id_grupo as id_group,
            tgrupo.nombre as name_group
        FROM tagente_modulo
        INNER JOIN tagente_estado
            ON tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
        INNER JOIN tagente
            ON tagente_modulo.id_agente = tagente.id_agente
        LEFT JOIN tagent_secondary_group
            ON tagente.id_agente = tagent_secondary_group.id_agent
        INNER JOIN tgrupo
            ON tagente.id_grupo = tgrupo.id_grupo
        WHERE 1=1
            %s
            %s
            %s
            ',
        $slq_filter_group,
        $slq_filter_agent,
        $slq_filter_module
    );

    $res = db_get_all_rows_sql($sql);

    if ($res === false) {
        $res = [];
    }

    return $res;
}


function agents_get_offspring(int $id_agent)
{
    $return = [];
    // Get parent.
    $agents = db_get_all_rows_filter(
        'tagente',
        [
            'id_parent' => $id_agent,
            'disabled'  => 0,
        ],
        'id_agente'
    );

    if ($agents !== false) {
        foreach ($agents as $agent) {
            if ((int) $agent['id_agente'] !== 0) {
                $return += agents_get_offspring((int) $agent['id_agente']);
            }
        }
    }

    $return += [$id_agent => 0];

    return $return;
}


function agents_get_starmap(int $id_agent, float $width=0, float $height=0)
{
    ui_require_css_file('heatmap');

    $all_modules = agents_get_modules($id_agent, 'id_agente_modulo', ['disabled' => 0]);
    if (empty($all_modules)) {
        return null;
    }

    $total_modules = count($all_modules);

    if ($width !== 0 && $height !== 0) {
        $measuresProvided = false;
        $width = 200;
        $height = 50;
    } else {
        $measuresProvided = true;
    }

    // Best square.
    $high = (float) max($width, $height);
    $low = 0.0;

    while (abs($high - $low) > 0.000001) {
        $mid = (($high + $low) / 2.0);
        $midval = (floor($width / $mid) * floor($height / $mid));
        if ($midval >= $total_modules) {
            $low = $mid;
        } else {
            $high = $mid;
        }
    }

    $square_length = min(($width / floor($width / $low)), ($height / floor($height / $low)));

    // $measureSymbol = ($measuresProvided === true) ? '' : '%';
    // Print starmap.
    $html = sprintf(
        '<svg id="svg_%s" style="width: %spx; height: %spx;">',
        $id_agent,
        $width,
        $height
    );

    $html .= '<g>';
    $row = 0;
    $column = 0;
    $x = 0;
    $y = 0;
    $cont = 1;
    foreach ($all_modules as $key => $value) {
        // Colour by status.
        $status = modules_get_agentmodule_status($key);
        switch ($status) {
            case 0:
            case 300:
                $status = 'normal';
            break;

            case 1:
            case 100:
                $status = 'critical';
            break;

            case 2:
            case 200:
                $status = 'warning';
            break;

            case 3:
                $status = 'unknown';
            break;

            case 4:
            case 5:
                $status = 'notinit';
            break;
        }

        $html .= sprintf(
            '<rect id="%s" x="%s" y="%s" row="%s" col="%s" width="%s" height="%s" class="%s_%s"></rect>',
            'rect_'.$cont,
            $x,
            $y,
            $row,
            $column,
            // $square_length.$measureSymbol,
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
            const total_modules = '<?php echo $total_modules; ?>';

            function getRandomInteger(min, max) {
                return Math.floor(Math.random() * max) + min;
            }

            function oneSquare(solid, time) {
                var randomPoint = getRandomInteger(1, total_modules);
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
            while (cont < Math.ceil(total_modules / 3)) {
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
 * Defines a hash for agent name.
 *
 * @param string $alias         Alias.
 * @param string $nombre_agente Agent name.
 *
 * @return string.
 */
function hash_agent_name(string $alias, string $nombre_agente)
{
    return hash('sha256', $alias.'|'.$nombre_agente.'|'.time().'|'.sprintf('%04d', rand(0, 10000)));
}