<?php 

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/**
 * Copy a module defined in an agent to other agent.
 * 
 * This function avoid duplicated by comparing module names.
 * 
 * @param int Source agent module id.
 * @param int Detiny agent id.
 *
 * @return New agent module id on success. Existing module id if it already exists.
 * False on error.
 */
function copy_agent_module_to_agent ($id_agent_module, $id_destiny_agent) {
	$module = get_agentmodule ($id_agent_module);
	if ($module === false)
		return false;
	
	$modules = get_agent_modules ($id_destiny_agent, false,
		array ('nombre' => $module['nombre']));
	
	if (! empty ($modules))
		return array_pop (array_keys ($modules));
	
	/* PHP copy arrays on assignment */
	$new_module = $module;
	
	/* Rewrite different values */
	$new_module['id_agente'] = $id_destiny_agent;
	$new_module['ip_target'] = get_agent_address ($id_destiny_agent);
	
	/* Unset numeric indexes or SQL would fail */
	$len = count ($new_module) / 2;
	for ($i = 0; $i < $len; $i++)
		unset ($new_module[$i]);
	/* Unset original agent module id */
	unset ($new_module['id_agente_modulo']);
	
	$id_new_module = process_sql_insert ('tagente_modulo', $new_module);
	if ($id_new_module === false) {
		return false;
	}
	
	$values = array ();
	$values['id_agente_modulo'] = $id_new_module;
	$values['id_agente'] = $id_destiny_agent;
	
	if (! in_array ($new_module['id_tipo_modulo'], array (2, 6, 9, 18, 21, 100)))
		/* Not proc modules uses a special estado (status) value */
		$values['estado'] = 100;
	
	$result = process_sql_insert ('tagente_estado', $values);
	if ($result === false)
		return false;
	
	return $id_new_module;
}

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
	if (empty ($id_module))
		return array ();
	if (!is_array ($filter))
		$filter = array ();
	
	$filter['id_modulo'] = (int) $id_module;
	$components = get_db_all_rows_filter ('tnetwork_component',
		$filter, $fields);
	if ($components === false)
		return array ();
	return $components;
}

/**
 * Get a list of network component groups.
 * 
 * The values returned can be passed directly to print_select(). Child groups
 * are indented, so ordering on print_select() is NOT recommendable.
 * 
 * @param int If provided, groups must have at least one compoent of the module
 * provided. Parents will be included in that case even if they don't have
 * components directly.
 *
 * @return array An ordered list of component groups with childs indented.
 */
function get_network_component_groups ($id_module_components = 0) {
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
		$childs = get_network_component_groups ($id_module_components);
		$id_parent = $tmp;
		$level--;
		
		if (! empty ($childs) || $id_module_components == 0) {
			$retval[$group['id_sg']] = $prefix.$group['name'];
			$retval = $retval + $childs;
		} else {
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
 * Deletes a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 *
 * @return True if the module was deleted. False if not.
 */
function delete_agent_module ($id_agent_module) {
	$where = array ('id_agent_module' => $id_agent_module);
	
	process_sql_delete ('talert_template_modules', $where);
	process_sql_delete ('tgraph_source', $where);
	process_sql_delete ('treport_content', $where);
	process_sql_delete ('tevento', array ('id_agentmodule' => $id_agent_module));
	$where = array ('id_agente_modulo' => $id_agent_module);
	process_sql_delete ('tlayout_data', $where);
	process_sql_delete ('tagente_estado', $where);
	process_sql_update ('tagente_modulo',
		array ('delete_pending' => 1, 'disabled' => 1),
		$where);
	
	return true;
}

/**
 * Updates a module from an agent.
 *
 * @param mixed Agent module id to be deleted. Accepts an array with ids.
 * @param array Values to update.
 *
 * @return True if the module was updated. False if not.
 */
function update_agent_module ($id, $values) {
	if (! is_array ($values))
		return false;
	
	return (@process_sql_update ('tagente_modulo', $values,
		array ('id_agente_modulo' => (int) $id)) !== false);
}

/**
 * Creates a module in an agent.
 *
 * @param int Agent id.
 * @param int Module name id.
 * @param array Extra values for the module.
 *
 * @return New module id if the module was created. False if not.
 */
function create_agent_module ($id_agent, $name, $values = false) {
	if (empty ($id_agent) || ! user_access_to_agent ($id_agent, 'AW'))
		return false;
	if (empty ($name))
		return false;
	if (! is_array ($values))
		$values = array ();
	$values['nombre'] = $name;
	$values['id_agente'] = (int) $id_agent;
	
	$id_agent_module = process_sql_insert ('tagente_modulo', $values);
	
	if ($id_agent_module === false)
		return false;
	
	$result = process_sql_insert ('tagente_estado',
			array ('id_agente_modulo' => $id_agent_module,
				'datos' => 0,
				'timestamp' => '0000-00-00 00:00:00',
				'estado' => 0,
				'id_agente' => (int) $id_agent,
				'utimestamp' => 0,
				'status_changes' => 0,
				'last_status' => 0
			));
	
	if ($result === false) {
		process_sql_delete ('tagente_modulo',
			array ('id_agente_modulo' => $id_agent_module));
		return false;
	}
	
	return $id_agent_module;
}
?>
