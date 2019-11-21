<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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
 * @subpackage Modules
 */

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_tags.php';


function modules_is_not_init($id_agent_module)
{
    $row = db_get_row('tagente_estado', 'id_agente_modulo', $id_agent_module);

    if (!$row) {
        return true;
    }

    if ($row['estado'] == AGENT_MODULE_STATUS_NO_DATA || $row['estado'] == AGENT_MODULE_STATUS_NOT_INIT) {
        return true;
    } else {
        return false;
    }
}


function modules_is_disable_agent($id_agent_module)
{
    $sql = '
		SELECT disabled
		FROM tagente
		WHERE id_agente IN (
			SELECT id_agente
			FROM tagente_modulo
			WHERE id_agente_modulo = '.(int) $id_agent_module.')';

    $disabled = db_get_value_sql($sql);

    return (bool) $disabled;
}


function modules_is_disable_type_event($id_agent_module=false, $type_event=false)
{
    if ($id_agent_module === false) {
        switch ($type_event) {
            case EVENTS_GOING_UNKNOWN:
            return false;

                break;
            case EVENTS_UNKNOWN:
            return false;

                break;
            case EVENTS_ALERT_FIRED:
            return false;

                break;
            case EVENTS_ALERT_RECOVERED:
            return false;

                break;
            case EVENTS_ALERT_CEASED:
            return false;

                break;
            case EVENTS_ALERT_MANUAL_VALIDATION:
            return false;

                break;
            case EVENTS_RECON_HOST_DETECTED:
            return false;

                break;
            case EVENTS_SYSTEM:
            return false;

                break;
            case EVENTS_ERROR:
            return false;

                break;
            case EVENTS_NEW_AGENT:
            return false;

                break;
            case EVENTS_GOING_UP_WARNING:
            return false;

                break;
            case EVENTS_GOING_UP_CRITICAL:
            return false;

                break;
            case EVENTS_GOING_DOWN_WARNING:
            return false;

                break;
            case EVENTS_GOING_DOWN_NORMAL:
            return false;

                break;
            case EVENTS_GOING_DOWN_CRITICAL:
            return false;

                break;
            case EVENTS_GOING_UP_NORMAL:
            return false;

                break;
            case EVENTS_CONFIGURATION_CHANGE:
            return false;

                break;
        }
    }

    $disabled_types_event = json_decode(
        db_get_value('disabled_types_event', 'tagente_modulo', 'id_agente_modulo', $id_agent_module),
        true
    );

    if (isset($disabled_types_event[$type_event])) {
        if ($disabled_types_event[$type_event]) {
            return true;
        } else {
            return false;
        }
    }

    return false;
}


/**
 * Copy a module defined in an agent to other agent.
 *
 * This function avoid duplicated by comparing module names.
 *
 * @param int Source agent module id.
 * @param int Destiny agent id.
 * @param string Forced name to the new module.
 *
 * @return New agent module id on success. Existing module id if it already exists.
 * False on error.
 */
function modules_copy_agent_module_to_agent($id_agent_module, $id_destiny_agent, $forced_name=false)
{
    global $config;

    $module = modules_get_agentmodule($id_agent_module);
    if ($module === false) {
        return false;
    }

    if ($forced_name !== false) {
        $module['nombre'] = $forced_name;
    }

    $modules = agents_get_modules(
        $id_destiny_agent,
        false,
        [
            'nombre'   => $module['nombre'],
            'disabled' => false,
        ]
    );

    // The module already exist in the target
    if (! empty($modules)) {
        return array_pop(array_keys($modules));
    }

    $modulesDisabled = agents_get_modules(
        $id_destiny_agent,
        false,
        [
            'nombre'   => $module['nombre'],
            'disabled' => true,
        ]
    );

    // If the module exist but disabled, we enable it
    if (!empty($modulesDisabled)) {
        // the foreach have only one loop but extract the array index, and it's id_agente_modulo
        foreach ($modulesDisabled as $id => $garbage) {
            $id_module = $id;
            modules_change_disabled($id_module, 0);
        }

        $id_new_module = $id_module;
    } else {
        // PHP copy arrays on assignment
        $new_module = $module;

        // Rewrite different values
        $new_module['ip_target'] = agents_get_address($id_destiny_agent);
        $new_module['policy_linked'] = 0;
        $new_module['id_policy_module'] = 0;

        // Unset numeric indexes or SQL would fail
        $len = (count($new_module) / 2);
        for ($i = 0; $i < $len; $i++) {
            unset($new_module[$i]);
        }

        // Unset original agent module id
        unset($new_module['id_agente_modulo']);
        unset($new_module['id_agente']);

        $id_new_module = modules_create_agent_module(
            $id_destiny_agent,
            $new_module['nombre'],
            $new_module
        );

        if ($id_new_module === false) {
            return false;
        }
    }

    // If the module is synthetic we duplicate the operations too
    if ($module['id_modulo'] == 5) {
        $synth_ops = db_get_all_rows_field_filter(
            'tmodule_synth',
            'id_agent_module_target',
            $module['id_agente_modulo']
        );

        if ($synth_ops === false) {
            $synth_ops = [];
        }

        foreach ($synth_ops as $synth_op) {
            unset($synth_op['id']);
            $synth_op['id_agent_module_target'] = $id_new_module;
            switch ($config['dbtype']) {
                case 'mysql':
                case 'postgresql':
                    db_process_sql_insert(
                        'tmodule_synth',
                        $synth_op
                    );
                break;

                case 'oracle':
                    db_process_sql_insert(
                        'tmodule_synth',
                        $synth_op,
                        false
                    );
                break;
            }
        }
    }

    // Copy module tags
    $source_tags = tags_get_module_tags($id_agent_module);

    if ($source_tags == false) {
        $source_tags = [];
    }

    tags_insert_module_tag($id_new_module, $source_tags);

    // Added the config data if necesary
    enterprise_include_once('include/functions_config_agents.php');

    $id_agente = modules_get_agentmodule_agent($id_agent_module);

    if ($module['id_modulo'] == MODULE_DATA) {
        if (enterprise_installed()) {
            if (enterprise_hook('config_agents_has_remote_configuration', [$id_agente])) {
                $result = enterprise_hook(
                    'config_agents_copy_agent_module_to_agent',
                    [
                        $id_agent_module,
                        $id_new_module,
                    ]
                );
                if ($result === false) {
                    return false;
                }
            }
        }
    }

    return $id_new_module;
}


/**
 * Enable/Disable a module
 *
 * @param mixed Agent module id to be disabled. Accepts an array with ids.
 * @param integer new value for the field disabled. 0 to enable, 1 to disable
 *
 * @return True if the module was disabled. False if not.
 */
function modules_change_disabled($id_agent_module, $new_value=1)
{
    $id_agent_module = (array) $id_agent_module;

    $id_agent_module_changed = [];

    foreach ($id_agent_module as $id_module) {
        // If the module is already disabled/enabled ignore
        $current_disabled = db_get_value(
            'disabled',
            'tagente_modulo',
            'id_agente_modulo',
            $id_module
        );
        if ($current_disabled == $new_value) {
            continue;
        }

        // Validate alerts for disabled modules.
        if ($new_value == 1) {
            alerts_validate_alert_module($id_module);
        }

        $id_agent_changed[] = modules_get_agentmodule_agent($id_module);
        $id_agent_module_changed[] = $id_module;
    }

    if (empty($id_agent_module_changed)) {
        return NOERR;
    } else {
        $result = db_process_sql_update(
            'tagente_modulo',
            ['disabled' => (int) $new_value],
            ['id_agente_modulo' => $id_agent_module_changed]
        );
    }

    if ($result) {
        // Change the agent flag to update modules count
        db_process_sql_update(
            'tagente',
            ['update_module_count' => 1],
            ['id_agente' => $id_agent_changed]
        );

        return NOERR;
    } else {
        return ERR_GENERIC;
    }
}


/**
 * Deletes a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 *
 * @return True if the module was deleted. False if not.
 */
function modules_delete_agent_module($id_agent_module)
{
    if (empty($id_agent_module)) {
        return false;
    }

    if (is_array($id_agent_module)) {
        $id_agents = db_get_all_rows_sql(
            sprintf(
                'SELECT id_agente
				FROM tagente_modulo
				WHERE id_agente_modulo IN (%s)
				GROUP BY id_agente',
                implode(',', $id_agent_module)
            )
        );

        foreach ($id_agents as $k => $v) {
            $id_agents[$k] = $v['id_agente'];
        }

        // Update update flags to server side
        db_process_sql(
            sprintf(
                'UPDATE tagente
			SET update_module_count=1, update_alert_count=1
			WHERE id_agente IN (%s)',
                implode(',', $id_agents)
            )
        );
    } else {
        // Read module data
        $id_agent = modules_get_agentmodule_agent($id_agent_module);

        // Update update flags to server side
        db_process_sql(
            sprintf(
                'UPDATE tagente
			SET update_module_count=1, update_alert_count=1
			WHERE id_agente = %s',
                $id_agent
            )
        );
    }

    $where = ['id_agent_module' => $id_agent_module];

    enterprise_include_once('include/functions_agents.php');
    $enterprise_include = enterprise_include_once('include/functions_config_agents.php');

    if ($enterprise_include !== ENTERPRISE_NOT_HOOK) {
        if (is_array($id_agent_module)) {
            foreach ($id_agent_module as $id_agent_module_item) {
                config_agents_delete_module_in_conf(
                    modules_get_agentmodule_agent($id_agent_module_item),
                    modules_get_agentmodule_name($id_agent_module_item)
                );
            }
        } else {
            config_agents_delete_module_in_conf(
                modules_get_agentmodule_agent($id_agent_module),
                modules_get_agentmodule_name($id_agent_module)
            );
        }
    }

    alerts_delete_alert_agent_module(0, $where);

    db_process_sql_delete('tgraph_source', $where);
    db_process_sql_delete('treport_content', $where);
    db_process_sql_delete(
        'tevento',
        ['id_agentmodule' => $id_agent_module]
    );
    $where = ['id_agente_modulo' => $id_agent_module];
    db_process_sql_delete('tlayout_data', $where);
    db_process_sql_delete('tagente_estado', $where);
    db_process_sql_update(
        'tagente_modulo',
        [
            'nombre'         => 'delete_pending',
            'delete_pending' => 1,
            'disabled'       => 1,
        ],
        $where
    );
    db_process_sql_delete('ttag_module', $where);

    return true;
}


/**
 * Updates a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 * @param array Values to update.
 * @param mixed Tag's module array or false.
 *
 * @return True if the module was updated. False if not.
 */
