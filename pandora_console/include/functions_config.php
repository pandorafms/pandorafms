<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

/**
 * Creates a single config value in the database.
 * 
 * @param string Config token to create.
 * @param string Value to set.
 *
 * @return bool Config id if success. False on failure.
 */
function create_config_value ($token, $value) {
	return process_sql_insert ('tconfig',
		array ('value' => $value,
			'token' => $token));
}

/**
 * Update a single config value in the database.
 * 
 * If the config token doesn't exists, it's created.
 * 
 * @param string Config token to update.
 * @param string New value to set.
 *
 * @return bool True if success. False on failure.
 */
function update_config_value ($token, $value) {
	global $config;
	
	if (!isset ($config[$token]))
		return (bool) create_config_value ($token, $value);
	
	/* If it has not changed */
	if ($config[$token] == $value)
		return true;
	
	$config[$token] = $value;
	
	return (bool) process_sql_update ('tconfig', 
		array ('value' => $value),
		array ('token' => $token));
}

/**
 * Updates all config values if .
 * 
 * 
 */
function update_config () {
	global $config;
	
	/* If user is not even log it, don't try this */
	if (! isset ($config['id_user']))
		return false;
	
	if (! give_acl ($config['id_user'], 0, "PM") || ! is_user_admin ($config['id_user']))
		return false;
	
	$update_config = (bool) get_parameter ('update_config');
	if (! $update_config)
		return;
	
	$style = (string) get_parameter ('style', $config["style"]);
	$style = substr ($style, 0, strlen ($style) - 4);
	
	/* Workaround for ugly language and language_code missmatch */
	$config['language_code'] = (string) get_parameter ('language', $config["language"]);
	update_config_value ('language_code', $config['language_code']);
	$config["language"] = $config['language_code'];
	
	update_config_value ('remote_config', (string) get_parameter ('remote_config', $config["remote_config"]));
	update_config_value ('block_size', (int) get_parameter ('block_size', $config["block_size"]));
	update_config_value ('days_purge', (int) get_parameter ('days_purge', $config["days_purge"]));
	update_config_value ('days_compact', (int) get_parameter ('days_compact', $config["days_compact"]));
	update_config_value ('graph_res', (int) get_parameter ('graph_res', $config["graph_res"]));
	update_config_value ('step_compact', (int) get_parameter ('step_compact', $config["step_compact"]));
	update_config_value ('style', $style);
	update_config_value ('graph_color1', (string) get_parameter ('graph_color1', $config["graph_color1"]));
	update_config_value ('graph_color2', (string) get_parameter ('graph_color2', $config["graph_color2"]));
	update_config_value ('graph_color3', (string) get_parameter ('graph_color3', $config["graph_color3"]));
	update_config_value ('sla_period', (int) get_parameter ('sla_period', $config["sla_period"]));
	update_config_value ('date_format', (string) get_parameter ('date_format', $config["date_format"]));
	update_config_value ('trap2agent', (string) get_parameter ('trap2agent', 0));
	update_config_value ('autoupdate', (bool) get_parameter ('autoupdate'));
	update_config_value ('prominent_time', (string) get_parameter ('prominent_time', $config["prominent_time"]));
	update_config_value ('timesource', (string) get_parameter ('timesource', $config["timesource"]));
	update_config_value ('event_view_hr', (int) get_parameter ('event_view_hr', $config["event_view_hr"]));
	update_config_value ('loginhash_pwd', (string) get_parameter ('loginhash_pwd', $config["loginhash_pwd"]));
	update_config_value ('https', (bool) get_parameter ('https'));
	update_config_value ('compact_header', (bool) get_parameter ('compact_header'));
}

/**
 * 
 */
function process_config () {
	global $config;
	
	$configs = get_db_all_rows_in_table ('tconfig');
	
	if (empty ($configs)) {
		exit ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml"><head><title>Pandora FMS Error</title>
			<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
			</head><body><div align="center">
			<div id="db_f">
			<div>
			<a href="index.php"><img src="images/pandora_logo.png" border="0" alt="logo" /></a>
			</div>
			<div id="db_ftxt">
			<h1 id="log_f" class="error">Pandora FMS Console Error DB-002</h1>
			Cannot load configuration variables from database. Please check your database setup in the
			<b>include/config.php</b> file or read the documentation on how to setup Pandora FMS.<i><br /><br />
			Most likely your database schema has been created but there are is no data in it, you have a problem with the database access credentials or your schema is out of date.
			</i><br />
			</div>
			</div></body></html>');
	}
	
	/* Compatibility fix */
	foreach ($configs as $c) {
		switch ($c["token"]) {
		case "language_code":
			$config['language'] = $c['value'];
			break;
		case "auth":
			exit ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml">
				<head>
				<title>Pandora FMS Error</title>
				<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
				</head>
				<body>
				<div align="center">
				<div id="db_f">
				<div>
				<a href="index.php"><img src="images/pandora_logo.png" border="0" alt="logo" /></a>
				</div>
				<div id="db_ftxt">
				<h1 id="log_f" class="error">Pandora FMS Console Error DB-003</h1>
				Cannot override authorization variables from the config database. Remove them from your database by executing:
				DELETE FROM tconfig WHERE token = "auth";
				<br />
				</div>
				</div></body></html>');
		default:
			$config[$c['token']] = $c['value'];
		}
	}
	
	if (isset ($config['homeurl']) && $config['homeurl'][0] != '/') {
		$config['homeurl'] = '/'.$config['homeurl'];
	}
	
	if (! isset ($config['date_format'])) {
		update_config_value ('date_format', 'F j, Y, g:i a');
	}
	
	if (! isset ($config['event_view_hr'])) {
		update_config_value ('event_view_hr', 8);
	}
	
	if (! isset ($config['loginhash_pwd'])) {
		update_config_value ('loginhash_pwd', rand (0, 1000) * rand (0, 1000)."pandorahash");
	}
	
	if (!isset($config["trap2agent"])) {
		update_config_value ('trap2agent', 0);
	}
	
	if (!isset ($config["sla_period"]) || empty ($config["sla_period"])) {
		update_config_value ('sla_period', 604800);
	}
	
	if (!isset ($config["prominent_time"])) {
		// Prominent time tells us what to show prominently when a timestamp is
		// displayed. The comparation (... days ago) or the timestamp (full date)
		update_config_value ('prominent_time', 'comparation');
	}
	
	if (!isset ($config["timesource"])) {
		// Timesource says where time comes from (system or mysql)
		update_config_value ('timesource', 'system');
	}
	
	if (!isset ($config["https"])) {
		// Sets whether or not we want to enforce https. We don't want to go to a
		// potentially unexisting config by default
		update_config_value ('https', false);
	}
	
	if (!isset ($config["compact_header"])) {
		update_config_value ('compact_header', false);
	}
	
	if (isset ($_SESSION['id_usuario']))
		$config["id_user"] = $_SESSION["id_usuario"];
	
	if (!isset ($config["auth"])) {
		require_once ($config["homedir"]."/include/auth/mysql.php");
	} else {
		require_once ($config["homedir"]."/include/auth/".$config["auth"]["scheme"].".php");
	}
	
	/* Finally, check if any value was overwritten in a form */
	update_config ();
}
?>
