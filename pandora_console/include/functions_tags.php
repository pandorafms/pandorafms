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
 *
 * @return mixed Returns an array with the tag selected by name or false.
 */
function tags_search_tag ($tag_name_description = false) {
	global $config;
	
	if ($tag_name_description){
		switch ($config["dbtype"]) {
			case "mysql":
						$sql = 'SELECT * FROM ttag WHERE ((name COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%") OR 
								(description COLLATE utf8_general_ci LIKE "%'. $tag_name_description .'%"))';
				break;
			case "postgresql":
						$sql = 'SELECT * FROM ttag WHERE ((name COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\') OR
								(description COLLATE utf8_general_ci LIKE \'%'. $tag_name_description .'%\'))';
				break;
			case "oracle":
						$sql = 'SELECT * FROM ttag WHERE (UPPER(name) LIKE UPPER (\'%'. $tag_name_description .'%\') OR
						UPPER(description) LIKE UPPER (\'%'. $tag_name_description .'%\'))';
				break;
		}
	}
	else{
		$sql = 'SELECT * FROM ttag';
	}
	$result = db_get_all_rows_sql ($sql);

	if ($result === false)
		return array (); //Return an empty array
	else 
		return $result;	
}

/**
 * Create a new tag. 
 * 
 * @param array $values Array with all values to insert. 
 *
 * @return mixed Tag id or false.
 */
function tags_create_tag($values){
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
function tags_search_tag_id($id){
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
	return db_get_value_filter ('name', 'ttag', 'id_tag', $id);
}

/**
 * Get tag description. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag description or false.
 */
function tags_get_description($id){
		return db_get_value_filter('description', 'ttag', 'id_tag', $id);
}

/**
 * Get tag url. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed String with tag url or false.
 */
function tags_get_url($id){
		return db_get_value_filter('description', 'ttag', 'id_tag', $id);
}

/**
 * Get tag's module count. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return mixed Int with the tag's count or false.
 */
function tags_get_modules_count($id){
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', 'id_tag', $id);
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', 'id_tag', $id);

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
	$num_modules = (int)db_get_value_filter('count(*)', 'ttag_module', 'id_tag', $id);

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
	$num_policy_modules = (int)db_get_value_filter('count(*)', 'ttag_policy_module', 'id_tag', $id);

	return $num_policy_modules;
}



/**
 * Updates a tag by id. 
 * 
 * @param array $id Int with tag id info. 
 *
 * @return bool True or false if something goes wrong.
 */
function tags_update_tag($values){
	return db_process_sql_update ('ttag', $values);
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
