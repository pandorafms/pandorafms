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
$build_version = 'PC10408'; // Remember is YYMMDD
$pandora_version = 'v3.1-dev';

/* Help to debug problems. Override global PHP configuration */
if (!isset($develop_bypass)) $develop_bypass = 0;

if ($develop_bypass != 1) {
	// error_reporting(E_ALL);
	
	if (strnatcmp(phpversion(),'5.2.11') >= 0) 
	{ 
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
	}
	else 
	{ 
		error_reporting(E_ALL & ~E_NOTICE);
	}
	
	ini_set("display_errors", 0);
	ini_set("error_log", $config["homedir"]."/pandora_console.log");
}
else {
	error_reporting(E_ALL);
	ini_set("display_errors", 1);
	ini_set("error_log", $config["homedir"]."/pandora_console.log");
}

$config['start_time'] = microtime (true);

// Non-persistent connection: This will help to avoid mysql errors like "has gone away" or locking problems
// If you want persistent connections change it to mysql_pconnect(). 
$config['dbconnection'] = mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);
if (! $config['dbconnection']) {
	include ($config["homedir"]."/general/error_authconfig.php");
	exit;
}

$ownDir = dirname(__FILE__) . '/';

mysql_select_db ($config["dbname"]);
require_once ($ownDir . 'functions.php');
require_once ($ownDir . 'functions_db.php');
require_once ($ownDir. 'functions_config.php');

process_config ();

require_once ($ownDir . 'streams.php');
require_once ($ownDir . 'gettext.php');

$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];
$config['user_language'] = $config["language"];

// Set a the system timezone default 
if ((!isset($config["timezone"])) OR ($config["timezone"] == "")){
	$config["timezone"] = "Europe/Berlin";
}

date_default_timezone_set($config["timezone"]);

// Set user language if provided, overriding System language
if (isset ($config['id_user'])){
	$userinfo = get_user_info ($config['id_user']);
	if ($userinfo["language"] != ""){
		$config['user_language'] = $userinfo["language"];
	}

	// Each user could have it's own timezone)
	if (isset($userinfo["timezone"])) {
		if ($userinfo["timezone"] != ""){
			date_default_timezone_set($userinfo["timezone"]);
		}
	}
} 

$l10n = NULL; 

if (file_exists ('./include/languages/'.$config["user_language"].'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$config["user_language"].'.mo'));
	$l10n->load_tables();
}

if (! defined ('EXTENSIONS_DIR'))
	define ('EXTENSIONS_DIR', 'extensions');

if (! defined ('ENTERPRISE_DIR'))
	define ('ENTERPRISE_DIR', 'enterprise');

require_once ($ownDir . 'functions_extensions.php');

$config['extensions'] = get_extensions ();

// Detect if enterprise extension is installed
// NOTICE: This variable (config[enterprise_installed] is used in several
// sections. Faking or forcing to 1 will make pandora fails.

if (file_exists ($config["homedir"].'/'.ENTERPRISE_DIR.'/index.php')) {
	$config['enterprise_installed'] = 1;
	enterprise_include_once ('include/functions_enterprise.php');
} else {
	$config['enterprise_installed'] = 0;
}

// Connect to the history DB
if (isset($config['history_db_enabled'])) {
	if ($config['history_db_enabled']) {
		$config['history_db_connection'] = mysql_connect ($config['history_db_host'] . ':' . $config['history_db_port'], $config['history_db_user'], $config['history_db_pass']);
		mysql_select_db ($config['history_db_name'], $config['history_db_connection']);
	}
}

// Make dbconnection the default connection again (the link identifier of the already opened link will be returned)
$config['dbconnection'] = mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);

?>
