<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package Include
 * @subpackage Modules
 */

/**
 * Include modules functions
 */
require_once ('include/functions_modules.php');

/**
 * Get a list of network components.
 * 
 * @param int Module type id of the requested components.
 * @param mixed Aditional filters to the components. It can be an indexed array
 * (keys would be the field name and value the expected value, and would be
 * joined with an AND operator). Examples:
<code>
$components = get_network_components ($id_module, array ('id_module_group', 10));
$components = get_network_components ($id_module, 'id_module_group = 10'));
</code>
 * @param mixed Fields to retrieve on each component.
 * 
 * @return array A list of network components matching. Empty array is returned
 * if none matches.
 */
function get_network_components ($id_module, $filter = false, $fields = false) {
	if (! is_array ($filter))
		$filter = array ();
	if (! empty ($id_module))
		$filter['id_modulo'] = (int) $id_module;
	
	$components = get_db_all_rows_filter ('tnetwork_component',
		$filter, $fields);
	if ($components === false)
		return array ();
	return $components;
}


/**
 * Get the name of a network components group.
 * 
 * @param int Network components group id.
 * 
 * @return string The name of the components group. 
 */
function get_network_component_group_name ($id_network_component_group) {
	if (empty ($id_network_component_group))
		return false;
	
	return @get_db_value ('name', 'tnetwork_component_group', 'id_sg', $id_network_component_group);
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
function get_network_component_group ($id_network_component_group, $filter = false, $fields = false) {
	if (empty ($id_network_component_group))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_sg'] = (int) $id_network_component_group;
	
	return get_db_row_filter ('tnetwork_component_group', $filter, $fields);
}

/**
 * Get a list of network component groups.
 * 
 * The values returned can be passed directly to print_select(). Child groups
 * are indented, so ordering on print_select() is NOT recommendable.
 * 
 * @param int id_module_components If provided, groups must have at least one component
 * of the module provided. Parents will be included in that case even if they don't have
 * components directly.
 * 
 * @param bool localComponent expecial comportation for local component.
 *
 * @return array An ordered list of component groups with childs indented.
 */
function get_network_component_groups ($id_module_components = 0, $localComponent = false) {
	/* Special vars to keep track of indentation level */
	static $level = 0;
	static $id_parent = 0;
	
	$groups = get_db_all_rows_filter ('tnetwork_component_group',
		array ('parent' => $id_parent),
		array ('id_sg', 'name'));
	if ($groups === false)
		return array ();
	
	$retval = array ();
	/* Magic indentation is here */
	$prefix = str_repeat ('&nbsp;', $level * 3);
	foreach ($groups as $group) {
		$level++;
		$tmp = $id_parent;
		$id_parent = (int) $group['id_sg'];
		$childs = get_network_component_groups ($id_module_components, $localComponent);
		$id_parent = $tmp;
		$level--;
		
		if ($localComponent) {
			if (! empty ($childs)) {
				$retval[$group['id_sg']] = $prefix.$group['name'];
				$retval = $retval + $childs;
			}
			else {
				$count = get_db_value_filter ('COUNT(*)', 'tlocal_component',
					array ('id_network_component_group' => (int) $group['id_sg']));
				
				if ($count > 0)
					$retval[$group['id_sg']] = $prefix.$group['name'];
			}
		}
		else {
			
			if (! empty ($childs) || $id_module_components == 0) {
				$retval[$group['id_sg']] = $prefix.$group['name'];
				$retval = $retval + $childs;
			}
			else {
				/* If components id module is provided, only groups with components
				that belongs to this id module are returned */
				if ($id_module_components) {
					$count = get_db_value_filter ('COUNT(*)', 'tnetwork_component',
						array ('id_group' => (int) $group['id_sg'],
							'id_modulo' => $id_module_components));
					if ($count > 0)
						$retval[$group['id_sg']] = $prefix.$group['name'];
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
function get_network_component ($id_network_component, $filter = false, $fields = false) {
	if (empty ($id_network_component))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter['id_nc'] = (int) $id_network_component;
	
	return get_db_row_filter ('tnetwork_component', $filter, $fields);
}

/**
 * Creates a network component.
 * 
 * @param string Component name.
 * @param string Component type.
 * @param string Component group id.
 * @param array Extra values to be set.
 *
 * @return int New component id. False on error.
 */
function create_network_component ($name, $type, $id_group, $values = false) {
	if (empty ($name))
		return false;
	if (empty ($type))
		return false;
	if (! is_array ($values))
		$values = array ();
	$values['name'] = $name;
	$values['type'] = (int) $type;
	$values['id_group'] = (int) $id_group;
	
	return @process_sql_insert ('tnetwork_component',
		$values);
}

/**
 * Updates a network component.
 * 
 * @param int Component id.
 * @param array Values to be set.
 *
 * @return bool True if updated. False on error.
 */
function update_network_component ($id_network_component, $values = false) {
	if (empty ($id_network_component))
		return false;
	$component = get_network_component ($id_network_component);
	if (empty ($component))
		return false;
	if (! is_array ($values))
		return false;
	
	return (@process_sql_update ('tnetwork_component',
		$values,
		array ('id_nc' => (int) $id_network_component)) !== false);
}

/**
 * Deletes a network component.
 * 
 * @param int Component id.
 * @param array Extra filter.
 *
 * @return bool True if deleted. False on error.
 */
function delete_network_component ($id_network_component) {
	if (empty ($id_network_component))
		return false;
	$filter = array ();
	$filter['id_nc'] = $id_network_component;
	
	@process_sql_delete ('tnetwork_profile_component', $filter);
	
	return (@process_sql_delete ('tnetwork_component', $filter) !== false);
}


/**
 * Creates a module in an agent from a network component.
 * 
 * @param int Component id to be created.
 * @param int Agent id to create module in.
 *
 * @return array New agent module id if created. False if could not be created
 */
function create_agent_module_from_network_component ($id_network_component, $id_agent) {
	if (! user_access_to_agent ($id_agent, 'AW'))
		return false;
	$component = get_network_component ($id_network_component,
		false,
		array ('name',
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
			'history_data',
			'min_warning',
			'max_warning',
			'min_critical',
			'max_critical',
			'min_ff_event'));
	if (empty ($component))
		return false;
	$values = $component;
	$len = count ($values) / 2;
	for ($i = 0; $i < $len; $i++)
		unset ($values[$i]);
	$name = $values['name'];
	unset ($values['name']);
	$values['ip_target'] = get_agent_address ($id_agent);
	
	return create_agent_module ($id_agent, $name, $values);
}

/**
 * Get the name of a network component.
 *
 * @param int Component id to get.
 *
 * @return Component name with the given id. False if not available or readable.
 */
function get_network_component_name ($id_network_component) {
	if (empty ($id_network_component))
		return false;
	return @get_db_value ('name', 'tnetwork_component', 'id', $id_network_component);
}

/**
 * Duplicate local compoment.
 * @param integer id_local_component Id of localc component for duplicate.
 */
function duplicate_network_component ($id_local_component) {	
	$network = get_network_component ($id_local_component);
	
	if ($network === false)
		return false;
	
	$name = __('Copy of').' '.$network['name'];

    $networkCopy['description'] = $network['description'];
    $networkCopy['max'] = $network['max'];
    $networkCopy['min'] = $network['min'];
    $networkCopy['module_interval'] = $network['module_interval'];
    $networkCopy['tcp_port'] = $network['tcp_port'];
    $networkCopy['tcp_send'] = $network['tcp_send'];
    $networkCopy['tcp_rcv'] = $network['tcp_rcv'];
    $networkCopy['snmp_community'] = $network['snmp_community'];
    $networkCopy['snmp_oid'] = $network['snmp_oid'];
    $networkCopy['id_module_group'] = $network['id_module_group'];
    $networkCopy['id_modulo'] = $network['id_modulo'];
    $networkCopy['id_plugin'] = $network['id_plugin'];
    $networkCopy['plugin_user'] = $network['plugin_user'];
    $networkCopy['plugin_pass'] = $network['plugin_pass'];
    $networkCopy['plugin_parameter'] = $network['plugin_parameter'];
    $networkCopy['max_timeout'] = $network['max_timeout'];
    $networkCopy['history_data'] = $network['history_data'];
    $networkCopy['min_warning'] = $network['min_warning'];
    $networkCopy['max_warning'] = $network['max_warning'];
    $networkCopy['min_critical'] = $network['min_critical'];
    $networkCopy['max_critical'] = $network['max_critical'];
    $networkCopy['min_ff_event'] = $network['min_ff_event'];
	
	return create_network_component ($name, $network['type'], $network['id_group'], $networkCopy);
}
?>
