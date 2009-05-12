<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

//Pandora Version
$build_version = 'PC090512';
$pandora_version = 'v3.0-dev';

$config['start_time'] = microtime (true);

//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
$config['dbconnection'] = mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);
if (! $config['dbconnection']) {
	exit ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml"><head><title>Pandora FMS Error</title>
		<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
		</head><body><div style="align:center">
		<div id="db_f">
		<div>
		<a href="index.php"><img src="images/pandora_logo.png" border="0" alt="logo" /></a>
		</div>
		<div id="db_ftxt">
		<h1 id="log_f" class="error">Pandora FMS Console Error DB-001</h1>
		Cannot connect to the database, please check your database setup in the 
		<b>include/config.php</b> file or read the documentation on how to setup Pandora FMS.<i><br /><br />
		Probably one or more of your user, database or hostname values are incorrect or 
		the database server is not running.</i><br /><br /><span class="error">
		<b>MySQL ERROR:</b> '. mysql_error().'</span>
		<br />&nbsp;
		</div>
		</div></body></html>');
}

mysql_select_db ($config["dbname"]);
require_once ('functions.php');
require_once ('functions_db.php');
require_once ('functions_config.php');

process_config ();

require_once ('streams.php');
require_once ('gettext.php');

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

?>
