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
$build_version = 'PC090926';
$pandora_version = 'v3.0-dev';

$config['start_time'] = microtime (true);

//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
$config['dbconnection'] = mysql_pconnect ($config["dbhost"], $config["dbuser"], $config["dbpass"]);
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
