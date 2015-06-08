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


function mysql_session_open ($save_path, $session_name) {
	return true;
}

function mysql_session_close() {
	return true;
}

function mysql_session_read ($SessionID) {
	
	$SessionID = addslashes($SessionID);
	
	$sql = "
		SELECT data
		FROM tsessions_php
		WHERE id_session = '$SessionID'";
	
	$session_data = db_process_sql($sql);
	
	if (count($session_data) == 1) {
		return $session_data[0]['data'];
	}
	else {
		return false;
	}
}

function mysql_session_write ($SessionID, $val) {
	
	$SessionID = addslashes($SessionID);
	$val = addslashes($val); 
	
	$sql = "
		SELECT COUNT(*)
		FROM tsessions_php
		WHERE id_session = '$SessionID'";
	
	$SessionExists = db_process_sql ($sql);
	
	$session_exists = $SessionExists[0]['COUNT(*)'];
	
	if ($session_exists == 0) {
		$now = time();
		$retval_write = db_process_sql_insert('tsessions_php',
			array('id_session' => $SessionID,
				'last_active' => $now,
				'data' => $val));
	}
	else {
		$now = time();
		$retval_write = db_process_sql_update('tsessions_php',
			array('last_active' => $now, 'data' => $val),
			array('id_session' => $SessionID));
	}
	
	return $retval_write;
}

function mysql_session_destroy ($SessionID) {
	$SessionID = addslashes($SessionID);
	
	$retval = db_process_sql ("
		DELETE
		FROM tsessions_php 
		WHERE id_session = '$SessionID'");
	return $retval;
}

function mysql_session_gc ($maxlifetime = 300) {
	global $config;
	
	if (isset($config['session_timeout'])) {
		$maxlifetime = $config['session_timeout'];
	}
	
	$CutoffTime = time() - $maxlifetime;
	
	$retval = db_process_sql("
		DELETE
		FROM tsessions_php 
		WHERE last_active < $CutoffTime");
	return $retval;
}

$resultado_handler = session_set_save_handler (
	'mysql_session_open',
	'mysql_session_close',
	'mysql_session_read',
	'mysql_session_write',
	'mysql_session_destroy',
	'mysql_session_gc'); 

?>
