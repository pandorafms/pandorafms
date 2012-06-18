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
 * @subpackage Users
 */

require_once($config['homedir'] . "/include/functions_groups.php");

/**
 * Get a list of all users in an array [username] => (info)
 *
 * @param string Field to order by (id_usuario, nombre_real or fecha_registro)
 * @param string Which info to get (defaults to nombre_real)
 *
 * @return array An array of users
 */
function users_get_info ($order = "fullname", $info = "fullname") {
	$users = get_users ($order);
	$ret = array ();
	foreach ($users as $user_id => $user_info) {
		$ret[$user_id] = $user_info[$info];
	}
	return $ret;
}

/**
 * Enable/Disable a user
 *
 * @param int user id
 * @param int new disabled value (0 when enable, 1 when disable)
 *
 * @return int sucess return
 */
function users_disable ($user_id, $new_disabled_value) {
	return db_process_sql_update('tusuario', array('disabled' => $new_disabled_value), array('id_user' => $user_id));
}

/**
 * Get all the Model groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_all_model_groups () {
	$groups = db_get_all_rows_in_table ('tmodule_group');
	if($groups === false) {
		$groups = array();
	}
	$returnGroups = array();
	foreach ($groups as $group)
	$returnGroups[$group['id_mg']] = $group['name'];
	
	$returnGroups[0] = "Not assigned"; //Module group external to DB but it exist
	
	
	return $returnGroups;
}

/**
 * Get all the groups a user has reading privileges.
 *
 * @param string User id
 * @param string The privilege to evaluate, and it is false then no check ACL.
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 * @param array $id_groups The list of group to scan to bottom child. By default null.
 *
 * @return array A list of the groups the user has certain privileges.
 */
function users_get_groups ($id_user = false, $privilege = "AR", $returnAllGroup = true, $returnAllColumns = false, $id_groups = null) {
	if (empty ($id_user)) {
		global $config;
		
		$id_user = null;
		if (isset($config['id_user'])) {
			$id_user = $config['id_user'];
		}
	}
	
	if (isset($id_groups)) {
		//Get recursive id groups
		$list_id_groups = array();
		foreach ((array)$id_groups as $id_group) {
			$list_id_groups = array_merge($list_id_groups, groups_get_id_recursive($id_group));
		}
		
		$list_id_groups = array_unique($list_id_groups);
		
		$groups = db_get_all_rows_filter('tgrupo', array('id_grupo' => $list_id_groups, 'order' => 'parent, nombre'));
	}
	else {
		$groups = db_get_all_rows_in_table ('tgrupo', 'parent, nombre');
	}
	
	$user_groups = array ();
	
	if (!$groups)
		return $user_groups;
	
	if ($returnAllGroup) { //All group
		if ($returnAllColumns) {
			$groupall = array('id_grupo' => 0, 'nombre' => __('All'),
				'icon' => 'world', 'parent' => 0, 'disabled' => 0,
				'custom_id' => null, 'propagate' => 0); 
		}
		else {
			$groupall = array('id_grupo' => 0, 'nombre' => __("All"));
		}
		
		// Add the All group to the beginning to be always the first
		array_unshift($groups, $groupall);
	}
	
	foreach ($groups as $group) {
		if ($privilege === false) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
		else if (check_acl($id_user, $group["id_grupo"], $privilege)) {
			if ($returnAllColumns) {
				$user_groups[$group['id_grupo']] = $group;
			}
			else {
				$user_groups[$group['id_grupo']] = $group['nombre'];
			}
		}
	}
	
	return $user_groups;
}

/**
 * Get all the groups a user has reading privileges. Version for tree groups.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean $returnAllGroup Flag the return group, by default true.
 * @param boolean $returnAllColumns Flag to return all columns of groups.
 *
 * @return array A treefield list of the groups the user has certain privileges.
 */
function users_get_groups_tree($id_user = false, $privilege = "AR", $returnAllGroup = true) {
	$user_groups = users_get_groups ($id_user, $privilege, $returnAllGroup, true);
	
	$user_groups_tree = groups_get_groups_tree_recursive($user_groups);
	
	return $user_groups_tree;
}

/**
 * Get the first group of an user.
 *
 * Useful function when you need a default group for a user.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param bool $all_group Flag to return all group, by default true;
 *
 * @return array The first group where the user has certain privileges.
 */
function users_get_first_group ($id_user = false, $privilege = "AR", $all_group = true) {
	$groups = array_keys (users_get_groups ($id_user, $privilege));
	
	$return = array_shift($groups);
	
	if ((!$all_group) && ($return == 0)) {
		$return = array_shift($groups);
	}
	
	return $return;
}

/**
 * Return access to a specific agent by a specific user
 *
 * @param int Agent id.
 * @param string Access mode to be checked. Default AR (Agent reading)
 * @param string User id. Current user by default
 *
 * @return bool Access to that agent (false not, true yes)
 */
