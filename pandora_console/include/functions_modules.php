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
	if (! is_array ($values) || empty ($values))
		return false;
	if (isset ($values['nombre']) && empty ($values['nombre']))
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

/**
 * Gets all the agents that have a module with a name given.
 *
 * @param string Module name.
 * @param int Group id of the agents. False will be any group.
 * @param array Extra filter.
 * @param mixed Fields to be returned. All agents field by default
 *
 * @return array All the agents which have a module with the name given.
 */
function get_agents_with_module_name ($module_name, $id_group, $filter = false, $fields = 'tagente.*') {
	if (empty ($module_name))
		return false;
	if (! is_array ($filter))
		$filter = array ();
	$filter[] = 'tagente_modulo.id_agente = tagente.id_agente';
	$filter['`tagente_modulo`.nombre'] = $module_name;
	$filter['`tagente`.id_agente'] = array_keys (get_group_agents ($id_group, false, "none"));
	
	return get_db_all_rows_filter ('tagente, tagente_modulo',
		$filter, $fields);
}
?>
