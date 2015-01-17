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
 * @subpackage Config
 */

/**
 * Pandora build version and version 
 */
$build_version = 'PC150118';
$pandora_version = 'v6.0dev';

// Do not overwrite default timezone set if defined.
$script_tz = @date_default_timezone_get();
if (empty($script_tz)) {
	date_default_timezone_set("Europe/Berlin");
	ini_set("date.timezone", "Europe/Berlin");
}
else {
	ini_set("date.timezone", $script_tz);
}

/* Help to debug problems. Override global PHP configuration */
global $develop_bypass;
if ($develop_bypass != 1) {
	// error_reporting(E_ALL);
	
	if (version_compare(PHP_VERSION, '5.3.0') >= 0)
	{ 
		error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
	}
	else 
	{ 
		error_reporting(E_ALL & ~E_NOTICE);
	}
	
	ini_set("display_errors", 0);
	ini_set("log_errors", 1);
	ini_set("error_log", $config["homedir"]."/pandora_console.log");
}
else {
	// Develop mode, show all notices and errors on Console (and log it)
	if (version_compare(PHP_VERSION, '5.3.0') >= 0)
	{
		error_reporting(E_ALL & ~E_DEPRECATED);
	}
	else
	{
		error_reporting(E_ALL);
	}
	ini_set("display_errors", 1);
	ini_set("log_errors", 1);
	ini_set("error_log", $config["homedir"]."/pandora_console.log");
}

$config['start_time'] = microtime (true);

$ownDir = dirname(__FILE__) . '/';
$ownDir = str_replace("\\", "/", $ownDir);

//Set by default the MySQL connection for DB, because in older Pandora have not
//this token in the config.php
if (!isset($config['dbtype'])) {
	$config['dbtype'] = 'mysql';
}

if (!isset($config['dbport'])) {
	switch ($config['dbtype']) {
		case 'mysql':
			$config['dbport'] = '3306';
			break;
		case 'postgresql':
			$config['dbport'] = '5432';
			break;
		case 'oracle':
			$config['dbport'] = '1521';
			break;
	}
}

require_once ($ownDir . 'constants.php');
require_once ($ownDir . 'functions_db.php');
require_once ($ownDir . 'functions.php');

db_select_engine();
$config['dbconnection'] = db_connect();


if (! defined ('EXTENSIONS_DIR'))
	define ('EXTENSIONS_DIR', 'extensions');

if (! defined ('ENTERPRISE_DIR'))
	define ('ENTERPRISE_DIR', 'enterprise');

require_once ($ownDir. 'functions_config.php');

// We need a timezone BEFORE calling config_process_config. 
// If not we will get ugly warnings. Set Europe/Madrid by default
// Later will be replaced by the good one.

date_default_timezone_set("Europe/Madrid");

config_process_config();

if (!isset($config["homeurl_static"])) {
	$config["homeurl_static"] = $config["homeurl"];
}

// Set a the system timezone default 
if ((!isset($config["timezone"])) OR ($config["timezone"] == "")) {
	$config["timezone"] = "Europe/Berlin";
}

date_default_timezone_set($config["timezone"]);

require_once ($ownDir . 'streams.php');
require_once ($ownDir . 'gettext.php');

if (isset($_SERVER['REMOTE_ADDR'])) {
	$config["remote_addr"] = $_SERVER['REMOTE_ADDR'];
}
else {
	$config["remote_addr"] = null;
}

// Save the global values
$config["global_block_size"] = $config["block_size"];
$config["global_flash_charts"] = $config["flash_charts"];