function users_access_to_agent ($id_agent, $mode = "AR", $id_user = false) {
	if (empty ($id_agent))
	return false;
	
	if ($id_user == false) {
		global $config;
		$id_user = $config['id_user'];
	}
	
	$id_group = (int) db_get_value ('id_grupo', 'tagente', 'id_agente', (int) $id_agent);
	return (bool) check_acl ($id_user, $id_group, $mode);
}

/**
 * Return user by id (user name)
 *
 * @param string User id.
 *
 * @return mixed User row or false if something goes wrong
 */
function users_get_user_by_id ($id_user){
	$result_user = db_get_row('tusuario', 'id_user', $id_user);
	
	return $result_user;
}

define("MAX_TIMES", 10);

////////////////////////////////////////////////////////////////////////
//////////////////////WEBCHAT FUNCTIONS/////////////////////////////////
////////////////////////////////////////////////////////////////////////
function users_get_last_messages($last_time = false) {
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	$timestamp = time();
	if ($last_time === false)
		$last_time = 24 * 60 * 60;
	$from = $timestamp - $last_time;
	
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$return = array('correct' => false, 'log' => array());
	
	if (!file_exists($log_chat_file)) {
		touch($log_chat_file);
	}
	
	$text_encode = @file_get_contents($log_chat_file);
	$log = json_decode($text_encode, true);
	
	if ($log !== false) {
		if ($log === null)
			$log = array();
		
		$log_last_time = array();
		foreach ($log as $message) {
			if ($message['timestamp'] >= $from) {
				$log_last_time[] = $message;
			}
		}
		
		$return['correct'] = true;
		$return['log'] = $log_last_time;
		$return['global_counter'] = $global_counter_file;
	}
	
	echo json_encode($return);
	
	fclose($fp_global_counter);
	
	return;
}

function users_save_login() {
	global $config;
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	$user = db_get_row_filter('tusuario',
		array('id_user' => $config['id_user']));
		
	$message = sprintf(__('User %s login at %s'), $user['fullname'],
		date($config['date_format']));
	users_save_text_message($message, 'notification');
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	if (isset($user_list[$config['id_user']])) {
		$user_list[$config['id_user']]['count']++;
	}
	else {
		$user_list[$config['id_user']] = array('name' => $user['fullname'],
			'count' => 1);
	}
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_logout($user = false, $delete = false) {
	global $config;
	
	$return = array('correct' => false, 'users' => array());
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	if (empty($user)) {
		$user = db_get_row_filter('tusuario',
			array('id_user' => $config['id_user']));
	}
	
	if ($delete) {
		$no_json_output = true;
		$message = sprintf(__('User %s was deleted in the DB at %s'),
			$user['fullname'], date($config['date_format']));
	}
	else {
		$no_json_output = false;
		$message = sprintf(__('User %s logout at %s'), $user['fullname'],
			date($config['date_format']));
	}
	
	users_save_text_message($message, 'notification', $no_json_output);
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	if ($delete) {
		unset($user_list[$user['id_user']]);
	}
	else {
		if (isset($user_list[$config['id_user']])) {
			$user_list[$config['id_user']]['count']--;
		}
		
		if ($user_list[$config['id_user']]['count'] <= 0) {
			unset($user_list[$user['id_user']]);
		}
	}
	
	//Clean the file
	ftruncate($fp_user_list, 0);
	
	$status = fwrite($fp_user_list, json_encode($user_list));
	
	if ($status === false) {
		fclose($fp_user_list);
		
		return;
	}
	
	fclose($fp_user_list);
}

function users_save_text_message($message = false, $type = 'message', $no_json_output = false) {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$return = array('correct' => false);
	
	$id_user = $config['id_user'];
	$user = db_get_row_filter('tusuario',
		array('id_user' => $id_user));
	
	$message_data = array();
	$message_data['type'] = $type;
	$message_data['id_user'] = $id_user;
	$message_data['user_name'] = $user['fullname'];
	$message_data['text'] = io_safe_input_html($message);
	//The $message_data['timestamp'] set when adquire the files to save.
	
	
	
	//First lock the file
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter === false) {
		if (!$no_json_output)
			echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_global_counter, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			if (!$no_json_output)
				echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_global_counter, "%d", $global_counter_file);
	if (empty($global_counter_file)) {
		$global_counter_file = 0;
	}
	
	//Clean the file
	ftruncate($fp_global_counter, 0);
	
	$message_data['timestamp'] = time();
	$message_data['human_time'] = date($config['date_format'], $message_data['timestamp']);
	
	$global_counter = $global_counter_file + 1;
	
	$status = fwrite($fp_global_counter, $global_counter);
	
	if ($status === false) {
		fclose($fp_global_counter);
		
		if (!$no_json_output)
			echo json_encode($return);
		
		return;
	}
	else {
		$text_encode = @file_get_contents($log_chat_file);
		$log = json_decode($text_encode, true);
		$log[$global_counter] = $message_data;
		$status = file_put_contents($log_chat_file, json_encode($log));
		
		fclose($fp_global_counter);
		
		$return['correct'] = true;
		if (!$no_json_output)
			echo json_encode($return);
	}
	
	return;
}

