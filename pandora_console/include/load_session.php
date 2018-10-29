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


function pandora_session_open ($save_path, $session_name) {
	return true;
}

function pandora_session_close() {
	return true;
}

function pandora_session_read ($session_id) {
	$session_id = addslashes($session_id);
	$session_data = db_get_value('data', 'tsessions_php', 'id_session', $session_id);
	
	if (!empty($session_data))
		return $session_data;
	else
		return '';
}

function pandora_session_write ($session_id, $data) {
	$session_id = addslashes($session_id);
	
	$values = array();
	$values['last_active'] = time();
	
	if (!empty($data))
		$values['data'] = addslashes($data);
	
	$session_exists = (bool) db_get_value('COUNT(id_session)', 'tsessions_php', 'id_session', $session_id);
	
	if (!$session_exists) {
		$values['id_session'] = $session_id;
		$retval_write = db_process_sql_insert('tsessions_php', $values);
	}
	else {
		$retval_write = db_process_sql_update('tsessions_php', $values, array('id_session' => $session_id));
	}

	return ($retval_write !== false) ? true : false;
}

function pandora_session_destroy ($session_id) {
	$session_id = addslashes($session_id);
	
	$retval = (bool) db_process_sql_delete('tsessions_php', array('id_session' => $session_id));
	
	return $retval;
}

function pandora_session_gc ($max_lifetime = 300) {
	global $config;
	
	if (isset($config['session_timeout'])) {
		$max_lifetime = $config['session_timeout'];
	}
	
	$time_limit = time() - $max_lifetime;
	
	$retval = (bool) db_process_sql_delete('tsessions_php', array('last_active' => "<" . $time_limit));
	
	return $retval;
}

$result_handler = @session_set_save_handler ('pandora_session_open', 'pandora_session_close', 'pandora_session_read', 'pandora_session_write', 'pandora_session_destroy', 'pandora_session_gc'); 

?>
