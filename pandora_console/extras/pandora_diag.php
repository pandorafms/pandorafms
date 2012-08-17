<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2010 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

function render_info ($table) {
	global $console_mode;
	
	$info = db_get_sql  ("SELECT COUNT(*) FROM $table");
	render_row ($info,"DB Table $table");
}

function render_info_data ($query, $label) {
	global $console_mode;
	
	$info = db_get_sql  ($query);
	render_row ($info, $label);
}

function render_row ($data, $label){
	global $console_mode;
	
	if ($console_mode == 1){
		echo $label;
		echo "|";
		echo $data;
		echo "\n";
	}
	else { 
		echo "<tr>";
		echo "<td>" . $label;
		echo "<td>" . $data;
		echo "</td>";
		echo "</tr>";
	}
}


$console_mode = 1;
if (!isset($argc))
	$console_mode = 0;
	
if ($console_mode == 1) {
	echo "\nPandora FMS PHP diagnostic tool v3.2 (c) Artica ST 2009-2010 \n";
	
	if ($argc == 1 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
		echo "\nThis command line script gives information about Pandora FMS database. 
This program only can be executed from console, and need a parameter, the
full path to Pandora FMS 'config.php' file.

  Usage:
  php pandora_diag.php path_to_pandora_console
  
  Example:
  php pandora_diag.php /var/www/pandora_console
  
";
		exit;
	}
	if (preg_match ('/[^a-zA-Z0-9_\/\.]|(\/\/)|(\.\.)/', $argv[1])) {
		echo "Invalid path: $argv[1]. Always use absolute paths.";
		exit;
	}
	include $argv[1]."/include/config.php";
}
else {
	if (file_exists("../include/config.php"))
		include "../include/config.php";
	
	global $config;
	
	// Not from console, this is a web session
	if ((!isset($config["id_user"])) OR (!check_acl ($config["id_user"], 0, "PM"))) {
		echo "<h2>You don't have privileges to use diagnostic tool</h2>";
		echo "<p>Please login with an administrator account before try to use this tool</p>";
		exit;
	}

	// Header
	ui_print_page_header (__('Pandora FMS Diagnostic tool'), "", false, "", true);

	echo "<table with='98%' cellpadding='4' cellspacing='4'>";
	echo "<tr><th align=left>".__("Item")."</th>";
	echo "<th>".__("Data value")."</th></tr>";
}

render_row ($build_version, "Pandora FMS Build");
render_row ($pandora_version, "Pandora FMS Version");
render_row ($config["homedir"], "Homedir");
render_row ($config["homeurl"], "HomeUrl");
render_row (phpversion(), "PHP Version");

render_info ("tagente");
render_info ("tagent_access");
render_info ("tagente_datos");
render_info ("tagente_datos_string");
render_info ("tagente_estado");
render_info ("tagente_modulo");
render_info ("talert_actions");
render_info ("talert_commands");
render_info ("talert_template_modules");
render_info ("tevento");
render_info ("tlayout");
if($config['enterprise_installed'])
	render_info ("tlocal_component");
render_info ("tserver");
render_info ("treport");
render_info ("ttrap");
render_info ("tusuario");
render_info ("tsesion");

switch ($config["dbtype"]) {
	case "mysql":
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_version'", "DB Schema Version");
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_build'", "DB Schema Build");
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'enterprise_installed'", "Enterprise installed");
		render_row ( date ("Y/m/d H:i:s",
			db_get_sql ("SELECT `value`
				FROM tconfig
				WHERE `token` = 'db_maintance'")), "PandoraDB Last run");
		
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE `key` = 'customer_key';", "Update Key");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE `key` = 'updating_code_path'", "Updating code path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE `key` = 'keygen_path'", "Keygen path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE `key` = 'current_update'", "Current Update #");
		break;
	case "postgresql":
		render_info_data ("SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'db_scheme_version'", "DB Schema Version");
		render_info_data ("SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'db_scheme_build'", "DB Schema Build");
		render_info_data ("SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'enterprise_installed'", "Enterprise installed");
		render_row ( date ("Y/m/d H:i:s",
			db_get_sql ("SELECT \"value\"
				FROM tconfig WHERE \"token\" = 'db_maintance'")), "PandoraDB Last run");
		
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'customer_key';", "Update Key");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'updating_code_path'", "Updating code path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'keygen_path'", "Keygen path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'current_update'", "Current Update #");
		break;
	case "oracle":
		render_info_data ("SELECT value
			FROM tconfig
			WHERE token = 'db_scheme_version'", "DB Schema Version");
		render_info_data ("SELECT value
			FROM tconfig
			WHERE token = 'db_scheme_build'", "DB Schema Build");
		render_info_data ("SELECT value
			FROM tconfig
			WHERE token = 'enterprise_installed'", "Enterprise installed");
		render_row (db_get_sql ("SELECT value
			FROM tconfig
			WHERE token = 'db_maintance'"), "PandoraDB Last run");
		
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE key = 'customer_key'", "Update Key");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE key = 'updating_code_path'", "Updating code path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE key = 'keygen_path'", "Keygen path");
		render_info_data ("SELECT value
			FROM tupdate_settings
			WHERE key = 'current_update'", "Current Update #");
		break;
}

if ($console_mode == 0) {
	echo "</table>";
}
?>