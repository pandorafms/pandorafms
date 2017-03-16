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
$build_version = 'PC170317';
$pandora_version = 'v7.0NG';

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

// Check if mysqli is available
if (!(isset($config["mysqli"]))) {
	$config["mysqli"] = extension_loaded(mysqli);
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
if (!isset($config["homeurl_static"])) {
	$config["homeurl_static"] = $config["homeurl"];
}

date_default_timezone_set("Europe/Madrid");


config_process_config();

config_prepare_session();
require_once ($config["homedir"].'/include/load_session.php');
if(session_id() == '') {
	$resultado = session_start();
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
	config_user_set_custom_config();
} 

// Check if inventory_changes_blacklist is setted, if not create it
if (!isset($config['inventory_changes_blacklist'])) {
	$config['inventory_changes_blacklist'] = array();
}

//NEW UPDATE MANAGER URL
if (!isset($config['url_update_manager'])) {
	config_update_value('url_update_manager',
		'https://licensing.artica.es/pandoraupdate7/server.php');
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
$select = db_process_sql("SELECT value FROM tconfig WHERE token='autorefresh_white_list'");
$autorefresh_list = json_decode($select[0]['value']);
$config['autorefresh_white_list'] = array();
$config['autorefresh_white_list'] = $autorefresh_list;
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
		if (!isset($config['quote_string'])) {
			$config['db_quote_string'] = "'";
		}
		break;
}
//======================================================================
?>
