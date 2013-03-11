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
 * @package Include
 * @subpackage TAGS
 */

 /**
 * Get critical agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with critical state
 * 
 * @return mixed Returns count of agents in critical status or false if they aren't.
 */
function tags_agent_critical ($id_tag) {
	
	return db_get_sql ("SELECT COUNT(*)
		FROM tagente, tagente_modulo, ttag_module 
		WHERE tagente.id_agente = tagente_modulo.id_agente
			AND tagente.disabled=0
			AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
			AND ttag_module.id_tag = $id_tag
			AND critical_count>0");
}

 /**
 * Get unknown agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with unknown state
 * 
 * @return mixed Returns count of agents in unknown status or false if they aren't.
 */
function tags_agent_unknown ($id_tag) {
	
	return db_get_sql ("SELECT COUNT(*)
		FROM tagente, tagente_modulo, ttag_module 
		WHERE tagente.id_agente = tagente_modulo.id_agente
			AND tagente.disabled=0
			AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
			AND ttag_module.id_tag = $id_tag
			AND critical_count=0 AND warning_count=0 AND unknown_count>0");
}

/**
 * Get total agents filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search total agents
 * 
 * @return mixed Returns count of agents with this tag or false if they aren't.
 */
function tags_total_agents ($id_tag) {
	
	// Avoid mysql error
	if (empty($id_tag))
		return;
	
	$total_agents = "SELECT COUNT(DISTINCT tagente.id_agente) 
		FROM tagente, tagente_modulo, ttag_module 
		WHERE tagente.id_agente = tagente_modulo.id_agente
			AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
			AND ttag_module.id_tag = " . $id_tag;
	
	return db_get_sql ($total_agents);	
}

 /**
 * Get normal agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with normal state
 * 
 * @return mixed Returns count of agents in normal status or false if they aren't.
 */
function tags_agent_ok ($id_tag) {
	
	return db_get_sql ("SELECT COUNT(*)
		FROM tagente, tagente_modulo, ttag_module 
		WHERE tagente.id_agente = tagente_modulo.id_agente
			AND tagente.disabled=0
			AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
			AND ttag_module.id_tag = $id_tag
			AND normal_count=total_count");
}

 /**
 * Get warning agents by using the status code in modules by filtering by id_tag.
 * 
 * @param int $id_tag Id of the tag to search module with warning state
 * 
 * @return mixed Returns count of agents in warning status or false if they aren't.
 */
function tags_agent_warning ($id_tag) {
	 
	return db_get_sql ("SELECT COUNT(*)
		FROM tagente, tagente_modulo, ttag_module 
		WHERE tagente.id_agente = tagente_modulo.id_agente
			AND tagente.disabled=0
			AND tagente_modulo.id_agente_modulo = ttag_module.id_agente_modulo
			AND ttag_module.id_tag = $id_tag
			AND critical_count=0 AND warning_count>0");
}
 
 /**
 * Find a tag searching by tag's or description name. 
 * 
 * @param string $tag_name_description Name or description of the tag that it's currently searched. 
 * @param array $filter Array with pagination parameters. 
 * @param bool $only_names Whether to return only names or all fields.
 * 
 * @return mixed Returns an array with the tag selected by name or false.
 */
function tags_search_tag ($tag_name_description = false, $filter = false, $only_names = false) {
	global $config;
	
	if ($tag_name_description) {
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%") OR 
						(description COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%"))';
				break;
			case "postgresql":
				$sql = 'SELECT *
					FROM ttag
					WHERE ((name COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\') OR
						(description COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\'))';
				break;
			case "oracle":
				$sql = 'SELECT *
					FROM ttag
					WHERE (UPPER(name) LIKE UPPER (\'%'. $tag_name_description .'%\') OR
						UPPER(dbms_lob.substr(description, 4000, 1)) LIKE UPPER (\'%'. $tag_name_description .'%\'))';
				break;
		}
	}
	else {
		$sql = 'SELECT * FROM ttag';
	}
	if ($filter !== false) {
		switch ($config["dbtype"]) {
			case "mysql":
				$result = db_get_all_rows_sql ($sql . ' LIMIT ' . $filter['offset'] . ',' . $filter['limit']);
				break;
			case "postgresql":
				$result = db_get_all_rows_sql ($sql . ' OFFSET ' . $filter['offset'] . ' LIMIT ' . $filter['limit']);
				break;
			case "oracle":
				$result = oracle_recode_query ($sql, $filter, 'AND', false);
				if ($components != false) {
					for ($i=0; $i < count($components); $i++) {
						unset($result[$i]['rnum']);
					}
				}
				break;
		}
	}
	else {
		$result = db_get_all_rows_sql ($sql);
	}
	
	if ($result === false)
		$result = array();
	
	if ($only_names) {
		$result_tags = array();
		foreach ($result as $tag) {
			$result_tags[$tag['id_tag']] = $tag['name'];
		}
		$result = $result_tags;
	}
	
	return $result;
}

/**
 * Create a new tag. 
 * 
 * @param array $values Array with all values to insert. 
 *
 * @return mixed Tag id or false.
 */
function tags_create_tag($values) {
	if (empty($values)) {
		return false;
	}
	
	return db_process_sql_insert('ttag',$values);
}

/**
 * Search tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Array with the seleted tag or false.
 */
function tags_search_tag_id($id) {
	return db_get_row ('ttag', 'id_tag', $id);
}

/**
 * Get tag name. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag name or false.
 */
function tags_get_name($id) {
	return db_get_value_filter ('name', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag id given the tag name. 
 * 
 * @param string Tag name.
 *
 * @return int Tag id.
 */
function tags_get_id($name) {
	return db_get_value_filter ('id_tag', 'ttag', array('name' => $name));
}

/**
 * Get tag description. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id) {
	return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag url. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id) {
	return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag's module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));
	
	return $num_modules + $num_policy_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_local_modules_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_tag' => $id));

	return $num_modules;
}

/**
 * Get tag's local module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_tag_count($id) {
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', array('id_agente_modulo' => $id));
	
	return $num_modules;
}

/**
 * Get tag's policy module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_policy_modules_count($id) {
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', array('id_tag' => $id));
	
	return $num_policy_modules;
}



/**
 * Updates a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 * @param string $where Where clause to update record.
 *
 * @return bool True or false if something goes wrong.
 */
function tags_update_tag($values, $where) {
	return db_process_sql_update ('ttag', $values, $where);
}

/**
 * Delete a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_delete_tag ($id_tag) {
	$errn = 0;
	
	$result_tag = db_process_delete_temp ('ttag', 'id_tag', $id_tag);
	if ($result_tag === false)
		$errn++;
	
	$result_module = db_process_delete_temp ('ttag_module', 'id_tag', $id_tag);
	if ($result_module === false)
		$errn++;
	
	$result_policy = db_process_delete_temp ('ttag_policy_module', 'id_tag', $id_tag);
	if ($result_policy === false)
		$errn++;
	
	if ($errn == 0) {
		db_process_sql_commit();
		return true;
	}
	else {
		db_process_sql_rollback();
		return false;
	}
	
}

/**
 * Get tag's total count.  
 *
 * @return mixed Int with the tag's count.
 */
function tags_get_tag_count() {
	return (int)db_get_value('count(*)', 'ttag');
}

/**
 * Inserts tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_module_tag ($id_agent_module, $tags) {
	$errn = 0;
	
	$values = array();
	
	if($tags == false) {
		$tags = array();
	}
	
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values);
		if ($result_tag === false)
			$errn++;
	}
	
/*	if ($errn > 0) {
		db_process_sql_rollback();
		return false;
	}
	else{
		db_process_sql_commit();
		return true;
	}*/
}

/**
 * Inserts tag's array of a policy module. 
 * 
 * @param int $id_agent_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_insert_policy_module_tag ($id_agent_module, $tags) {
	$errn = 0;
	
	db_process_sql_begin();
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
	if ($errn > 0) {
		db_process_sql_rollback();
		return false;
	}
	else {
		db_process_sql_commit();
		return true;
	}
}

/**
 * Updates tag's array of a module. 
 * 
 * @param int $id_agent_module Module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_module_tag ($id_agent_module, $tags, $autocommit = false) {
	$errn = 0;
	
	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_module', array('id_agente_modulo' => $id_agent_module));
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
}

/**
 * Updates tag's array of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 * @param array $tags Array with tags to associate to the module. 
 * @param bool $autocommit Whether to do automatical commit or not.
 * 
 * @return bool True or false if something goes wrong.
 */
function tags_update_policy_module_tag ($id_policy_module, $tags, $autocommit = false) {
	$errn = 0;
	
	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_policy_module', array('id_policy_module' => $id_policy_module));
	
	$values = array();
	foreach ($tags as $tag) {
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_policy_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;
	}
	
}

/**
 * Select all tags of a module. 
 * 
 * @param int $id_agent_module Module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_module_tags ($id_agent_module) {
	if (empty($id_agent_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_module', array('id_agente_modulo' => $id_agent_module), false);
	
	if ($tags === false)
		return array();
	
	$return = array();
	foreach ($tags as $tag) {
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags of a policy module. 
 * 
 * @param int $id_policy_module Policy module's id.
 *
 * @return mixed Array with module tags or false if something goes wrong.
 */
function tags_get_policy_module_tags ($id_policy_module) {
	if (empty($id_policy_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_policy_module', array('id_policy_module' => $id_policy_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag) {
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags.
 *
 * @return mixed Array with tags.
 */
function tags_get_all_tags ($return_url = false) {
	$tags = db_get_all_fields_in_table('ttag', 'name');
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag) {
		$return[$tag['id_tag']] = $tag['name'];
		if($return_url) {
			$return[$tag['id_tag']] .= ' '.$tag['url'];
		}
	}
	
	return $return;
}

/**
 * Get the tags required
 *
 * @return mixed Array with tags.
 */
function tags_get_tags ($ids) {
	$all_tags = tags_get_all_tags(true);
	
	$tags = array();
	foreach($ids as $id) {
		if(isset($all_tags[$id])) {
			$tags[$id] = $all_tags[$id];
		}
	}
	
	return $tags;
}

/**
 * Give format to tags when go concatened with url.
 *
 * @param string name of tags serialized
 * @param bool flag to return the url or not
 * 
 * @return string Tags with url format
 */
function tags_get_tags_formatted ($tags_array, $get_url = true) {
	if(!is_array($tags_array)) {
		$tags_array = explode(',',$tags_array);
	}
	
	$tags = array();
	foreach($tags_array as $t) {
		$tag_url = explode(' ', trim($t));
		$tag = $tag_url[0];
		if(isset($tag_url[1]) && $tag_url[1] != '' && $get_url) {
			$title = $tag_url[1];
			//$link = '<a href="'.$tag_url[1].'" target="_blank">'.html_print_image('images/zoom.png',true, array('alt' => $title, 'title' => $title)).'</a>';
			$link = '<a href="javascript: openURLTagWindow(\'' . $tag_url[1] . '\');">' . html_print_image('images/zoom.png', true, array('title' => __('Click here to open a popup window with URL tag'))) . '</a>';
		
		}
		else {
			$link = '';
		}
		
		$tags[] = $tag.$link;
	}
	
	$tags = implode(',',$tags);
	
	$tags = str_replace(',',' , ',$tags);
	
	return $tags;
}

/**
 * Get the tags (more restrictive) of an access flag in a group
 *
 * @param string id of the user
 * @param string id of the group
 * @param string access flag (AR,AW...)
 * @param string return_mode 
 * 			- 'data' for return array with groups and tags
 * 			- 'module_condition' for return string with sql condition for tagente_module
 * 			- 'event_condition' for return string with sql condition for tevento
 * 
 * @return mixed/string Tag ids
 */
 
function tags_get_acl_tags($id_user, $id_group, $access = 'AR', $return_mode = 'module_condition', $query_prefix = '', $query_table = '') {
	global $config;
	
	if($id_user == false) {
		$id_user = $config['id_user'];
	}
	
	if (is_user_admin ($id_user)) {
		switch($return_mode) {
			case 'data':
				return array();
				break;
			case 'event_condition':
			case 'module_condition':
				return "";
				break;
		}
	}
	
	if ((string)$id_group === "0") {
		$id_group = array_keys(users_get_groups($id_user, $access, false));
		
		if (empty($id_group)) {
			return ERR_WRONG_PARAMETERS;
		}
	}
	elseif (empty($id_group)) {
		return ERR_WRONG_PARAMETERS;
	}
	elseif (!is_array($id_group)) {
		$id_group = (array) $id_group;
	}
	
	$acl_column = get_acl_column($access);
	
	if (empty($acl_column)) {
		return ERR_WRONG_PARAMETERS;
	}
	
	$query = sprintf("SELECT tags, id_grupo 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
				tusuario_perfil.id_usuario = '%s' AND 
				tperfil.%s = 1 AND
			(tusuario_perfil.id_grupo IN (%s) OR tusuario_perfil.id_grupo = 0)
			ORDER BY id_grupo", $id_user, $acl_column, implode(',',$id_group));
	$tags = db_get_all_rows_sql($query);
	
	// If not profiles returned, the user havent acl permissions
	if (empty($tags)) {
		return ERR_ACL;
	}
	
	// Array to store groups where there arent tags restriction
	$non_restriction_groups = array();
	
	$acltags = array();
	foreach ($tags as $tagsone) {
		if (empty($tagsone['tags'])) {
			// If there arent tags restriction in all groups (group 0), return no condition
			if ($tagsone['id_grupo'] == 0) {
				switch ($return_mode) {
					case 'data':
						return array();
						break;
					case 'event_condition':
					case 'module_condition':
						return "";
						break;
				}
			}
			
			$non_restriction_groups[] = $tagsone['id_grupo'];
			continue;
		}
		
		$tags_array = explode(',',$tagsone['tags']);
		
		if (!isset($acltags[$tagsone['id_grupo']])) {
			$acltags[$tagsone['id_grupo']] = $tags_array;
		}
		else {
			$acltags[$tagsone['id_grupo']] = array_unique(array_merge($acltags[$tagsone['id_grupo']], $tags_array));
		}
	}
	
	// Delete the groups without tag restrictions from the acl tags array
	foreach ($non_restriction_groups as $nrgroup) {
		if (isset($acltags[$nrgroup])) {
			unset($acltags[$nrgroup]);
		}
	}
	
	switch ($return_mode) {
		case 'data':
			// Stop here and return the array
			return $acltags;
			break;
		case 'module_condition':
			// Return the condition of the tags for tagente_modulo table
			$condition = tags_get_acl_tags_module_condition($acltags, $query_table);
			if (!empty($condition)) {
				return " $query_prefix ".$condition;
			}
			break;
		case 'event_condition':
			// Return the condition of the tags for tevento table
			$condition = tags_get_acl_tags_event_condition($acltags);
			
			if(!empty($condition)) {
				return " $query_prefix ".$condition;
			}
			break;
	}
	
	return "";
}

/**
 * Transform the acl_groups data into a SQL condition
 * 
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 * 
 * @return string SQL condition for tagente_module
 */
 
function tags_get_acl_tags_module_condition($acltags, $modules_table = '') {
	if (!empty($modules_table)) {
		$modules_table .= '.';
	}
	
	$condition = '';
	foreach ($acltags as $group_id => $group_tags) {
		if ($condition != '') {
			$condition .= ' OR ';
		}
		
		// Group condition (The module belongs to an agent of the group X)
		if (!array_key_exists(0, array_keys($acltags))) {
			$group_condition = sprintf('%sid_agente IN (SELECT id_agente FROM tagente WHERE id_grupo = %d)', $modules_table, $group_id);
		}
		else {
			//Avoid the user profiles with all group access.
			$group_condition = " 1 = 1 ";
		}
		// Tags condition (The module has at least one of the restricted tags)
		$tags_condition = sprintf('%sid_agente_modulo IN (SELECT id_agente_modulo FROM ttag_module WHERE id_tag IN (%s))', $modules_table, implode(',',$group_tags));
		
		$condition .= "($group_condition AND \n$tags_condition)\n";
	}
	
	//Avoid the user profiles with all group access.
	//if (!empty($condition)) {
	if (!empty($condition) &&
		!array_key_exists(0, array_keys($acltags))) {
		$condition = sprintf("\n((%s) OR %sid_agente NOT IN (SELECT id_agente FROM tagente WHERE id_grupo IN (%s)))", $condition, $modules_table, implode(',',array_keys($acltags)));
	}
	
	return $condition;
}

/**
 * Transform the acl_groups data into a SQL condition
 * 
 * @param mixed acl_groups data calculated in tags_get_acl_tags function
 * 
 * @return string SQL condition for tagente_module
 */
 
function tags_get_acl_tags_event_condition($acltags) {
	$condition = '';
	
	// Get all tags of the system
	$all_tags = tags_get_all_tags(false);
	
	foreach($acltags as $group_id => $group_tags) {
		// Group condition (The module belongs to an agent of the group X)
		$group_condition = sprintf('id_grupo = %d',$group_id);
		
		// Tags condition (The module has at least one of the restricted tags)
		$tags_condition = '';
		foreach($group_tags as $tag) {
			// If the tag ID doesnt exist, ignore
			if(!isset($all_tags[$tag])) {
				continue;
			}
			
			if($tags_condition != '') {
				$tags_condition .= " OR \n";
			}
			
			//~ // Add as condition all the posibilities of the serialized tags
			//~ $tags_condition .= sprintf('tags LIKE "%s,%%"',io_safe_input($all_tags[$tag]));
			//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s,%%"',io_safe_input($all_tags[$tag]));
			//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s"',io_safe_input($all_tags[$tag]));
			//~ $tags_condition .= sprintf(' OR tags LIKE "%s %%"',io_safe_input($all_tags[$tag]));
			//~ $tags_condition .= sprintf(' OR tags LIKE "%%,%s %%"',io_safe_input($all_tags[$tag]));
			
			$tags_condition .= sprintf('tags LIKE "%%%s%%"',io_safe_input($all_tags[$tag]));
		}
		
		// If there is not tag condition ignore
		if(empty($tags_condition)) {
			continue;
		}
		
		if($condition != '') {
			$condition .= ' OR ';
		}
		
		$condition .= "($group_condition AND \n($tags_condition))\n";
	}
	
	if(!empty($condition)) {
		$condition = sprintf("\n((%s) OR id_grupo NOT IN (%s))", $condition, implode(',',array_keys($acltags)));
	}

	return $condition;
}

/**
 * Check if a user has assigned acl tags or not (if is admin, is like not acl tags)
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * 
 * @return bool true if the user has tags and false if not
 */
function tags_has_user_acl_tags($id_user = false) {
	global $config;
	
	if($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	if(is_user_admin($id_user)) {
		return false;
	}
	
	$query = sprintf("SELECT count(*) 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
			tusuario_perfil.id_usuario = '%s' AND tags != ''", 
			$id_user);
			
	$user_tags = db_get_value_sql($query);
	
	return (bool)$user_tags;
}

/**
 * Get the tags of a user in an ACL flag
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * @param string Access flag where check what tags have the user
 * 
 * @return string SQL condition for tagente_module
 */
function tags_get_user_tags($id_user = false, $access = 'AR') {
	global $config;
	
	if($id_user === false) {
		$id_user = $config['id_user'];
	}
	
	// Get all tags to have the name of all of them
	$all_tags = tags_get_all_tags();
	
	// If at least one of the profiles of this access flag hasent
	// tags restrictions, the user can see all tags
	$acl_column = get_acl_column($access);
	
	if(empty($acl_column)) {
		return array();
	}
	
	$query = sprintf("SELECT count(*) 
			FROM tusuario_perfil, tperfil
			WHERE tperfil.id_perfil = tusuario_perfil.id_perfil AND
			tusuario_perfil.id_usuario = '%s' AND 
			tperfil.%s = 1 AND tags = ''", 
			$id_user, $acl_column);
			
	$profiles_without_tags = db_get_value_sql($query);
	
	if ($profiles_without_tags > 0) {
		return $all_tags;
	}
	
	// Get the tags of the required access flag for each group
	$tags = tags_get_acl_tags($id_user, 0, $access, 'data');
	
	// Merge the tags to get an array with all of them
	$user_tags_id = array();

	foreach($tags as $t) {
		if(empty($user_tags_id)) {
			$user_tags_id = $t;
		}
		else {
			$user_tags_id = array_unique(array_merge($t,$user_tags_id));
		}
	}

	// Set the format id=>name to tags
	$user_tags = array();
	foreach($user_tags_id as $id) {
		if(!isset($all_tags[$id])) {
			continue;
		}
		$user_tags[$id] = $all_tags[$id];
	}
	
	
	return $user_tags;
}

/**
 * Check the ACLs with tags
 * 
 * @param string ID of the user (with false the user will be taked from config)
 * @param string id of the group (0 means for at least one)
 * @param string access flag (AR,AW...)
 * @param mixed tags to be checked (array() means for at least one)
 * 
 * @return bool true if the acl check has success, false otherwise
 */
function tags_check_acl($id_user, $id_group, $access, $tags = array()) {
	global $config;
	
	if($id_user === false) {
		$id_user = $config['id_user'];
	}
		
	$acls = tags_get_acl_tags($id_user, $id_group, $access, 'data');
	
	// If there are wrong parameters or fail ACL check, return false
	if($acls === ERR_WRONG_PARAMETERS || $acls === ERR_ACL) {
		return false;
	}
	
	// If there are not tags restrictions or tags passed, return true
	if(empty($acls) || empty($tags)) {
		return true;
	}

	if($id_group > 0) {
		if(isset($acls[$id_group])) {
			foreach($tags as $tag) {
				$tag = tags_get_id($tag);

				if(in_array($tag, $acls[$id_group])) {
					return true;
				}
			}
		}
		else {
			return false;
		}
	}
	else {
		foreach($acls as $acl_tags) {
			foreach($tags as $tag) {
				$tag = tags_get_id($tag);
				if(in_array($tag, $acl_tags)) {
					return true;
				}
			}
		}
	}
	
	return false;
}
?>