function users_long_polling_check_messages($global_counter) {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$changes = false;
	
	$tries_general = 0;
	
	$error = false;
	
	while (!$changes) {
		//First lock the file
		$fp_global_counter = @fopen($file_global_counter_chat, "a+");
		if ($fp_global_counter) {
			//Try to look MAX_TIMES times
			$tries = 0;
			$lock = true;
			while (!flock($fp_global_counter, LOCK_EX)) {
				$tries++;
				if ($tries > MAX_TIMES) {
					$lock = false;
					$error = true;
					break;
				}
				
				sleep(1);
			}
			
			if ($lock) {
				@fscanf($fp_global_counter, "%d", $global_counter_file);
				if (empty($global_counter_file)) {
					$global_counter_file = 0;
				}
				
				if ($global_counter_file > $global_counter) {
					//TODO Optimize slice the array.
					
					$text_encode = @file_get_contents($log_chat_file);
					$log = json_decode($text_encode, true);
					
					$return_log = array();
					foreach ($log as $key => $message) {
						if ($key <= $global_counter) continue;
						
						$return_log[] = $message;
					}
					
					$return = array(
						'correct' => true,
						'global_counter' => $global_counter_file,
						'log' => $return_log);
					
					echo json_encode($return);
					
					fclose($fp_global_counter);
					
					return;
				}
			}
			fclose($fp_global_counter);
		}
		
		sleep(3);
		$tries_general = $tries_general + 3;
		
		if ($tries_general > MAX_TIMES) {
			break;
		}
	}
	
	//Because maybe the exit of loop for exaust.
	echo json_encode(array('correct' => false, 'error' => $error));
	
	return;
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_global_counter($mode = 'json') {
	global $config;
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			@fscanf($fp_global_counter, "%d", $global_counter_file);
			if (empty($global_counter_file)) {
				$global_counter_file = 0;
			}
			
			fclose($fp_global_counter);
		}
	}
	
	switch ($mode) {
		case 'json':
			echo json_encode(array('correct' => true, 'global_counter' => $global_counter_file));
			break;
		case 'return':
			return $global_counter_file;
			break;
		case 'session':
			$_SESSION['global_counter_chat'] = $global_counter_file;
			break;
	}
}

/**
 * Get the last global counter for chat.
 * 
 * @param string $mode There are two modes 'json', 'return' and 'session'. And json is by default.
 */
function users_get_last_type_message() {
	global $config;
	
	$return = 'false';
	
	$file_global_counter_chat = $config["attachment_store"] . '/pandora_chat.global_counter.txt';
	$log_chat_file = $config["attachment_store"] . '/pandora_chat.log.json.txt';
	
	$global_counter_file = 0;
	
	$fp_global_counter = @fopen($file_global_counter_chat, "a+");
	if ($fp_global_counter) {
		$tries = 0;
		$lock = true;
		while (!flock($fp_global_counter, LOCK_EX)) {
			$tries++;
			if ($tries > MAX_TIMES) {
				$lock = false;
				break;
			}
			
			sleep(1);
		}
		
		if ($lock) {
			$text_encode = @file_get_contents($log_chat_file);
			$log = json_decode($text_encode, true);
			
			$last = end($log);
			
			$return = $last['type'];
			
			fclose($fp_global_counter);
		}
	}
	
	return $return;
}

function users_is_last_system_message() {
	$type = users_get_last_type_message();
	
	if ($type != 'message')
		return true;
	else
		return false;
}

function users_check_users() {
	global $config;
	
	$return = array('correct' => false, 'users' => '');
	
	$file_global_user_list = $config["attachment_store"] . '/pandora_chat.user_list.json.txt';
	
	//First lock the file
	$fp_user_list = @fopen($file_global_user_list, "a+");
	if ($fp_user_list === false) {
		echo json_encode($return);
		
		return;
	}
	//Try to look MAX_TIMES times
	$tries = 0;
	while (!flock($fp_user_list, LOCK_EX)) {
		$tries++;
		if ($tries > MAX_TIMES) {
			echo json_encode($return);
			
			return;
		}
		
		sleep(1);
	}
	@fscanf($fp_user_list, "%[^\n]", $user_list_json);
	
	$user_list = json_decode($user_list_json, true);
	if (empty($user_list))
		$user_list = array();
	
	fclose($fp_user_list);
	
	$user_name_list = array();
	foreach ($user_list as $user) {
		$user_name_list[] = $user['name'];
	}
	
	$return['correct'] = true;
	$return['users'] = implode('<br />', $user_name_list);
	echo json_encode($return);
	
	return;
}
?>
