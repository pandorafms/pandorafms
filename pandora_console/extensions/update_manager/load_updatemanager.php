<?php

//Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/* Change to E_ALL for development/debugging */
error_reporting (E_ALL);

/* Database backend, not really tested with other backends, so it's 
 not functional right now */
define ('DB_BACKEND', 'mysql');
define ('FREE_USER', 'PANDORA-FREE');

if (! extension_loaded ('mysql'))
	die ('Your PHP installation appears to be missing the MySQL extension which is required.');

require_once ('lib/libupdate_manager.php');


function check_keygen ($settings) {
	global $config;
	
	return '';
}

function get_user_key ($settings) {
	global $config;
	global $build_version;
	global $pandora_version; 

	require_once($config["homedir"] . "/extensions/update_manager/debug.php");
		
	print_debug_message_trace("Init Call get_user_key function.");
	
	$s = $settings->customer_key;
	switch ($config['dbtype']) {
		case 'mysql':
			$n = (int) db_get_value ('COUNT(`id_agente`)', 'tagente', 'disabled', 0);
			$m = (int) db_get_value ('COUNT(`id_agente_modulo`)', 'tagente_modulo', 'disabled', 0);
			$u = (int) db_get_value ('MAX(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(keepalive))', 'tserver');
			break;
		case 'postgresql':
			$n = (int) db_get_value ('COUNT("id_agente")', 'tagente', 'disabled', 0);
			$m = (int) db_get_value ('COUNT("id_agente_modulo")', 'tagente_modulo', 'disabled', 0);
			$u = (int) db_get_value ('MAX(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(keepalive))', 'tserver');
			break;
		case 'oracle':
			$n = (int) db_get_value ('COUNT(id_agente)', 'tagente', 'disabled', 0);
			$m = (int) db_get_value ('COUNT(id_agente_modulo)', 'tagente_modulo', 'disabled', 0);
			$u = (int) db_get_value ('MAX(UNIX_TIMESTAMP()-UNIX_TIMESTAMP(keepalive))', 'tserver');
			break;
	}
	$user_key = array ('S' => $s, 'A' => $n, 'U' => $u, 'M' => $m, 'B' => $build_version, 'P' => $pandora_version);
	
	return json_encode ($user_key);
}

flush ();

?>