function modules_update_agent_module(
    $id,
    $values,
    $onlyNoDeletePending=false,
    $tags=false
) {
    $update_tags = false;
    $return_tag = true;
    if ($tags !== false) {
        $update_tags = true;
        $return_tag = tags_update_module_tag($id, $tags, false, false);
    }

    if ($return_tag === false) {
        return ERR_DB;
    }

    if (!is_array($values) || empty($values)) {
        if ($update_tags) {
            return true;
        } else {
            return ERR_GENERIC;
        }
    }

    if (isset($values['nombre'])) {
        if (empty($values['nombre'])) {
            return ERR_INCOMPLETE;
        }

        $id_agent = modules_get_agentmodule_agent($id);

        $exists = (bool) db_get_value_filter(
            'id_agente_modulo',
            'tagente_modulo',
            [
                'nombre'           => $values['nombre'],
                'id_agente'        => $id_agent,
                'id_agente_modulo' => "<>$id",
            ]
        );

        if ($exists) {
            return ERR_EXIST;
        }
    }

    if (isset($values['ip_target'])) {
        if ($values['ip_target'] == 'force_pri') {
            $sql_agent = 'SELECT id_agente FROM tagente_modulo WHERE id_agente_modulo='.$id;
            $id_agente = mysql_db_process_sql($sql_agent);
            $values['ip_target'] = agents_get_address($id_agente);
        } else if ($values['ip_target'] == 'custom') {
            $values['ip_target'] = $values['custom_ip_target'];
        }
    }

    unset($values['custom_ip_target']);

    $where = [];
    $where['id_agente_modulo'] = $id;
    if ($onlyNoDeletePending) {
        $where['delete_pending'] = 0;
    }

    // Disable action requires a special function
    if (isset($values['disabled'])) {
        $result_disable = modules_change_disabled($id, $values['disabled']);

        unset($values['disabled']);
    } else {
        $result_disable = true;
    }

    $result = @db_process_sql_update('tagente_modulo', $values, $where);

    if ($result == false) {
        if ($result_disable === ERR_GENERIC) {
            return ERR_DB;
        } else {
            return true;
        }
    } else {
        if (ERR_GENERIC === $result_disable) {
            return ERR_DB;
        } else {
            return true;
        }
    }
}


/**
 * Creates a module in an agent.
 *
 * @param integer $id_agent   Agent id.
 * @param integer $name       Module name id.
 * @param array   $values     Extra values for the module.
 * @param boolean $disableACL Disable the ACL checking, for default false.
 * @param mixed   $tags       Array with tag's ids or false.
 *
 * @return New module id if the module was created. False if not.
 */
