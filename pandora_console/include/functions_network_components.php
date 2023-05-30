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
 * @subpackage Network components
 */

global $config;

/*
 * Include modules functions
 */
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_users.php';


function network_components_is_disable_type_event($id=false, $type_event=false)
{
    if ($id === false) {
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
        db_get_value(
            'disabled_types_event',
            'tnetwork_component',
            'id_nc',
            $id
        ),
        true
    );

    if (isset($disabled_types_event[$type_event])) {
        if ($disabled_types_event[$type_event]) {
            return true;
        } else {
            return false;
        }
    }

    return true;
}


/**
 * Get a list of network components.
 *
 * @param int Module type id of the requested components.
 * @param mixed Aditional filters to the components. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator). Examples:
 * <code>
 * $components = network_components_get_network_components ($id_module, array ('id_module_group', 10));
 * $components = network_components_get_network_components ($id_module, 'id_module_group = 10'));
 * </code>
 * @param mixed Fields to retrieve on each component.
 *
 * @return array A list of network components matching. Empty array is returned
 * if none matches.
 */
function network_components_get_network_components($id_module, $filter=false, $fields=false)
{
    global $config;

    if (! is_array($filter)) {
        $filter = [];
    }

    if (! empty($id_module)) {
        $filter['id_modulo'] = (int) $id_module;
    }

    if (isset($filter['offset'])) {
        $offset = $filter['offset'];
        unset($filter['offset']);
    }

    if (isset($filter['limit'])) {
        $limit = $filter['limit'];
        unset($filter['limit']);
    }

    $sql = @db_get_all_rows_filter('tnetwork_component', $filter, $fields, 'AND', false, true);

    switch ($config['dbtype']) {
        case 'mysql':
            $limit_sql = '';
            if (isset($offset) && isset($limit)) {
                $limit_sql = " LIMIT $offset, $limit ";
            }

            $sql = sprintf('%s %s', $sql, $limit_sql);

            $components = db_get_all_rows_sql($sql);
        break;

        case 'postgresql':
            $limit_sql = '';
            if (isset($offset) && isset($limit)) {
                $limit_sql = " OFFSET $offset LIMIT $limit ";
            }

            $sql = sprintf('%s %s', $sql, $limit_sql);

            $components = db_get_all_rows_sql($sql);

        break;

        case 'oracle':
            $set = [];
            if (isset($offset) && isset($limit)) {
                $set['limit'] = $limit;
                $set['offset'] = $offset;
            }

            $components = oracle_recode_query($sql, $set, 'AND', false);

        break;
    }

    if ($components === false) {
        return [];
    }

    return $components;
}


/**
 * Get the name of a network components group.
 *
 * @param int Network components group id.
 *
 * @return string The name of the components group.
 */
function network_components_get_group_name($id_network_component_group)
{
    if (empty($id_network_component_group)) {
        return false;
    }

    return @db_get_value('name', 'tnetwork_component_group', 'id_sg', $id_network_component_group);
}


/**
 * Get a network component group.
 *
 * @param int Group id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A network component group matching id and filter.
 */