if (isset ($config['id_user'])) {
	$userinfo = get_user_info ($config['id_user']);
	
	// Refresh the last_connect info in the user table 
	// if last update was more than 5 minutes ago
	if($userinfo['last_connect'] < (time()-SECONDS_1MINUTE)) {
		update_user($config['id_user'], array('last_connect' => time()));
	}
	
	// If block_size or flash_chart are provided then override global settings
	if (!empty($userinfo["block_size"]) && ($userinfo["block_size"] != 0))
		$config["block_size"] = $userinfo["block_size"];
	
	if ($userinfo["flash_chart"] != -1)
		$config["flash_charts"] = $userinfo["flash_chart"];
	
	// Each user could have it's own timezone)
	if (isset($userinfo["timezone"])) {
		if ($userinfo["timezone"] != "") {
			date_default_timezone_set($userinfo["timezone"]);
		}
	}
	
	if (defined('METACONSOLE')) {
		$config['metaconsole_access'] = $userinfo["metaconsole_access"];
	}
} 

// Check if inventory_changes_blacklist is setted, if not create it
if (!isset($config['inventory_changes_blacklist'])) {
	$config['inventory_changes_blacklist'] = array();
}

//NEW UPDATE MANAGER URL
if (!isset($config['url_update_manager'])) {
	config_update_value('url_update_manager',
		'https://artica.es/pandoraupdate51/server.php');
}

if (defined('METACONSOLE')) {
	enterprise_include_once('meta/include/functions_users_meta.php');
	enterprise_hook('set_meta_user_language');
}
else
	set_user_language();

require_once ($ownDir . 'functions_extensions.php');

$config['extensions'] = extensions_get_extensions ();

// Detect if enterprise extension is installed
// NOTICE: This variable (config[enterprise_installed] is used in several
// sections. Faking or forcing to 1 will make pandora fails.

if (file_exists ($config["homedir"] . '/' . ENTERPRISE_DIR . '/index.php')) {
	$config['enterprise_installed'] = 1;
	enterprise_include_once ('include/functions_enterprise.php');
}
else {
	$config['enterprise_installed'] = 0;
}

// Function include_graphs_dependencies() it's called in the code below
require_once("include_graph_dependencies.php");

include_graphs_dependencies($config['homedir'] . '/');

// Updates autorefresh time
if (isset($_POST['vc_refr'])) {
	config_update_value ('vc_refr', get_parameter('vc_refr', $config['vc_refr']));
}


//======= Autorefresh code =============================================
$config['autorefresh_white_list'] = array(
	'operation/agentes/tactical',
	'operation/agentes/group_view',
	'operation/agentes/estado_agente',
	'operation/agentes/alerts_status',
	'operation/agentes/status_monitor',
	'enterprise/operation/services/services',
	'enterprise/dashboard/main_dashboard',
	'operation/reporting/graph_viewer',
	'operation/snmpconsole/snmp_view',
	'operation/agentes/networkmap',
	'enterprise/operation/services/services',
	'operation/visual_console/render_view',
	'operation/events/events');
	
// Specific metaconsole autorefresh white list sections
if (defined('METACONSOLE')) {
	$config['autorefresh_white_list'][] = 'monitoring/tactical';
	$config['autorefresh_white_list'][] = 'monitoring/group_view';
	$config['autorefresh_white_list'][] = 'operation/tree';
	$config['autorefresh_white_list'][] = 'screens/screens';
}

//======================================================================


//======================================================================
// Update the $config['homeurl'] with the full url with the special
// cases (reverse proxy, others ports...).
//======================================================================
$config["homeurl"] = ui_get_full_url(false);


//======================================================================
// Get the version of DB manager
//======================================================================
switch ($config["dbtype"]) {
	case "mysql":
		if (!isset($config['quote_string'])) {
			$config['db_quote_string'] = "\"";
		}
		break;
	case "postgresql":
		if (!isset($config['dbversion'])) {
			$result = db_get_sql("select version();");
			$result_chunks = explode(" ", $result);
			
			$config['dbversion'] = $result_chunks[1];
		}
		if (!isset($config['quote_string'])) {
			$config['db_quote_string'] = "'";
		}
		break;
	case "oracle":
		break;
}
//======================================================================
?>