function modules_create_agent_module(
    $id_agent,
    $name,
    $values=false,
    $disableACL=false,
    $tags=false
) {
    global $config;

    if (!$disableACL) {
        if (!users_is_admin() && (empty($id_agent)
            || !users_access_to_agent($id_agent, 'AW'))
        ) {
            return false;
        }
    }

    if (empty($name)) {
        return ERR_INCOMPLETE;
    }

    // Check for non valid characters in module name.
    if (mb_ereg_match('[\xc2\xa1\xc2\xbf\xc3\xb7\xc2\xba\xc2\xaa]', io_safe_output($name)) !== false) {
        return ERR_GENERIC;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['nombre'] = $name;
    $values['id_agente'] = (int) $id_agent;

    $exists = (bool) db_get_value_filter(
        'id_agente_modulo',
        'tagente_modulo',
        [
            'nombre'    => $name,
            'id_agente' => (int) $id_agent,
        ]
    );

    if ($exists) {
        return ERR_EXIST;
    }

    // Encrypt passwords.
    if (isset($values['plugin_pass'])) {
        $values['plugin_pass'] = io_input_password($values['plugin_pass']);
    }

    // Encrypt SNMPv3 passwords.
    if (isset($values['id_tipo_modulo']) && ($values['id_tipo_modulo'] >= 15
        && $values['id_tipo_modulo'] <= 18)
        && isset($values['tcp_send']) && ($values['tcp_send'] == 3)
        && isset($values['custom_string_2'])
    ) {
        $values['custom_string_2'] = io_input_password(
            $values['custom_string_2']
        );
    }

    $id_agent_module = db_process_sql_insert('tagente_modulo', $values);

    if ($id_agent_module === false) {
        return ERR_DB;
    }

    $return_tag = true;
    if (($tags !== false) || (empty($tags))) {
        $return_tag = tags_insert_module_tag($id_agent_module, $tags);
    }

    if ($return_tag === false) {
        db_process_sql_delete(
            'tagente_modulo',
            ['id_agente_modulo' => $id_agent_module]
        );

        return ERR_DB;
    }

    if (isset($values['id_tipo_modulo'])
        && ($values['id_tipo_modulo'] == 21
        || $values['id_tipo_modulo'] == 22
        || $values['id_tipo_modulo'] == 23)
    ) {
        // Async modules start in normal status.
        $status = AGENT_MODULE_STATUS_NORMAL;
    } else {
        // Sync modules start in unknown status.
        $status = AGENT_MODULE_STATUS_NO_DATA;
    }

    // Condition for cron modules. Don't touch.
    $time = 0;
    if (empty($values['interval']) === false) {
        $time = (time() - (int) $values['interval']);
    }

    $result = db_process_sql_insert(
        'tagente_estado',
        [
            'id_agente_modulo'  => $id_agent_module,
            'datos'             => '',
            'timestamp'         => '01-01-1970 00:00:00',
            'estado'            => $status,
            'known_status'      => $status,
            'id_agente'         => (int) $id_agent,
            'utimestamp'        => $time,
            'status_changes'    => 0,
            'last_status'       => $status,
            'last_known_status' => $status,
            'current_interval'  => (int) $values['interval'],
        ]
    );

    if ($result === false) {
        db_process_sql_delete(
            'tagente_modulo',
            ['id_agente_modulo' => $id_agent_module]
        );

        return ERR_DB;
    }

    // Update module status count if the module is not created disabled.
    if (!isset($values['disabled']) || $values['disabled'] == 0) {
        if ($status == 0) {
            db_process_sql(
                'UPDATE tagente
                SET total_count=total_count+1, normal_count=normal_count+1
                WHERE id_agente='.(int) $id_agent
            );
        } else {
            db_process_sql(
                'UPDATE tagente
                SET total_count=total_count+1, notinit_count=notinit_count+1
                WHERE id_agente='.(int) $id_agent
            );
        }
    }

    return $id_agent_module;
}


/**
 * Gets all the agents that have a module with a name given.
 *
 * @param string Module name.
 * @param int Group id of the agents. False will be any group.
 * @param array Extra filter.
 * @param mixed Fields to be returned. All agents field by default
 * @param bool Flag to search agents in child groups.
 *
 * @return array All the agents which have a module with the name given.
 */
function modules_get_agents_with_module_name($module_name, $id_group, $filter=false, $fields='tagente.*', $childGroups=false)
{
    if (empty($module_name)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter[] = 'tagente_modulo.id_agente = tagente.id_agente';
    $filter['tagente_modulo.nombre'] = $module_name;
    $filter['tagente.id_agente'] = array_keys(agents_get_group_agents($id_group, false, 'none', false, $childGroups));

    return db_get_all_rows_filter(
        'tagente, tagente_modulo',
        $filter,
        $fields
    );
}


//
// This are functions to format the data
//


/**
 * Formats time data to tiemstamp format.
 *
 * @param numeric Numeric data.
 *
 * @return string HTML Code with data time with timestamp format.
 */
function modules_format_time($ts)
{
    return ui_print_timestamp($ts, true, ['prominent' => 'comparation']);
}


/**
 * Formats module data.
 *
 * @param variant Numeric or string data.
 *
 * @return variant Module data formated.
 */
function modules_format_data($data)
{
    if (is_numeric($data)) {
        $data = format_numeric($data, 2);
    } else {
        $data = io_safe_input($data);
    }

    return $data;
}


/**
 * Formats verbatim to string data.
 *
 * @param string String data.
 *
 * @return string HTML string data with verbatim format.
 */
function modules_format_verbatim($data)
{
    // We need to replace \n by <br> to create a "similar" output to
    // information recolected in logs.
    $data2 = preg_replace("/\\n/", '<br>', $data);
    return "<span style='font-size:10px;'>".$data2.'</span>';
}


/**
 * Formats data time to timestamp format.
 *
 * @param int Data time.
 *
 * @return integer Data time with timestamp format.
 */
function modules_format_timestamp($ts)
{
    global $config;

    // This returns data with absolute user-defined timestamp format
    // and numeric by data managed with 2 decimals, and not using Graph format
    // (replacing 1000 by K and 1000000 by G, like version 2.x
    return date($config['date_format'], $ts);
}


/**
 * Writes HTML code to perform delete module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete($id)
{
    global $period, $module_id, $config, $group;

    $txt = '';

    if (check_acl($config['id_user'], $group, 'AW') == 1) {
        $txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete='.$id.'">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
    }

    return $txt;
}


/**
 * Writes HTML code to perform delete string module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete_string($id)
{
    global $period, $module_id, $config, $group;

    $txt = '';

    if (check_acl($config['id_user'], $group, 'AW') == 1) {
        $txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_string='.$id.'">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
    }

    return $txt;
}


/**
 * Writes HTML code to perform delete log4x module action for a particular module.
 *
 * @param int Id of the module.
 *
 * @return string HTML code to perform delete action.
 */
function modules_format_delete_log4x($id)
{
    global $period, $module_id, $config, $group;

    $txt = '';

    if (check_acl($config['id_user'], $group, 'AW') == 1) {
        $txt = '<a href="index.php?sec=estado&sec2=operation/agentes/datos_agente&period='.$period.'&id='.$module_id.'&delete_log4x='.$id.'">'.html_print_image('images/cross.png', true, ['border' => '0']).'</a>';
    }

    return $txt;
}


/**
 * Get a single module information.
 *
 * @param int agentmodule id to get.
 *
 * @return array An array with module information
 */
function modules_get_agentmodule($id_agentmodule)
{
    return db_get_row('tagente_modulo', 'id_agente_modulo', (int) $id_agentmodule);
}


function modules_get_table_data($id_agent_module)
{
    $id_type = db_get_value(
        'id_tipo_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        $id_agent_module
    );

    $name_type = db_get_value('nombre', 'ttipo_modulo', 'id_tipo', $id_type);

    $chunks = explode('_', $name_type);
    $subtype = end($chunks);

    switch ($subtype) {
        case 'data':
        case 'proc':
        case 'icmp':
        case 'tcp':
        case 'snmp':
        case 'inc':
            // Numeric
        return 'tagente_datos';

            break;
        case 'log4x':
            // Log4x
        return 'tagente_datos_log4x';

            break;
        case 'string':
            // String
        return 'tagente_datos_string';

            break;
        default:
        return null;
            break;
    }
}


function modules_get_raw_data($id_agent_module, $date_init, $date_end)
{
    $table = modules_get_table_data($id_agent_module);

    $datelimit = ($date_init - $date_end);
    $search_in_history_db = db_search_in_history_db($datelimit);

    $data = db_get_all_rows_sql(
        '
		SELECT *
		FROM '.$table.'
		WHERE id_agente_modulo = '.$id_agent_module.'
			AND utimestamp >= '.$date_init.'
			AND utimestamp <= '.$date_end,
        $search_in_history_db
    );

    return $data;
}


function modules_get_agent_groups($id_agent_module)
{
    $return = false;

    $id_agent = modules_get_agentmodule_agent(
        $id_agent_module
    );

    if (!empty($id_agent)) {
        $return = agents_get_all_groups_agent($id_agent);
    }

    return $return;
}


/**
 * Check the module exists in the DB.
 *
 * @param integer $id_agentmodule The agent id.
 * @param boolean $show_disabled  Show the agent found althought it is disabled. By default false.
 *
 * @return boolean The result to check if the agent is in the DB.
 */
function modules_check_agentmodule_exists($id_agentmodule, $show_disabled=true)
{
    $module = db_get_row_filter(
        'tagente_modulo',
        [
            'id_agente_modulo' => $id_agentmodule,
            'disabled'         => !$show_disabled,
        ]
    );

    if (!empty($module)) {
        if ($module['delete_pending']) {
            return false;
        } else {
            return true;
        }
    } else {
        return false;
    }
}


/**
 * Get a id of module from his name and the agent id
 *
 * @param string agentmodule name to get.
 * @param int agent id.
 *
 * @return integer the agentmodule id
 */
function modules_get_agentmodule_id($agentmodule_name, $agent_id)
{
    return db_get_row_filter(
        'tagente_modulo',
        [
            'nombre'         => $agentmodule_name,
            'id_agente'      => $agent_id,
            'delete_pending' => 0,
        ]
    );
}


/**
 * Get a if a module is init.
 *
 * @param integer $agentmodule Id to get.
 * @param boolean $metaconsole Flag to extract the data for metaconsole, by default false.
 * @param integer $id_server   Id of children console.
 *
 * @return boolean true if is init and false if is not init
 */
function modules_get_agentmodule_is_init($id_agentmodule, $metaconsole=false, $id_server=null)
{
    if ($metaconsole) {
        $server = db_get_row('tmetaconsole_setup', 'id', $id_server);

        if (metaconsole_connect($server) == NOERR) {
            $result = db_get_row_filter(
                'tagente_estado',
                ['id_agente_modulo' => $id_agentmodule],
                'utimestamp'
            );
        }

        metaconsole_restore_db();
    } else {
        $result = db_get_row_filter(
            'tagente_estado',
            ['id_agente_modulo' => $id_agentmodule],
            'utimestamp'
        );
    }

    return (bool) $result['utimestamp'];
}


/**
 * Get the number of all agent modules in the database
 *
 * @param mixed Array of integers with agent(s) id or a single agent id. Default
 * value will select all.
 *
 * @return integer The number of agent modules
 */
function modules_get_agent_modules_count($id_agent=0)
{
    // Make sure we're all int's and filter out bad stuff
    $id_agent = safe_int($id_agent, 1);

    if (empty($id_agent)) {
        // If the array proved empty or the agent is less than 1 (eg. -1)
        $filter = '';
    } else {
        $filter = sprintf(' WHERE id_agente IN (%s)', implode(',', (array) $id_agent));
    }

    return (int) db_get_sql(
        'SELECT COUNT(*)
		FROM tagente_modulo'.$filter
    );
}


/**
 * Get the name of a module type
 *
 * @param integer $id_type Type id
 *
 * @return string The name of the given type.
 */
function modules_get_type_name($id_type)
{
    return (string) db_get_value(
        'nombre',
        'ttipo_modulo',
        'id_tipo',
        (int) $id_type
    );
}


/**
 * Get the id of a module type
 *
 * @param integer $id_type Type id
 *
 * @return string The name of the given type.
 */
function modules_get_type_id($name_type)
{
    return (int) db_get_value(
        'id_tipo',
        'ttipo_modulo',
        'nombre',
        $name_type
    );
}


/**
 * Know if a module type is a string or not
 *
 * @param integer $id_type Type id
 *
 * @return boolean true if string. false if not
 */
function modules_is_string_type($id_type)
{
    $type_name = modules_get_type_name($id_type);

    return (bool) preg_match('/_string$/', $type_name);
}


function modules_is_string($id_agentmodule)
{
    $id_type = db_get_value(
        'id_tipo_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        (int) $id_agentmodule
    );

    return modules_is_string_type($id_type);
}


/**
 * Know if a module type is a boolean or not
 *
 * @param integer $id_type Type id
 *
 * @return boolean true if boolean. false if not
 */
function modules_is_boolean_type($id_type)
{
    $type_name = modules_get_type_name($id_type);

    if ($type_name === 'keep_alive') {
        return true;
    }

    return (bool) preg_match('/_proc$/', $type_name);
}


function modules_is_boolean($id_agentmodule)
{
    $id_type = db_get_value(
        'id_tipo_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        (int) $id_agentmodule
    );

    return modules_is_boolean_type($id_type);
}


/**
 * Get the icon of a module type
 *
 * @param integer $id_type Type id
 *
 * @return string The name of the icon.
 */
function modules_get_type_icon($id_type)
{
    return (string) db_get_value(
        'icon',
        'ttipo_modulo',
        'id_tipo',
        (int) $id_type
    );
}


/**
 * Get agent id of an agent module.
 *
 * @param integer $id_agentmodule Agent module id.
 *
 * @return integer The id of the agent of given agent module
 */
function modules_get_agentmodule_agent($id_agentmodule)
{
    return (int) db_get_value(
        'id_agente',
        'tagente_modulo',
        'id_agente_modulo',
        (int) $id_agentmodule
    );
}


/**
 * Get agent name of an agent module.
 *
 * @param integer $id_agente_modulo Agent module id.
 *
 * @return string The name of the given agent module.
 */
function modules_get_agentmodule_agent_name($id_agentmodule)
{
    // Since this is a helper function we don't need to do casting
    return (string) agents_get_name(modules_get_agentmodule_agent($id_agentmodule));
}


/**
 * Get agent alias of an agent module.
 *
 * @param integer $id_agente_modulo Agent module id.
 *
 * @return string The alias of the given agent module.
 */
function modules_get_agentmodule_agent_alias($id_agentmodule)
{
    // Since this is a helper function we don't need to do casting
    return (string) agents_get_alias(modules_get_agentmodule_agent($id_agentmodule));
}


/**
 * Get the module name of an agent module.
 *
 * @param integer $id_agente_modulo Agent module id.
 *
 * @return string Name of the given agent module.
 */
function modules_get_agentmodule_name($id_agente_modulo)
{
    return (string) db_get_value('nombre', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}


/**
 * Get the module descripcion of an agent module.
 *
 * @param integer $id_agente_modulo Agent module id.
 *
 * @return string descripcion of the given agent module.
 */
function modules_get_agentmodule_descripcion($id_agente_modulo)
{
    return (string) db_get_value('descripcion', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}


/**
 * Get the module type of an agent module.
 *
 * @param integer $id_agentmodule Agent module id.
 *
 * @return string Module type of the given agent module.
 */
function modules_get_agentmodule_type($id_agentmodule)
{
    $return = db_get_value(
        'id_tipo_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        (int) $id_agentmodule
    );

    return (int) $return;
}


/**
 * Get the module kind (dataserver, networkserver...) of an agent module.
 *
 * @param integer $id_agentmodule Agent module id.
 *
 * @return string Module kind of the given agent module.
 */
function modules_get_agentmodule_kind($id_agentmodule)
{
    $id_modulo = (int) db_get_value(
        'id_modulo',
        'tagente_modulo',
        'id_agente_modulo',
        (int) $id_agentmodule
    );

    switch ($id_modulo) {
        case MODULE_DATA:
        return 'dataserver';

            break;
        case MODULE_NETWORK:
        case MODULE_SNMP:
        return 'networkserver';

            break;
        case MODULE_PLUGIN:
        return 'pluginserver';

            break;
        case MODULE_PREDICTION:
        return 'predictionserver';

            break;
        case MODULE_WMI:
        return 'wmiserver';

            break;
        case MODULE_WEB:
        return 'webserver';

            break;
        default:
        return 'other';
            break;
    }
}


/**
 * Get the unit of an agent module.
 *
 * @param integer $id_agente_module Agent module id.
 *
 * @return string Module unit of the given agent module.
 */
function modules_get_unit($id_agente_modulo)
{
    return $unit = (string) db_get_value('unit', 'tagente_modulo', 'id_agente_modulo', (int) $id_agente_modulo);
}


function modules_get_interfaces($id_agent, $fields_param=false)
{
    $return = [];

    $fields = $fields_param;
    if ($fields !== false) {
        if (is_array($fields)) {
            $fields[] = 'id_tipo_modulo';
        }
    }

    $modules = db_get_all_rows_filter(
        'tagente_modulo',
        ['id_agente' => $id_agent],
        $fields
    );

    if (empty($modules)) {
        $modules = [];
    }

    foreach ($modules as $module) {
        // 18 = remote_snmp_proc
        // 6 = remote_icmp_proc
        if ($module['id_tipo_modulo'] == 18) {
            if ($fields_param !== false) {
                if (is_array($fields_param)) {
                    if (in_array('id_tipo_modulo', $fields) !== false) {
                        unset($module['id_tipo_modulo']);
                    }
                }
            }

            $return[] = $module;
        }
    }

    return $return;
}


/**
 * Get all the times a monitor went down during a period.
 *
 * @param integer $id_agent_module Agent module of the monitor.
 * @param integer $period          Period timed to check from date
 * @param integer $date            Date to check (now by default)
 *
 * @return integer The number of times a monitor went down.
 */
function modules_get_monitor_downs_in_period($id_agent_module, $period, $date=0)
{
    global $config;

    if ($date == 0) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT COUNT(`id_agentmodule`)
				FROM `tevento`
				WHERE
					`event_type` = 'monitor_down' 
					AND `id_agentmodule` = %d 
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;

        case 'postgresql':
            $sql = sprintf(
                "SELECT COUNT(\"id_agentmodule\")
				FROM \"tevento\"
				WHERE
					\"event_type\" = 'monitor_down' 
					AND \"id_agentmodule\" = %d 
					AND \"utimestamp\" > %d 
					AND \"utimestamp\" <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;

        case 'oracle':
            $sql = sprintf(
                "SELECT COUNT(id_agentmodule)
				FROM tevento
				WHERE
					event_type = 'monitor_down' 
					AND id_agentmodule = %d 
					AND utimestamp > %d 
					AND utimestamp <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;
    }

    return db_get_sql($sql);
}


/**
 * Get the last time a monitor went down during a period.
 *
 * @param integer $id_agent_module Agent module of the monitor.
 * @param integer $period          Period timed to check from date
 * @param integer $date            Date to check (now by default)
 *
 * @return integer The last time a monitor went down.
 */
function modules_get_last_down_timestamp_in_period($id_agent_module, $period, $date=0)
{
    global $config;

    if ($date == 0) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT MAX(`timestamp`)
				FROM `tevento`
				WHERE event_type = 'monitor_down' 
					AND `id_agentmodule` = %d 
					AND `utimestamp` > %d 
					AND `utimestamp` <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;

        case 'postgresql':
            $sql = sprintf(
                "SELECT MAX(\"timestamp\")
				FROM \"tevento\"
				WHERE event_type = 'monitor_down' 
					AND \"id_agentmodule\" = %d 
					AND \"utimestamp\" > %d 
					AND \"utimestamp\" <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;

        case 'oracle':
            $sql = sprintf(
                "SELECT MAX(timestamp)
				FROM tevento
				WHERE event_type = 'monitor_down' 
					AND id_agentmodule = %d 
					AND utimestamp > %d 
					AND utimestamp <= %d",
                $id_agent_module,
                $datelimit,
                $date
            );
        break;
    }

    return db_get_sql($sql);
}


/**
 * Get all the monitors defined in an group.
 *
 * @param integer $id_group Group id to get all the monitors.
 *
 * @return array An array with all the monitors defined in the group (tagente_modulo).
 */
function modules_get_monitors_in_group($id_group)
{
    global $config;

    if ($id_group <= 0) {
        // We select all groups the user has access to if it's 0 or -1
        global $config;
        $id_group = array_keys(users_get_groups($config['id_user']));
    }

    if (is_array($id_group)) {
        $id_group = implode(',', $id_group);
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT `tagente_modulo`.*
				FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
				WHERE `id_tipo_modulo` = `id_tipo` 
					AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente` 
					AND `ttipo_modulo`.`nombre` LIKE '%%_proc' 
					AND `tagente`.`id_grupo` IN (%s)
				ORDER BY `tagente`.`nombre`",
                $id_group
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                "SELECT tagente_modulo.*
				FROM tagente_modulo, ttipo_modulo, tagente
				WHERE id_tipo_modulo = id_tipo 
					AND tagente.id_agente = tagente_modulo.id_agente 
					AND ttipo_modulo.nombre LIKE '%%_proc' 
					AND tagente.id_grupo IN (%s)
				ORDER BY tagente.nombre",
                $id_group
            );
        break;
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the modules defined in an group.
 *
 * @param integer $id_group Group id to get all the modules.
 *
 * @return array An array with all the modules defined in the group (tagente_modulo).
 */
function modules_get_modules_in_group($id_group)
{
    global $config;

    if ($id_group <= 0) {
        // We select all groups the user has access to if it's 0 or -1
        global $config;
        $id_group = array_keys(users_get_groups($config['id_user']));
    }

    if (is_array($id_group)) {
        $id_group = implode(',', $id_group);
    }

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                'SELECT `tagente_modulo`.*
				FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
				WHERE `id_tipo_modulo` = `id_tipo` 
					AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente` 
					AND `tagente`.`id_grupo` IN (%s)
				ORDER BY `tagente`.`nombre`',
                $id_group
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                'SELECT tagente_modulo.*
				FROM tagente_modulo, ttipo_modulo, tagente
				WHERE id_tipo_modulo = id_tipo 
					AND tagente.id_agente = tagente_modulo.id_agente 
					AND tagente.id_grupo IN (%s)
				ORDER BY tagente.nombre',
                $id_group
            );
        break;
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the monitors defined in an agent.
 *
 * @param integer $id_agent Agent id to get all the monitors.
 *
 * @return array An array with all the monitors defined (tagente_modulo).
 */
function modules_get_monitors_in_agent($id_agent)
{
    global $config;

    switch ($config['dbtype']) {
        case 'mysql':
            $sql = sprintf(
                "SELECT `tagente_modulo`.*
				FROM `tagente_modulo`, `ttipo_modulo`, `tagente`
				WHERE `id_tipo_modulo` = `id_tipo`
					AND `tagente`.`id_agente` = `tagente_modulo`.`id_agente`
					AND `ttipo_modulo`.`nombre` LIKE '%%_proc'
					AND `tagente`.`id_agente` = %d",
                $id_agent
            );
        break;

        case 'postgresql':
        case 'oracle':
            $sql = sprintf(
                "SELECT tagente_modulo.*
				FROM tagente_modulo, ttipo_modulo, tagente
				WHERE id_tipo_modulo = id_tipo
					AND tagente.id_agente = tagente_modulo.id_agente
					AND ttipo_modulo.nombre LIKE '%%_proc'
					AND tagente.id_agente = %d",
                $id_agent
            );
        break;
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Get all the monitors down during a period of time.
 *
 * @param array   $monitors An array with all the monitors to check. Each
 *   element of the array must be a dictionary.
 * @param integer $period   Period of time to check the monitors.
 * @param integer $date     Beginning date to check the monitors.
 *
 * @return array An array with all the monitors that went down in that
 * period of time.
 */
function modules_get_monitors_down($monitors, $period=0, $date=0)
{
    $monitors_down = [];

    if (empty($monitors)) {
        return $monitors_down;
    }

    foreach ($monitors as $monitor) {
        $down = modules_get_monitor_downs_in_period($monitor['id_agente_modulo'], $period, $date);
        if ($down > 0) {
            array_push($monitors_down, $monitor);
        }
    }

    return $monitors_down;
}


/**
 * Get the module type name (type = generic_data, remote_snmp, ...)
 *
 * @param integer $id_type Type id
 *
 * @return string Name of the given type.
 */
function modules_get_moduletype_name($id_type)
{
    return (string) db_get_value('nombre', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}


/**
 * Get the module type description
 *
 * @param integer $id_type Type id
 *
 * @return string Description of the given type.
 */
function modules_get_moduletype_description($id_type)
{
    return (string) db_get_value('descripcion', 'ttipo_modulo', 'id_tipo', (int) $id_type);
}


/**
 * Returns an array with all module types (default) or if "remote" or "agent"
 * is passed it will return only remote (ICMP, SNMP, TCP...) module types
 * otherwise the full list + the column you specify
 *
 * @param string Specifies which type to return (will return an array with id's)
 * @param string Which rows to select (defaults to nombre)
 *
 * @return array Either the full table or if a type is specified, an array with id's
 */
function modules_get_moduletypes($type='all', $rows='nombre')
{
    $return = [];
    $rows = (array) $rows;
    // Cast as array
    $row_cnt = count($rows);
    if ($type == 'remote') {
        return array_merge(range(6, 18), (array) 100);
    } else if ($type == 'agent') {
        return array_merge(range(1, 4), range(19, 24));
    }

    $sql = sprintf(
        'SELECT id_tipo, %s
		FROM ttipo_modulo',
        implode(',', $rows)
    );
    $result = db_get_all_rows_sql($sql);
    if ($result === false) {
        return $return;
    }

    foreach ($result as $type) {
        if ($row_cnt > 1) {
            $return[$type['id_tipo']] = $type;
        } else {
            $return[$type['id_tipo']] = $type[reset($rows)];
        }
    }

    return $return;
}


/**
 * Get the interval value of an agent module.
 *
 * If the module interval is not set, the agent interval is returned
 *
 * @param int Id agent module to get the interval value.
 *
 * @return integer Module interval or agent interval if no module interval
 */
function modules_get_interval($id_agent_module)
{
    $interval = (int) db_get_value('module_interval', 'tagente_modulo', 'id_agente_modulo', (int) $id_agent_module);
    if ($interval > 0) {
        return $interval;
    }

    $id_agent = modules_give_agent_id_from_module_id($id_agent_module);
    return (int) agents_get_interval($id_agent);
}


/**
 * Get module type icon.
 *
 * TODO: Create ui_print_moduletype_icon and print the full tag including hover etc.
 *
 * @deprecated Use ui_print_moduletype_icon instead
 *
 * @param int Module type id
 *
 * @return string Icon filename of the given group
 */
function modules_show_icon_type($id_type)
{
    return (string) db_get_value('icon', 'ttipo_modulo', 'id_tipo', $id_type);
}


/**
 * Get agent id from an agent module.
 *
 * @param int Id of the agent module.
 *
 * @return integer The agent id of the given module.
 */
function modules_give_agent_id_from_module_id($id_agent_module)
{
    return (int) db_get_value('id_agente', 'tagente_modulo', 'id_agente_modulo', $id_agent_module);
}


/**
 * Get the status of an agent module.
 *
 * @param int Id agent module to check.
 * @param boolean                      $without_alerts The flag to check only the module, by default false.
 *
 * @return integer Module status. Value 4 means that some alerts assigned to the
 * module were fired.
 */
function modules_get_agentmodule_status($id_agentmodule=0, $without_alerts=false, $metaconsole=false, $id_server=null)
{
    $current_timestamp = get_system_time();

    if ($metaconsole) {
        $server = db_get_row('tmetaconsole_setup', 'id', $id_server);

        if (metaconsole_connect($server) == NOERR) {
            $status_row = db_get_row(
                'tagente_estado',
                'id_agente_modulo',
                $id_agentmodule
            );

            if (!$without_alerts) {
                $times_fired = db_get_value('SUM(times_fired)', 'talert_template_modules', 'id_agent_module', $id_agentmodule);
                if ($times_fired > 0) {
                    switch ($status_row['estado']) {
                        case AGENT_STATUS_WARNING:
                        return AGENT_MODULE_STATUS_WARNING_ALERT;

                        // Alert fired in warning
                            break;
                        case AGENT_STATUS_CRITICAL:
                        return AGENT_MODULE_STATUS_CRITICAL_ALERT;

                        // Alert fired in critical
                            break;
                    }
                }
            }
        }

        metaconsole_restore_db();
    } else {
        $status_row = db_get_row(
            'tagente_estado',
            'id_agente_modulo',
            $id_agentmodule
        );

        if (!$without_alerts) {
            $times_fired = db_get_value(
                'SUM(times_fired)',
                'talert_template_modules',
                'id_agent_module',
                $id_agentmodule
            );

            if ($times_fired > 0) {
                switch ($status_row['estado']) {
                    case AGENT_STATUS_NORMAL:
                    return AGENT_MODULE_STATUS_NORMAL_ALERT;

                        break;
                    case AGENT_STATUS_WARNING:
                    return AGENT_MODULE_STATUS_WARNING_ALERT;

                    // Alert fired in warning
                        break;
                    case AGENT_STATUS_CRITICAL:
                    return AGENT_MODULE_STATUS_CRITICAL_ALERT;

                    // Alert fired in critical
                        break;
                }
            }
        }
    }

    return $status_row['estado'];
}


/**
 * Get the last status of an agent module.
 *
 * @param int Id agent module to check.
 *
 * @return integer Module last status.
 */
function modules_get_agentmodule_last_status($id_agentmodule=0)
{
    $status_row = db_get_row('tagente_estado', 'id_agente_modulo', $id_agentmodule);

    return $status_row['known_status'];
}


/**
 * Get the data by applying the macro.
 *
 * @param string macro unit.
 *
 * @return false or data with applied macro.
 */
function modules_get_unit_macro($data, $macro)
{
    if (modules_is_unit_macro($macro)) {
        $data = human_milliseconds_to_string($data);
        return $data;
    }

    return false;
}


/**
 * Check if it's a unit macro.
 *
 * @param string macro unit.
 *
 * @return false or true.
 */
function modules_is_unit_macro($macro)
{
    if ($macro == '_timeticks_') {
        return true;
    }

    return false;
}


function modules_get_last_contact($id_agentmodule)
{
    return db_get_value(
        'utimestamp',
        'tagente_estado',
        'id_agente_modulo',
        $id_agentmodule
    );
}


/**
 * Get the current value of an agent module.
 *
 * @param int Agent module id.
 *
 * @return integer a numerically formatted value
 */
function modules_get_last_value($id_agentmodule)
{
    return db_get_value(
        'datos',
        'tagente_estado',
        'id_agente_modulo',
        $id_agentmodule
    );
}


/**
 * Get the previous data to the timestamp provided.
 *
 * It's useful to know the first value of a module in an interval,
 * since it will be the last value in the table which has a timestamp
 * before the beginning of the interval. All this calculation is due
 * to the data compression algorithm.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function modules_get_previous_data($id_agent_module, $utimestamp=0, $string=0)
{
    if (empty($utimestamp)) {
        $utimestamp = time();
    }

    if ($string == 1) {
        $table = 'tagente_datos_string';
    } else {
        $table = 'tagente_datos';
    }

    $sql = sprintf(
        'SELECT * FROM %s 
		WHERE id_agente_modulo = %d 
		AND utimestamp = ( SELECT max(utimestamp) 
							FROM tagente_datos 
							WHERE id_agente_modulo = %d 
							AND utimestamp <= %d )',
        $table,
        $id_agent_module,
        $id_agent_module,
        $utimestamp
    );

    $search_in_history_db = db_search_in_history_db($utimestamp);

    return db_get_row_sql($sql, $search_in_history_db);
}


/**
 * Get the next data to the timestamp provided.
 *
 * @param int Agent module id
 * @param int The timestamp to look backwards from and get the data.
 * @param int 1 if the module has a string type.
 *
 * @return mixed The row of tagente_datos of the last period. False if there were no data.
 */
function modules_get_next_data($id_agent_module, $utimestamp=0, $string=0)
{
    if (empty($utimestamp)) {
        $utimestamp = time();
    }

    if ($string == 1) {
        $table = 'tagente_datos_string';
    } else {
        $table = 'tagente_datos';
    }

    $interval = modules_get_interval($id_agent_module);
    $sql = sprintf(
        'SELECT *
		FROM '.$table.'
		WHERE id_agente_modulo = %d 
			AND utimestamp <= %d 
			AND utimestamp >= %d
		ORDER BY utimestamp ASC',
        $id_agent_module,
        ($utimestamp + $interval),
        $utimestamp
    );

    $search_in_history_db = db_search_in_history_db($utimestamp);

    return db_get_row_sql($sql, $search_in_history_db);
}


/**
 * Get all the values of an agent module in a period of time.
 *
 * @param int Agent module id
 * @param int Period of time to check (in seconds)
 * @param int Top date to check the values. Default current time.
 * @param
 * @param
 * @param string 'ASC' od 'DESC'
 * @param string with a json with parameters to filter data
 *     string object:
 *        value: Text to search
 *        exact: Boolean. True if search exact phrase or false to content
 *
 * @return array The module value and the timestamp
 */
function modules_get_agentmodule_data(
    $id_agent_module,
    $period,
    $date=0,
    $trash=false,
    $conexion=false,
    $order='ASC',
    $freesearch=''
) {
    global $config;

    $module = db_get_row(
        'tagente_modulo',
        'id_agente_modulo',
        $id_agent_module
    );

    if ($date < 1) {
        $date = get_system_time();
    }

    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    switch ($module['id_tipo_modulo']) {
        // generic_data_string
        case 3:
            // remote_tcp_string
        case 10:
            // remote_snmp_string
        case 17:
            // async_string
        case 23:
            // Free search is a json with value and exact modifier
            $freesearch = json_decode($freesearch, true);
            $freesearch_sql = '';
            if (isset($freesearch['value']) && !empty($freesearch['value'])) {
                $freesearch_sql = ' AND datos ';
                if ($freesearch['exact']) {
                    $freesearch_sql .= "='".$freesearch['value']."' ";
                } else {
                    $freesearch_sql .= " LIKE '%".$freesearch['value']."%' ";
                }
            }

            $sql = sprintf(
                'SELECT datos AS data, utimestamp FROM tagente_datos_string
					WHERE id_agente_modulo = %d
					%s
					AND utimestamp > %d	AND utimestamp <= %d
					ORDER BY utimestamp %s',
                $id_agent_module,
                $freesearch_sql,
                $datelimit,
                $date,
                $order
            );
        break;

        // log4x
        case 24:
            $sql = sprintf(
                'SELECT stacktrace AS data, utimestamp
				FROM tagente_datos_log4x
				WHERE id_agente_modulo = %d
					AND utimestamp > %d AND utimestamp <= %d
				ORDER BY utimestamp %s',
                $id_agent_module,
                $datelimit,
                $date,
                $order
            );
        break;

        case 2:
        case 6:
        case 9:
        case 18:
        case 21:
        case 31:
            if ($config['render_proc']) {
                $sql = sprintf(
                    "
					SELECT IF(datos >= 1, '".$config['render_proc_ok']."', '".$config['render_proc_fail']."') as data, utimestamp
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp > %d AND utimestamp <= %d
					ORDER BY utimestamp %s",
                    $id_agent_module,
                    $datelimit,
                    $date,
                    $order
                );
            } else {
                $sql = sprintf(
                    '
					SELECT datos AS data, utimestamp
					FROM tagente_datos
					WHERE id_agente_modulo = %d
						AND utimestamp > %d AND utimestamp <= %d
					ORDER BY utimestamp %s',
                    $id_agent_module,
                    $datelimit,
                    $date,
                    $order
                );
            }
        break;

        default:
            $sql = sprintf(
                '
				SELECT datos AS data, utimestamp
				FROM tagente_datos
				WHERE id_agente_modulo = %d
					AND utimestamp > %d AND utimestamp <= %d
				ORDER BY utimestamp %s',
                $id_agent_module,
                $datelimit,
                $date,
                $order
            );
        break;
    }

    $values = db_get_all_rows_sql($sql, $search_in_history_db, false, $conexion);

    if ($values === false) {
        return [];
    }

    $module_name = modules_get_agentmodule_name($id_agent_module);
    $agent_id = modules_get_agentmodule_agent($id_agent_module);
    $agent_name = modules_get_agentmodule_agent_name($id_agent_module);
    $module_type = modules_get_agentmodule_type($id_agent_module);

    foreach ($values as $key => $data) {
        $values[$key]['module_name'] = $module_name;
        $values[$key]['agent_id'] = $agent_id;
        $values[$key]['agent_name'] = $agent_name;
        $values[$key]['module_type'] = $module_type;
    }

    if ($search_in_history_db) {
        $datos = [];
        foreach ($values as $key => $value) {
            $utimestamp[$key] = $value['utimestamp'];
        }

        array_multisort($utimestamp, SORT_DESC, $values);
        foreach ($utimestamp as $key => $utimes) {
            $datos[$key] = [
                'utimestamp'  => $utimes,
                'data'        => $values[$key]['data'],
                'module_name' => $values[$key]['module_name'],
                'agent_id'    => $values[$key]['agent_id'],
                'agent_name'  => $values[$key]['agent_name'],
                'module_type' => $values[$key]['module_type'],
            ];
        }

        $values = $datos;
    }

    return $values;
}


/**
 * This function gets the modulegroup for a given group
 *
 * @param int The group id
 *
 * @return integer The modulegroup id
 */
function modules_get_agentmodule_modulegroup($id_module)
{
    return (int) db_get_value('id_module_group', 'tagente_modulo', 'id_agente_modulo', (int) $id_module);
}


/**
 * Gets all module groups. (General, Networking, System).
 *
 * Module groups are merely for sorting frontend
 *
 * @return array All module groups
 */
function modules_get_modulegroups()
{
    $result = db_get_all_fields_in_table('tmodule_group');
    $return = [];

    if (empty($result)) {
        return $return;
    }

    foreach ($result as $modulegroup) {
        $return[$modulegroup['id_mg']] = $modulegroup['name'];
    }

    return $return;
}


/**
 * Gets a modulegroup name based on the id
 *
 * @param int The id of the modulegroup
 *
 * @return string The modulegroup name
 */
function modules_get_modulegroup_name($modulegroup_id)
{
    if ($modulegroup_id == 0) {
        return false;
    } else {
        return (string) db_get_value('name', 'tmodule_group', 'id_mg', (int) $modulegroup_id);
    }
}


/**
 * Returns target color to be used based on the status received.
 *
 * @param integer $status Source information.
 *
 * @return string HTML tag for color.
 */
function modules_get_color_status($status)
{
    if (isset($status) === false) {
        return COL_UNKNOWN;
    }

    switch ((string) $status) {
        case (string) AGENT_MODULE_STATUS_NORMAL:
        case (string) AGENT_STATUS_NORMAL:
        case STATUS_MODULE_OK:
        case STATUS_AGENT_OK:
        case STATUS_ALERT_NOT_FIRED:
        case STATUS_SERVER_OK:
        case STATUS_MODULE_OK_BALL:
        case STATUS_AGENT_OK_BALL:
        case STATUS_ALERT_NOT_FIRED_BALL:
        return COL_NORMAL;

        case AGENT_MODULE_STATUS_NOT_INIT:
        case AGENT_STATUS_NOT_INIT:
        case STATUS_MODULE_NO_DATA:
        case STATUS_AGENT_NOT_INIT:
        case STATUS_AGENT_NO_DATA:
        case STATUS_MODULE_NO_DATA_BALL:
        case STATUS_AGENT_NO_DATA_BALL:
        case STATUS_AGENT_NO_MONITORS_BALL:
        return COL_NOTINIT;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        case AGENT_STATUS_CRITICAL:
        case STATUS_MODULE_CRITICAL:
        case STATUS_AGENT_CRITICAL:
        case STATUS_MODULE_CRITICAL_BALL:
        case STATUS_AGENT_CRITICAL_BALL:
        return COL_CRITICAL;

        case AGENT_MODULE_STATUS_WARNING:
        case AGENT_STATUS_WARNING:
        case STATUS_MODULE_WARNING:
        case STATUS_AGENT_WARNING:
        case STATUS_MODULE_WARNING_BALL:
        case STATUS_AGENT_WARNING_BALL:
        return COL_WARNING;

        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        case AGENT_MODULE_STATUS_WARNING_ALERT:
        case AGENT_STATUS_ALERT_FIRED:
        case STATUS_ALERT_FIRED:
        case STATUS_ALERT_FIRED_BALL:
        return COL_ALERTFIRED;

        case AGENT_MODULE_STATUS_UNKNOWN:
        case AGENT_STATUS_UNKNOWN:
        case STATUS_MODULE_UNKNOWN:
        case STATUS_AGENT_UNKNOWN:
        case STATUS_AGENT_DOWN:
        case STATUS_ALERT_DISABLED:
        case STATUS_MODULE_UNKNOWN_BALL:
        case STATUS_AGENT_UNKNOWN_BALL:
        case STATUS_AGENT_DOWN_BALL:
        case STATUS_ALERT_DISABLED_BALL:
        return COL_UNKNOWN;

        case STATUS_SERVER_DOWN:
        case STATUS_SERVER_DOWN_BALL:
        return '#444';

        default:
            // Ignored.
        break;
    }

    return COL_IGNORED;
}


/**
 * Gets a module status an modify the status and title reference variables
 *
 * @param mixed The module data (Necessary $module['datos'] and $module['estado']
 * @param int status reference variable
 * @param string title reference variable
 */
function modules_get_status($id_agent_module, $db_status, $data, &$status, &$title)
{
    $status = STATUS_MODULE_WARNING;
    $title = '';
    global $config;

    // This module is initialized ? (has real data)
    // $module_init = db_get_value ('utimestamp', 'tagente_estado', 'id_agente_modulo', $id_agent_module);
    if ($db_status == AGENT_MODULE_STATUS_NO_DATA) {
        $status = STATUS_MODULE_NO_DATA;
        $title = __('NOT INIT');
    } else if ($db_status == AGENT_MODULE_STATUS_CRITICAL_BAD) {
        $status = STATUS_MODULE_CRITICAL;
        $title = __('CRITICAL');
    } else if ($db_status == AGENT_MODULE_STATUS_WARNING) {
        $status = STATUS_MODULE_WARNING;
        $title = __('WARNING');
    } else if ($db_status == AGENT_MODULE_STATUS_NORMAL) {
        $status = STATUS_MODULE_OK;
        $title = __('NORMAL');
    } else if ($db_status == AGENT_MODULE_STATUS_UNKNOWN) {
        $status = STATUS_MODULE_UNKNOWN;
        $last_status = modules_get_agentmodule_last_status($id_agent_module);
        switch ($last_status) {
            case AGENT_STATUS_NORMAL:
                $title = __('UNKNOWN').' - '.__('Last status').' '.__('NORMAL');
            break;

            case AGENT_STATUS_CRITICAL:
                $title = __('UNKNOWN').' - '.__('Last status').' '.__('CRITICAL');
            break;

            case AGENT_STATUS_WARNING:
                $title = __('UNKNOWN').' - '.__('Last status').' '.__('WARNING');
            break;
        }
    }

    if (is_numeric($data)) {
        $title .= ': '.remove_right_zeros(number_format($data, $config['graph_precision']));
    } else {
        $text = io_safe_output($data);

        // Fixed the data from Selenium Plugin
        if ($text != strip_tags($text)) {
            $text = io_safe_input($text);
        }

        $title .= ': '.substr($text, 0, 42);
    }
}


// Get unknown agents by using the status code in modules
function modules_agents_unknown($module_name)
{
    return db_get_sql(
        "SELECT COUNT( DISTINCT tagente.id_agente)
		FROM tagente_estado, tagente, tagente_modulo
		WHERE tagente.disabled = 0
			AND tagente_estado.utimestamp != 0
			AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
			AND tagente_modulo.disabled = 0
			AND tagente_estado.id_agente = tagente.id_agente
			AND tagente_estado.estado = 3
			AND tagente_modulo.nombre = '$module_name'"
    );
}


// Get ok agents by using the status code in modules.
function modules_agents_ok($module_name)
{
    // !!!Query explanation!!!
    // An agent is OK if all its modules are OK
    // The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
    // This query grouped all modules by agents and select the MAX value for status which has the value 0
    // If MAX(estado) is 0 it means all modules has status 0 => OK
    // Then we count the agents of the group selected to know how many agents are in OK status
    return db_get_sql(
        "SELECT COUNT(max_estado)
		FROM (
			SELECT MAX(tagente_estado.estado) as max_estado
			FROM tagente_estado, tagente, tagente_modulo
			WHERE tagente.disabled = 0
				AND tagente_estado.utimestamp != 0
				AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
				AND tagente_modulo.disabled = 0
				AND tagente_estado.id_agente = tagente.id_agente
				AND tagente_modulo.nombre = '$module_name'
			GROUP BY tagente.id_agente HAVING max_estado = 0) AS S1"
    );
}


// Get critical agents by using the status code in modules.
function modules_agents_critical($module_name)
{
    // !!!Query explanation!!!
    // An agent is Warning when has at least one module in warning status and nothing more in critical status
    // The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
    // If estado = 1 it means at leas 1 module is in critical status so the agent is critical
    // Then we count the agents of the group selected to know how many agents are in critical status
    return db_get_sql(
        "SELECT COUNT( DISTINCT tagente_estado.id_agente) 
		FROM tagente_estado, tagente, tagente_modulo 
		WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 
			AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
			AND tagente_modulo.disabled = 0 
			AND estado = 1 
			AND tagente_estado.id_agente = tagente.id_agente 
			AND tagente_modulo.nombre = '$module_name'"
    );
}


// Get warning agents by using the status code in modules.
function modules_agents_warning($module_name)
{
    // !!!Query explanation!!!
    // An agent is Warning when has at least one module in warning status and nothing more in critical status
    // The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
    // This query grouped all modules by agents and select the MIN value for status which has the value 0
    // If MIN(estado) is 2 it means at least one module is warning and there is no critical modules
    // Then we count the agents of the group selected to know how many agents are in warning status
    return db_get_sql(
        "SELECT COUNT(min_estado) 
		FROM (SELECT MAX(tagente_estado.estado) as min_estado 
			FROM tagente_estado, tagente, tagente_modulo 
			WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 
				AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo 
				AND tagente_modulo.disabled = 0 
				AND tagente_estado.id_agente = tagente.id_agente 
				AND tagente_modulo.nombre = '$module_name' 
			GROUP BY tagente.id_agente 
				HAVING min_estado = 2) AS S1"
    );
}


// Get unknown agents by using the status code in modules
function modules_group_agent_unknown($module_group)
{
    return db_get_sql(
        "SELECT COUNT(DISTINCT tagente.id_agente)
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente=tagente_modulo.id_agente
			AND critical_count=0 AND warning_count=0
			AND tagente.disabled = 0
			AND unknown_count>0 AND id_module_group = $module_group"
    );
}


// Get ok agents by using the status code in modules.
function modules_group_agent_ok($module_group)
{
    return db_get_sql(
        "SELECT COUNT(DISTINCT tagente.id_agente)
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente=tagente_modulo.id_agente
			AND normal_count = total_count
			AND tagente.disabled = 0
			AND id_module_group = $module_group"
    );
}


// Get critical agents by using the status code in modules.
function modules_group_agent_critical($module_group)
{
    return db_get_sql(
        "SELECT COUNT(DISTINCT tagente.id_agente)
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente=tagente_modulo.id_agente
			AND tagente.disabled = 0
			AND critical_count > 0 AND id_module_group = $module_group"
    );
}


// Get warning agents by using the status code in modules.
function modules_group_agent_warning($module_group)
{
    return db_get_sql(
        "SELECT COUNT(DISTINCT tagente.id_agente)
		FROM tagente, tagente_modulo
		WHERE tagente.id_agente=tagente_modulo.id_agente
			AND critical_count = 0 AND warning_count > 0
			AND tagente.disabled = 0
			AND id_module_group = $module_group"
    );
}


// Return a base64 encoded JSON document to store module macros inside the database
function modules_get_module_macros_json($macro_names, $macro_values)
{
    $module_macros = [];
    for ($i = 0; $i < count($macro_names); $i++) {
        if (isset($macro_values[$i])) {
            $module_macros[$macro_names[$i]] = $macro_values[$i];
        }
    }

    return base64_encode(json_encode($module_macros));
}


/**
 * Returns the relations between modules.
 *
 * @param array Optional assoc array with parameters.
 * (int) id_agent
 * (int) id_module
 * (bool) disabled_update
 * (string) modules_type: The type of the two modules
 *
 * @return mixed Array with relations between modules. False if there were no data.
 */
function modules_get_relations($params=[])
{
    $id_agent = 0;
    if (isset($params['id_agent'])) {
        $id_agent = $params['id_agent'];
    }

    $id_module = 0;
    if (isset($params['id_module'])) {
        $id_module = $params['id_module'];
    }

    $disabled_update = -1;
    if (isset($params['disabled_update'])) {
        $disabled_update = (int) $params['disabled_update'];
        if ($disabled_update > 1) {
            $disabled_update = 1;
        }
    }

    $modules_type = '';
    $modules_type_filter = '';
    if (isset($params['modules_type'])) {
        $module_type = 'INNER JOIN ttipo_modulo ttm ON tam.id_tipo_modulo = ttm.id_tipo';
        $modules_type_filter = sprintf(
            "AND ttm.nombre = '%s'",
            $params['modules_type']
        );
    }

    $distinct = '';
    if (empty($params)) {
        $distinct = 'DISTINCT';
    }

    $module_a_filter = '';
    $module_b_filter = '';
    if ($id_module > 0) {
        $module_a_filter = sprintf('AND tmr.module_a = %d', $id_module);
        $module_b_filter = sprintf('AND tmr.module_b = %d', $id_module);
    }

    $agent_filter = '';
    if ($id_agent > 0) {
        $agent_filter = sprintf('AND ta.id_agente = %d', $id_agent);
    }

    $disabled_update_filter = '';
    if ($disabled_update >= 0) {
        $disabled_update_filter = sprintf(
            'AND tmr.disable_update = %d',
            $disabled_update
        );
    }

    $sql = sprintf(
        'SELECT %s tmr.id, tmr.module_a, tmr.module_b,
        tmr.disable_update, tmr.type 
        FROM tmodule_relationship tmr 
        INNER JOIN tagente_modulo tam 
            ON (tmr.module_a = tam.id_agente_modulo %s) 
            OR (tmr.module_b = tam.id_agente_modulo %s) 
        INNER JOIN tagente ta 
            ON tam.id_agente = ta.id_agente
        %s 
        WHERE 1=1 %s %s %s',
        $distinct,
        $module_a_filter,
        $module_b_filter,
        $module_type,
        $agent_filter,
        $disabled_update_filter,
        $modules_type_filter
    );

    return db_get_all_rows_sql($sql);
}


/**
 * Check if a relation already exists.
 *
 * @param int First module id.
 * @param mixed (Optional) int The second module id. array The module ids filter.
 *
 * @return boolean True if the relation exists, false otherwise.
 */
function modules_relation_exists($id_module, $id_module_other=false)
{
    if ($id_module_other === false) {
        $sql = sprintf(
            'SELECT id
						FROM tmodule_relationship
						WHERE module_a = %d
							OR module_b = %d',
            $id_module,
            $id_module
        );
    } else if (is_array($id_module_other)) {
        $ids_other = 0;
        if (!empty($id_module_other)) {
            $ids_other = implode(',', $id_module_other);
        }

        $sql = sprintf(
            'SELECT id
						FROM tmodule_relationship
						WHERE (module_a = %d AND module_b IN (%s))
							OR (module_b = %d AND module_a IN (%s))',
            $id_module,
            $ids_other,
            $id_module,
            $ids_other
        );
    } else {
        $sql = sprintf(
            'SELECT id
						FROM tmodule_relationship
						WHERE (module_a = %d AND module_b = %d)
							OR (module_b = %d AND module_a = %d)',
            $id_module,
            $id_module_other,
            $id_module,
            $id_module_other
        );
    }

    return (bool) db_get_row_sql($sql);
}


/**
 * Change the 'disabled_update' value of a relation row.
 *
 * @param integer $id_module_a Id agent module a.
 * @param integer $id_module_b Id agent module b.
 * @param string  $type        Type direct or failover.
 *
 * @return boolean True if the 'disabled_update' changes to 1, false otherwise.
 */
function modules_add_relation($id_module_a, $id_module_b, $type='direct')
{
    $result = false;

    if (!modules_relation_exists($id_module_a, $id_module_b) && $id_module_a > 0 && $id_module_b > 0) {
        $values = [
            'module_a' => $id_module_a,
            'module_b' => $id_module_b,
            'type'     => $type,
        ];
        $result = db_process_sql_insert('tmodule_relationship', $values);
    }

    return $result;
}


/**
 * Change the 'disabled_update' value of a relation row.
 *
 * @param int Relation id.
 *
 * @return boolean True if the 'disabled_update' changes to 1, false otherwise.
 */
function modules_delete_relation($id_relation)
{
    $result = db_process_sql_delete('tmodule_relationship', ['id' => $id_relation]);

    return $result;
}


/**
 * Change the 'disabled_update' value of a relation row.
 *
 * @param int Relation id.
 *
 * @return boolean True if the 'disabled_update' changes to 1, false otherwise.
 */
function modules_change_relation_lock($id_relation)
{
    $old_value = (int) db_get_value('disable_update', 'tmodule_relationship', 'id', $id_relation);
    $new_value = $old_value === 1 ? 0 : 1;

    $result = db_process_sql_update(
        'tmodule_relationship',
        ['disable_update' => $new_value],
        ['id' => $id_relation]
    );

    return ($result !== false ? $new_value : $old_value);
}


/*
 * @return utimestamp with the first contact of the module or first contact before datelimit, false if not-init
 */
function modules_get_first_date($id_agent_module, $datelimit=0)
{
    global $config;

    // check datatype string or normal
    $table = 'tagente_datos';
    $module_type_str = modules_get_agentmodule_type($id_agent_module);
    if (strstr($module_type_str, 'string') !== false) {
        $table = 'tagente_datos_string';
    }

    $search_historydb = false;

    // tagente_estado.first_utimestamp is not valid or is not updated. Scan DBs for first utimestamp
    if ($datelimit > 0) {
        // get last data before datelimit
        $query  = " SELECT max(utimestamp) as utimestamp FROM $table ";
        $query .= " WHERE id_agente_modulo=$id_agent_module ";
        $query .= " AND utimestamp < $datelimit ";
    } else {
        // get first utimestamp
        $query  = " SELECT min(utimestamp) as utimestamp FROM $table ";
        $query .= " WHERE id_agente_modulo=$id_agent_module ";
    }

    // SEARCH ACTIVE DB
    $data = db_get_all_rows_sql($query, $search_historydb);
    if (($data === false) || ($data[0]['utimestamp'] === null) || ($data[0]['utimestamp'] <= 0)) {
        // first utimestamp not found in active database
        // SEARCH HISTORY DB
        $search_historydb = true;
        $data = db_get_all_rows_sql($query, $search_historydb);
    }

    if (($data === false) || ($data[0]['utimestamp'] === null) || ($data[0]['utimestamp'] <= 0)) {
        // Nor active DB nor history DB have the data, the module is not-init
        return [
            'first_utimestamp' => false,
            'search_historydb' => $search_historydb,
        ];
    }

    // The data has been found
    return [
        'first_utimestamp' => $data[0]['utimestamp'],
        'search_historydb' => $search_historydb,
    ];

}


/**
 * Get the unknown time status of a module in a period.
 * If there is more than 1 days between data, there is some unknown time modules
 *
 * @param int id_agent_module.
 * @param int ending interval timestamp
 * @param int interval duration
 *
 * @return integer unknown seconds.
 */
function modules_get_unknown_time($id_agent_module, $date, $period)
{
    // TODO REMOVE THE TIME IN PLANNED DOWNTIME
    if (empty($id_agent_module) || empty($date)) {
        return false;
    }

    // Set initial conditions
    $unknown_seconds = 0;
    $datelimit = ($date - $period);
    $search_in_history_db = db_search_in_history_db($datelimit);

    // Get interval data
    $sql = sprintf(
        'SELECT utimestamp
		FROM tagente_datos
		WHERE id_agente_modulo = %d
			AND utimestamp > %d AND utimestamp <= %d',
        $id_agent_module,
        $datelimit,
        $date
    );
    $sql .= ' ORDER BY utimestamp ASC';
    $interval_data = db_get_all_rows_sql($sql, $search_in_history_db);

    $previous_data = modules_get_previous_data($id_agent_module, $datelimit);

    // All alternatives on first data
    if ($previous_data === false && $interval_data === false) {
        return false;
    } else if ($previous_data !== false && $interval_data === false) {
        if (($date - $previous_data['utimestamp']) <= SECONDS_1DAY) {
            return 0;
        }

        if (($previous_data['utimestamp'] + SECONDS_1DAY) >= $datelimit) {
            return ($date - ($previous_data['utimestamp'] + SECONDS_1DAY));
        } else {
            return $period;
        }
    } else if ($previous_data === false && $interval_data !== false) {
        $first_data = array_shift($interval_data);
        $unknown_seconds += ($first_data['utimestamp'] - $datelimit);
        array_unshift($interval_data, $first_data);
    } else {
        $first_data = array_shift($interval_data);
        $previous_1day = ($previous_data['utimestamp'] + SECONDS_1DAY);
        if ($previous_1day <= $datelimit) {
            $unknown_seconds += ($first_data['utimestamp'] - $datelimit);
        } else if ($previous_1day <= $first_data['utimestamp']) {
            $unknown_seconds += ($first_data['utimestamp'] - $previous_1day);
        }

        array_unshift($interval_data, $first_data);
    }

    // Put utimestamp like last data
    $last_data = modules_get_next_data($id_agent_module, $datelimit);
    $last_data['utimestamp'] = $date;
    array_push($interval_data, $last_data);
    $previous_data = array_shift($interval_data);
    // Check if all datas have data maximum one day before
    foreach ($interval_data as $data) {
        $previous_1day = ($previous_data['utimestamp'] + SECONDS_1DAY);
        if ($previous_1day <= $data['utimestamp']) {
            $unknown_seconds += ($data['utimestamp'] - $previous_1day);
        }

        $previous_data = $data;
    }

    return $unknown_seconds;
}


function modules_get_module_group_status($id_agent, $id_module_group)
{
    $status_return = null;

    $modules = db_get_all_rows_filter(
        'tagente_modulo',
        [
            'id_agente'       => $id_agent,
            'id_module_group' => $id_module_group,
        ]
    );

    if (empty($modules)) {
        $module = [];
    }

    foreach ($modules as $module) {
        $status = modules_get_status($module['id_agente_modulo']);

        // This code is copied from the networkmap old code
        switch ($status) {
            case AGENT_MODULE_STATUS_NORMAL:
                if (is_null($status_return)) {
                    $status_return = AGENT_MODULE_STATUS_NORMAL;
                } else if ($status_return == AGENT_MODULE_STATUS_ALL) {
                    $status_return = AGENT_MODULE_STATUS_NORMAL;
                }
            break;

            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                $status_return = AGENT_MODULE_STATUS_CRITICAL_BAD;
            break;

            case AGENT_MODULE_STATUS_WARNING:
                if (is_null($status_return)) {
                    $status_return = AGENT_MODULE_STATUS_NORMAL;
                } else if ($status_return != AGENT_MODULE_STATUS_CRITICAL_BAD) {
                    $status_return = AGENT_MODULE_STATUS_WARNING;
                }
            break;

            case AGENT_MODULE_STATUS_NO_DATA:
                if (is_null($status_return)) {
                    $status_return = AGENT_MODULE_STATUS_NO_DATA;
                } else if (($status_return == AGENT_MODULE_STATUS_NORMAL)
                    || ($status_return == AGENT_MODULE_STATUS_ALL)
                ) {
                    $status_return = AGENT_MODULE_STATUS_NO_DATA;
                }
            break;

            default:
                $status_return = AGENT_MODULE_STATUS_ALL;
            break;
        }
    }

    return $status_return;
}


function modules_get_modules_name($sql_from, $sql_conditions='', $meta=false)
{
    global $config;

    if (!$meta) {
        // Query to get name of the modules to module name filter combo
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $sql = 'SELECT distinct(tagente_modulo.nombre)
					'.$sql_from.$sql_conditions;
            break;

            case 'oracle':
                $sql = 'SELECT DISTINCT(tagente_modulo.nombre)'.$sql_from.$sql_conditions;
            break;
        }

        $return = db_get_all_rows_sql($sql);

        return $return;
    } else {
        switch ($config['dbtype']) {
            case 'mysql':
            case 'postgresql':
                $sql = 'SELECT distinct(tagente_modulo.nombre)
					'.$sql_from.$sql_conditions;
            break;

            case 'oracle':
                $sql = 'SELECT DISTINCT(tagente_modulo.nombre)'.$sql_from.$sql_conditions;
            break;
        }

        // For each server defined and not disabled:h
        $servers = db_get_all_rows_sql(
            'SELECT *
			FROM tmetaconsole_setup
			WHERE disabled = 0'
        );

        if ($servers === false) {
            $servers = [];
        }

        $result = [];
        $modules = [];
        foreach ($servers as $server) {
            // If connection was good then retrieve all data server
            if (metaconsole_connect($server) == NOERR) {
                $connection = true;
            } else {
                $connection = false;
            }

            // Get all info for filters of all nodes
            $modules_temp = db_get_all_rows_sql($sql);

            $rows_temp = db_get_all_rows_sql(
                'SELECT distinct name
				FROM tmodule_group
				ORDER BY name'
            );
            $rows_temp = io_safe_output($rows_temp);

            if (!empty($rows_temp)) {
                foreach ($rows_temp as $module_group_key => $modules_group_val) {
                    $rows_temp_processed[$modules_group_val['name']] = $modules_group_val['name'];
                }

                if (is_array($rows_select) && is_array($rows_temp_processed)) {
                    $rows_select = array_unique(array_merge($rows_select, $rows_temp_processed));
                }
            }

            $groups_temp = users_get_groups_for_select(false, 'AR', true, true, false);

            $groups_temp_processed = [];

            foreach ($groups_temp as $group_temp_key => $group_temp_val) {
                $new_key = str_replace('&nbsp;', '', $group_temp_val);
                $groups_temp_processed[$new_key] = $group_temp_val;
            }

            if (!empty($groups_temp_processed)) {
                if (is_array($rows_select) && is_array($rows_temp_processed)) {
                    $groups_select = array_unique(array_merge($groups_select, $groups_temp_processed));
                }
            }

            if (!empty($modules_temp)) {
                $modules = array_merge($modules, $modules_temp);
            }

            metaconsole_restore_db();
        }

        unset($groups_select[__('All')]);
        if (is_array($groups_select)) {
            $key_group_all = array_search(__('All'), $groups_select);
        } else {
            $key_group_all = false;
        }

        if ($key_group_all !== false) {
            unset($groups_select[$key_group_all]);
        }

        return $modules;
    }
}


function modules_get_agentmodule_mininterval($id_agent)
{
    $sql = sprintf('SELECT min(current_interval) min_interval from tagente_estado where id_agente = %d', $id_agent);
    return db_get_row_sql($sql);
}


function modules_get_agentmodule_mininterval_no_async($id_agent)
{
    $sql = 'SELECT COUNT(tae.current_interval) AS num_interval, MIN(tae.current_interval) AS min_interval
					FROM tagente_estado tae
					INNER JOIN tagente_modulo tam ON tae.id_agente_modulo = tam.id_agente_modulo
					INNER JOIN ttipo_modulo ttm ON tam.id_tipo_modulo = ttm.id_tipo where ttm.nombre not like "async%" and tae.id_agente = '.$id_agent.' and tam.disabled = 0 and tae.current_interval != 0';

    return db_get_row_sql($sql);
}


function get_same_modules($agents, $modules)
{
    $modules_to_report = [];
    if ($modules != '') {
        foreach ($modules as $m) {
            $module_name = modules_get_agentmodule_name($m);
            foreach ($agents as $a) {
                $module_in_agent = db_get_value_filter(
                    'id_agente_modulo',
                    'tagente_modulo',
                    [
                        'id_agente' => $a,
                        'nombre'    => $module_name,
                    ]
                );
                if ($module_in_agent) {
                    $modules_to_report[] = $module_in_agent;
                }
            }
        }
    }

    $modules_to_report = array_merge($modules_to_report, $modules);
    $modules_to_report = array_unique($modules_to_report);

    return $modules_to_report;
}


function get_hierachy_modules_tree($modules)
{
    $new_modules = [];

    $new_modules_root = array_filter(
        $modules,
        function ($module) {
            return (isset($module['parent_module_id']) && ($module['parent_module_id'] == 0));
        }
    );

    $new_modules_child = array_filter(
        $modules,
        function ($module) {
            return (isset($module['parent_module_id']) && ($module['parent_module_id'] != 0));
        }
    );

    while (!empty($new_modules_child)) {
        foreach ($new_modules_child as $i => $child) {
            recursive_modules_tree($new_modules_root, $new_modules_child, $i, $child);
        }
    }

    return $new_modules_root;
}


function recursive_modules_tree(&$new_modules, &$new_modules_child, $i, $child)
{
    foreach ($new_modules as $index => $module) {
        if ($module['id_agente_modulo'] == $child['parent_module_id']) {
            $new_modules[$index]['child'][] = $child;
            $new_modules[$index]['have_childs'] = true;
            unset($new_modules_child[$i]);
            break;
        } else if (isset($new_modules[$index]['child'])) {
            recursive_modules_tree($new_modules[$index]['child'], $new_modules_child, $i, $child);
        }
    }
}


function get_dt_from_modules_tree($modules)
{
    $final_modules = [];

    foreach ($modules as $i => $module) {
        $final_modules[$module['id_agente_modulo']] = $module;
        $final_modules[$module['id_agente_modulo']]['deep'] = 0;
        if (isset($modules[$i]['child'])) {
            recursive_get_dt_from_modules_tree($final_modules, $modules[$i]['child'], $final_modules[$module['id_agente_modulo']]['deep']);
        }

        unset($modules[$i]);
    }

    return $final_modules;
}


function recursive_get_dt_from_modules_tree(&$f_modules, $modules, $deep)
{
    foreach ($modules as $i => $module) {
        $f_modules[$module['id_agente_modulo']] = $module;
        $f_modules[$module['id_agente_modulo']]['deep'] = ($deep + 1);
        if (isset($modules[$i]['child'])) {
            recursive_get_dt_from_modules_tree($f_modules, $modules[$i]['child'], $f_modules[$module['id_agente_modulo']]['deep']);
        }
    }
}


/**
 * @brief Get the button with the link to open realtime stats into a new window
 *        Only to native (not satellite discovered) snmp modules.
 *
 * @param  array With all the module info
 * @return string All the HTML code to paint the button
 */
function get_module_realtime_link_graph($module)
{
    global $config;

    // Sometimes some parameters are renamed
    if (!isset($module['id_tipo_modulo'])) {
        $module['id_tipo_modulo'] = $module['module_type'];
    }

    if (!isset($module['nombre'])) {
        $module['nombre'] = $module['module_name'];
    }

    // Avoid to show on metaconsole
    if (is_metaconsole()) {
        return '';
    }

    // Realtime graph is an extension and it should be enabled
    if (!extensions_is_enabled_extension('realtime_graphs.php')) {
        return '';
    }

    // Only to remote_snmp, remote_snmp_proc. snmp_snmp_inc
    if ($module['id_tipo_modulo'] != 15 && $module['id_tipo_modulo'] != 16 && $module['id_tipo_modulo'] != 18) {
        return '';
    }

    // Only version 1, 2 and 2c
    if ($module['tcp_send'] != '1' && $module['tcp_send'] != '2' && $module['tcp_send'] != '2c') {
        return '';
    }

    $params = [
        'graph'          => 'snmp_module',
        'agent_alias'    => urlencode(modules_get_agentmodule_agent_alias($module['id_agente_modulo'])),
        'module_name'    => urlencode($module['nombre']),
        'snmp_address'   => $module['ip_target'],
        'snmp_community' => $module['snmp_community'],
        'snmp_oid'       => $module['snmp_oid'],
        'snmp_ver'       => $module['tcp_send'],
        'hide_header'    => 1,
        'rel_path'       => '../../',
    ];
    // Incremental type
    if ($module['id_tipo_modulo'] == 16) {
        $params['incremental'] = 1;
    }

    $link = 'operation/agentes/realtime_win.php?';
    foreach ($params as $p_key => $p_value) {
        $link .= "$p_key=".urlencode(io_safe_output($p_value)).'&';
    }

    $link = substr($link, 0, -1);

    $win_handle = 'realtime_'.dechex(crc32($module['id_agente_modulo'].$module['nombre']));

    $link_button = '<a href="javascript:winopeng_var(\''.$link.'\',\''.$win_handle.'\', 850, 480)">'.html_print_image(
        'images/realtime_shortcut.png',
        true,
        [
            'border' => '0',
            'alt'    => '',
            'title'  => __('Realtime SNMP graph'),
        ]
    ).'</a>';

    return $link_button;
}


/**
 * @brief Force a module to adopt a determinated status.
 *         WARNING: Only use this function to modules that changes their status
 *             with some user action through the console
 * @param int New status
 * @param int Agent module to force new status
 * @param int Agent id to force state recalculations
 */
function force_set_module_status($status, $id_agent_module, $id_agent)
{
    // Force recalculate counters
    db_process_sql_update(
        'tagente',
        ['update_module_count' => 1],
        ['id_agente' => $id_agent]
    );
    return db_process_sql_update(
        'tagente_estado',
        [
            'estado'            => $status,
            'known_status'      => $status,
            'last_known_status' => $status,
        ],
        ['id_agente_modulo' => $id_agent_module]
    );
}


function modules_get_modules_status($mod_status_id)
{
    $diferent_types = get_priorities();

    $mod_status_desc = '';
    switch ($mod_status_id) {
        case AGENT_MODULE_STATUS_NORMAL:
            $mod_status_desc = __('NORMAL');
        break;

        case AGENT_MODULE_STATUS_CRITICAL_BAD:
            $mod_status_desc = __('CRITICAL');
        break;

        case AGENT_MODULE_STATUS_WARNING:
            $mod_status_desc = __('WARNING');
        break;

        case AGENT_MODULE_STATUS_UNKNOWN:
            $mod_status_desc = __('UNKNOWN');
        break;

        case AGENT_MODULE_STATUS_NOT_INIT:
            $mod_status_desc = __('NOT INIT');
        break;

        case AGENT_MODULE_STATUS_ALL:
            $mod_status_desc = __('ALL');
        break;

        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
            $mod_status_desc = __('CRITICAL');
        break;

        case AGENT_MODULE_STATUS_NO_DATA:
            $mod_status_desc = __('NO DATA');
        break;

        case AGENT_MODULE_STATUS_NORMAL_ALERT:
            $mod_status_desc = __('NORMAL');
        break;

        case AGENT_MODULE_STATUS_NOT_NORMAL:
            $mod_status_desc = __('NOT NORMAL');
        break;

        case AGENT_MODULE_STATUS_WARNING_ALERT:
            $mod_status_desc = __('WARNING');
        break;

        default:
            if (isset($config['text_char_long'])) {
                foreach ($diferent_types as $key => $type) {
                    if ($key == $mod_status_id) {
                        $mod_status_desc = ui_print_truncate_text(
                            $type,
                            $config['text_char_long'],
                            false,
                            true,
                            false
                        );
                    }
                }
            }
        break;
    }

    return $mod_status_desc;
}


function modules_get_counter_by_states($state)
{
    switch ($state) {
        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return 'critical_count';

        case AGENT_MODULE_STATUS_WARNING_ALERT:
        case AGENT_MODULE_STATUS_WARNING:
        return 'warning_count';

            break;
        case AGENT_MODULE_STATUS_UNKNOWN:
        return 'unknown_count';

        case AGENT_MODULE_STATUS_NO_DATA:
        case AGENT_MODULE_STATUS_NOT_INIT:
        return 'notinit_count';

        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        case AGENT_MODULE_STATUS_NORMAL:
        return 'normal_count';
    }

    // If the state is not an expected state, return condition
    // to not show any data
    return false;
}


function modules_get_state_condition($state, $prefix='tae')
{
    switch ($state) {
        case AGENT_MODULE_STATUS_CRITICAL_ALERT:
        case AGENT_MODULE_STATUS_CRITICAL_BAD:
        return "(
				$prefix.estado = ".AGENT_MODULE_STATUS_CRITICAL_ALERT."
				OR $prefix.estado = ".AGENT_MODULE_STATUS_CRITICAL_BAD.'
			)';

        case AGENT_MODULE_STATUS_WARNING_ALERT:
        case AGENT_MODULE_STATUS_WARNING:
        return "(
				$prefix.estado = ".AGENT_MODULE_STATUS_WARNING_ALERT."
				OR $prefix.estado = ".AGENT_MODULE_STATUS_WARNING.'
			)';

        case AGENT_MODULE_STATUS_UNKNOWN:
        return "$prefix.estado = ".AGENT_MODULE_STATUS_UNKNOWN.' ';

        case AGENT_MODULE_STATUS_NO_DATA:
        case AGENT_MODULE_STATUS_NOT_INIT:
        return "(
				$prefix.estado = ".AGENT_MODULE_STATUS_NO_DATA."
				OR $prefix.estado = ".AGENT_MODULE_STATUS_NOT_INIT.'
			)';

        case AGENT_MODULE_STATUS_NORMAL_ALERT:
        case AGENT_MODULE_STATUS_NORMAL:
        return "(
				$prefix.estado = ".AGENT_MODULE_STATUS_NORMAL_ALERT."
				OR $prefix.estado = ".AGENT_MODULE_STATUS_NORMAL.'
			)';

        case AGENT_MODULE_STATUS_NOT_NORMAL:
        return "(
				$prefix.estado <> ".AGENT_MODULE_STATUS_NORMAL."
				AND $prefix.estado <> ".AGENT_MODULE_STATUS_NORMAL_ALERT.'
			)';
    }

    // If the state is not an expected state, return no condition
    return '1=1';
}