function network_components_get_group($id_network_component_group, $filter=false, $fields=false)
{
    if (empty($id_network_component_group)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_sg'] = (int) $id_network_component_group;

    return db_get_row_filter('tnetwork_component_group', $filter, $fields);
}


/**
 * Get a list of network component groups.
 *
 * The values returned can be passed directly to html_print_select(). Child groups
 * are indented, so ordering on html_print_select() is NOT recommendable.
 *
 * @param int id_module_components If provided, groups must have at least one component
 * of the module provided. Parents will be included in that case even if they don't have
 * components directly.
 *
 * @param bool localComponent expecial comportation for local component.
 *
 * @return array An ordered list of component groups with childs indented.
 */
function network_components_get_groups($id_module_components=0, $localComponent=false)
{
    // Special vars to keep track of indentation level
    static $level = 0;
    static $id_parent = 0;
    global $config;

    $groups = db_get_all_rows_filter(
        'tnetwork_component_group',
        ['parent' => $id_parent],
        [
            'id_sg',
            'name',
        ]
    );
    if ($groups === false) {
        return [];
    }

    $retval = [];
    // Magic indentation is here.
    $prefix = str_repeat('&nbsp;', ($level * 3));
    foreach ($groups as $group) {
        $level++;
        $tmp = $id_parent;
        $id_parent = (int) $group['id_sg'];
        $childs = network_components_get_groups(
            $id_module_components,
            $localComponent
        );
        $id_parent = $tmp;
        $level--;

        if ($localComponent) {
            if (! empty($childs)) {
                $retval[$group['id_sg']] = $prefix.$group['name'];
                $retval = ($retval + $childs);
            } else {
                $count = db_get_value_filter(
                    'COUNT(*)',
                    'tlocal_component',
                    ['id_network_component_group' => (int) $group['id_sg']]
                );

                if ($count > 0) {
                    $retval[$group['id_sg']] = $prefix.$group['name'];
                }
            }
        } else {
            if (! empty($childs) || $id_module_components == 0) {
                $retval[$group['id_sg']] = $prefix.$group['name'];
                $retval = ($retval + $childs);
            } else {
                /*
                    If components id module is provided, only groups with components
                that belongs to this id module are returned */
                if ($id_module_components) {
                    $count = db_get_value_filter(
                        'COUNT(*)',
                        'tnetwork_component',
                        [
                            'id_group'  => (int) $group['id_sg'],
                            'id_modulo' => $id_module_components,
                        ]
                    );

                    if ($count > 0) {
                        $retval[$group['id_sg']] = $prefix.$group['name'];
                    }
                }
            }
        }
    }

    return $retval;
}


/**
 * Get a network component.
 *
 * @param int Component id to be fetched.
 * @param array Extra filter.
 * @param array Fields to be fetched.
 *
 * @return array A network component matching id and filter.
 */
function network_components_get_network_component($id_network_component, $filter=false, $fields=false)
{
    if (empty($id_network_component)) {
        return false;
    }

    if (! is_array($filter)) {
        $filter = [];
    }

    $filter['id_nc'] = (int) $id_network_component;

    $network_component = db_get_row_filter('tnetwork_component', $filter, $fields);

    if (!empty($network_component) && $network_component['id_category'] != 0) {
        $network_component['category_name'] = (string) db_get_value('name', 'tcategory', 'id', $network_component['id_category']);
    }

    return $network_component;
}


/**
 * Creates a network component.
 *
 * @param string Component name.
 * @param string Component type.
 * @param string Component group id.
 * @param array Extra values to be set.
 *
 * @return integer New component id. False on error.
 */
function network_components_create_network_component($name, $type, $id_group, $values=false)
{
    global $config;

    switch ($config['dbtype']) {
        case 'oracle':
            switch ($type) {
                case 8:
                case 9:
                case 10:
                case 11:
                case 12:
                    if (empty($values['tcp_rcv'])) {
                        $values['tcp_rcv'] = ' ';
                    }
                break;

                default:
                break;
            }
        break;
    }

    if (empty($name)) {
        return false;
    }

    if (empty($type)) {
        return false;
    }

    if (! is_array($values)) {
        $values = [];
    }

    $values['name'] = $name;
    $values['type'] = (int) $type;
    $values['id_group'] = (int) $id_group;

    return @db_process_sql_insert(
        'tnetwork_component',
        $values
    );
}


/**
 * Updates a network component.
 *
 * @param int Component id.
 * @param array Values to be set.
 *
 * @return boolean True if updated. False on error.
 */
function network_components_update_network_component($id_network_component, $values=false)
{
    if (empty($id_network_component)) {
        return false;
    }

    $component = network_components_get_network_component($id_network_component);
    if (empty($component)) {
        return false;
    }

    if (! is_array($values)) {
        return false;
    }

    return (@db_process_sql_update(
        'tnetwork_component',
        $values,
        ['id_nc' => (int) $id_network_component]
    ) !== false);
}


/**
 * Deletes a network component.
 *
 * @param int Component id.
 * @param array Extra filter.
 *
 * @return boolean True if deleted. False on error.
 */
function network_components_delete_network_component($id_network_component)
{
    if (empty($id_network_component)) {
        return false;
    }

    $filter = [];
    $filter['id_nc'] = $id_network_component;

    @db_process_sql_delete('tnetwork_profile_component', $filter);

    return (@db_process_sql_delete('tnetwork_component', $filter) !== false);
}


/**
 * Creates a module in an agent from a network component.
 *
 * @param int Component id to be created.
 * @param int Agent id to create module in.
 *
 * @return array New agent module id if created. False if could not be created
 */
function network_components_create_module_from_network_component($id_network_component, $id_agent)
{
    if (! users_access_to_agent($id_agent, 'AW')) {
        return false;
    }

    $component = network_components_get_network_component(
        $id_network_component,
        false,
        [
            'name',
            'description AS descripcion',
            'type AS id_tipo_modulo',
            'max',
            'min',
            'module_interval',
            'tcp_port',
            'tcp_send',
            'tcp_rcv',
            'snmp_community',
            'snmp_oid',
            'id_module_group',
            'id_modulo',
            'plugin_user',
            'plugin_pass',
            'plugin_parameter',
            'max_timeout',
            'max_retries',
            'history_data',
            'dynamic_interval',
            'dynamic_min',
            'dynamic_max',
            'dynamic_two_tailed',
            'min_warning',
            'max_warning',
            'str_warning',
            'min_critical',
            'max_critical',
            'str_critical',
            'min_ff_event',
            'critical_inverse',
            'warning_inverse',
            'percentage_warning',
            'percentage_critical',
            'module_critical_instructions',
            'module_warning_instructions',
            'module_unknown_instructions',
        ]
    );
    if (empty($component)) {
        return false;
    }

    $values = $component;
    $len = (count($values) / 2);
    for ($i = 0; $i < $len; $i++) {
        unset($values[$i]);
    }

    $name = $values['name'];
    unset($values['name']);
    $values['ip_target'] = agents_get_address($id_agent);

    return modules_create_agent_module($id_agent, $name, $values);
}


/**
 * Get the name of a network component.
 *
 * @param int Component id to get.
 *
 * @return Component name with the given id. False if not available or readable.
 */
function network_components_get_name($id_network_component)
{
    if (empty($id_network_component)) {
        return false;
    }

    return @db_get_value('name', 'tnetwork_component', 'id_nc', $id_network_component);
}


/**
 * Duplicate local compoment.
 *
 * @param integer id_local_component Id of localc component for duplicate.
 */
function network_components_duplicate_network_component($id_local_component)
{
    $network = network_components_get_network_component($id_local_component);

    if ($network === false) {
        return false;
    }

    $name = io_safe_input(__('Copy of').' ').$network['name'];
    unset($network['id_nc']);
    unset($network['name']);
    unset($network['category_name']);

    return network_components_create_network_component($name, $network['type'], $network['id_group'], $network);
}
