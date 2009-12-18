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
 * @subpackage Config
 */

/**
 * Pandora build version and version 
 */
$build_version = 'PC091218';
$pandora_version = 'v3.0';

/* Help to debug problems. Override global PHP configuration */

// error_reporting(E_ALL);

if (strnatcmp(phpversion(),'5.3') >= 0) 
	{ 
	error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
	}
else 
	{ 
	error_reporting(E_ALL & ~E_NOTICE);
	}

ini_set("display_errors", 0);
ini_set("error_log", $config["homedir"]."/pandora_console.log");

// Set a default timezone default if not configured
// to avoid warnings and bad timestamp calculation in PHP > 5.1 

if (ini_get('date.timezone') == ""){
	date_default_timezone_set("Europe/Berlin");
}

$config['start_time'] = microtime (true);

// Non-persistent connection: This will help to avoid mysql errors like "has gone away" or locking problems
// If you want persistent connections change it to mysql_pconnect(). 
$config['dbconnection'] = mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);
if (! $config['dbconnection']) {
	include ($config["homedir"]."/general/error_authconfig.php");
	exit;
}

mysql_select_db ($config["dbname"]);
require_once ('functions.php');
require_once ('functions_db.php');
require_once ('functions_config.php');

process_config ();

require_once ('streams.php');
require_once ('gettext.php');

// Set IP address of user connected to Pandora console and store it in session array
global $REMOTE_ADDR;

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];

// Set user language if provided, overriding System language
if (isset ($config['id_user'])){
	$userinfo = get_user_info ($config['id_user']);
	if ($userinfo["language"] != ""){
		$config['language'] = $userinfo["language"];
	}
} 

$l10n = NULL;
if (file_exists ('./include/languages/'.$config["language"].'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$config["language"].'.mo'));
	$l10n->load_tables();
}

if (! defined ('EXTENSIONS_DIR'))
	define ('EXTENSIONS_DIR', 'extensions');

if (! defined ('ENTERPRISE_DIR'))
	define ('ENTERPRISE_DIR', 'enterprise');

require_once ('functions_extensions.php');

$config['extensions'] = get_extensions ();

// Connect to the history DB
if ($config['history_db_enabled']) {
	$config['history_db_connection'] = mysql_connect ($config['history_db_host'] . ':' . $config['history_db_port'], $config['history_db_user'], $config['history_db_pass']);
	mysql_select_db ($config['history_db_name'], $config['history_db_connection']);
}

// Make dbconnection the default connection again (the link identifier of the already opened link will be returned)
$config['dbconnection'] = mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);

?>
