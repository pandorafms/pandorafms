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
	else{
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
	if (empty($values)){
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
function tags_get_name($id){
	return db_get_value_filter ('name', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag description. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id){
		return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag url. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id){
		return db_get_value_filter('description', 'ttag', array('id_tag' => $id));
}

/**
 * Get tag's module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_count($id){
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
function tags_get_local_modules_count($id){
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
function tags_get_modules_tag_count($id){
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
function tags_get_policy_modules_count($id){
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
function tags_update_tag($values, $where){
	return db_process_sql_update ('ttag', $values, $where);
}

/**
 * Delete a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_delete_tag ($id_tag){
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
		
	if ($errn == 0){
			db_process_sql_commit();
			return true;
	}
	else{
			db_process_sql_rollback();
			return false;
	}

}

/**
 * Get tag's total count.  
 *
 * @return mixed Int with the tag's count.
 */
function tags_get_tag_count(){
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
function tags_insert_module_tag ($id_agent_module, $tags){
	$errn = 0;
	
	$values = array();
	
	if($tags == false) {
		$tags = array();
	}
	
	foreach ($tags as $tag){
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_agente_modulo'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_module', $values);
		if ($result_tag === false)
			$errn++;		
	}
	
/*	if ($errn > 0){
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
function tags_insert_policy_module_tag ($id_agent_module, $tags){
	$errn = 0;
	
	db_process_sql_begin();
	
	$values = array();
	foreach ($tags as $tag){
		//Protect against default insert
		if (empty($tag))
			continue;
		
		$values['id_tag'] = $tag;
		$values['id_policy_module'] = $id_agent_module;
		$result_tag = db_process_sql_insert('ttag_policy_module', $values, false);
		if ($result_tag === false)
			$errn++;		
	}

	if ($errn > 0){
		db_process_sql_rollback();
		return false;
	}
	else{
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
function tags_update_module_tag ($id_agent_module, $tags, $autocommit = false){
	$errn = 0;
	
	if (empty($tags))
		$tags = array();
	
	/* First delete module tag entries */
	$result_tag = db_process_sql_delete ('ttag_module', array('id_agente_modulo' => $id_agent_module));
	
	$values = array();
	foreach ($tags as $tag){
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
function tags_update_policy_module_tag ($id_policy_module, $tags, $autocommit = false){
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
function tags_get_module_tags ($id_agent_module){
	if (empty($id_agent_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_module', array('id_agente_modulo' => $id_agent_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag){
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
function tags_get_policy_module_tags ($id_policy_module){
	if (empty($id_policy_module))
		return false;
	
	$tags = db_get_all_rows_filter('ttag_policy_module', array('id_policy_module' => $id_policy_module), false);
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $tag){
		$return[] = $tag['id_tag'];
	}
	
	return $return;
}

/**
 * Select all tags.
 *
 * @return mixed Array with tags.
 */
function tags_get_all_tags () {
	$tags = db_get_all_fields_in_table('ttag', 'name');
	
	if ($tags === false)
		return false;
	
	$return = array();
	foreach ($tags as $id => $tag) {
		$return[$id] = $tag['name'];
	}
	
	return $return;
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
?>