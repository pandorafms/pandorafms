<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

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
if (!isset ($build_version))
	$build_version = "PC080822";
if (!isset ($pandora_version))
	$pandora_version = "v2.0Beta1";

// This is directory where placed "/attachment" directory, to upload files stores. 
// This MUST be writtable by http server user, and should be in pandora root. 
// By default, Pandora adds /attachment to this, so by default is the pandora console home dir

$config["attachment_store"] = $config["homedir"]."attachment";

// Default font used for graphics (a Free TrueType font included with Pandora FMS)
$config["fontpath"] = $config["homedir"]."/reporting/FreeSans.ttf";

// Style (pandora by default)
$config["style"] = "pandora";

// Default period (in secs) for auto SLA calculation (for monitors)
$config["sla_period"] = 604800;

// Read remaining config tokens from DB
if (! mysql_connect ($config["dbhost"], $config["dbuser"], $config["dbpass"])) {
	//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
	exit ('<html><head><title>Pandora FMS Error</title>
		<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
		</head><body><div align="center">
		<div id="db_f">
		<div>
		<a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
		</div>
		<div id="db_ftxt">
		<h1 id="log_f" class="error">Pandora FMS Console Error DB-001</h1>
		Cannot connect with Database, please check your database setup in the 
		<b>./include/config.php</b> file and read documentation.<i><br><br>
		Probably any of your user/database/hostname values are incorrect or 
		database is not running.</i><br><br><font class="error">
		<b>MySQL ERROR:</b> '. mysql_error().'</font>
		<br>&nbsp;
		</div>
		</div></body></html>');
}

mysql_select_db ($config["dbname"]);
require_once ('functions_db.php');
$configs = get_db_all_rows_in_table ('tconfig');

if (sizeof ($configs) == 0) {
	exit ('<html><head><title>Pandora FMS Error</title>
		<link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
		</head><body><div align="center">
		<div id="db_f">
		<div>
		<a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
		</div>
		<div id="db_ftxt">
		<h1 id="log_f" class="error">Pandora FMS Console Error DB-002</h1>
		Cannot load configuration variables. Please check your database setup in the
		<b>./include/config.php</b> file and read documentation.<i><br><br>
		Probably database schema is created but there are no data inside it or you have a problem with DB access credentials.
		</i><br>
		</div>
		</div></body></html>');
}

foreach ($configs as $c) {
	switch ($c["token"]) {
	case "language_code":
		$config["language"] = $c["value"];
		
		break;
	default:
		$config[$c["token"]] = $c["value"];
	}
}

if ($config["language"] == 'ast_es') {
	$help_code = 'ast';
} else {
	$help_code = substr ($config["language"], 0, 2);
}

if (! defined ('EXTENSIONS_DIR'))
	define ('EXTENSIONS_DIR', 'extensions');

require_once ('functions_extensions.php');

$config['extensions'] = get_extensions ();

require_once ('streams.php');
require_once ('gettext.php');

$l10n = NULL;
if (file_exists ('./include/languages/'.$config["language"].'.mo')) {
	$l10n = new gettext_reader (new CachedFileReader ('./include/languages/'.$config["language"].'.mo'));
	$l10n->load_tables();
}

if (!isset($config["date_format"])) {
	$config["date_format"] = "F j, Y, g:i a";
}


?>
