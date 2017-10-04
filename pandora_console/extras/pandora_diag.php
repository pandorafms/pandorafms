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

function render_row ($data, $label) {
	global $console_mode;
	
	if ($console_mode == 1) {
		echo $label;
		echo "|";
		echo $data;
		echo "\n";
	}
	else { 
		echo "<tr>";
		echo "<td style='padding:2px;border:0px;' width='60%'><div style='padding:5px;background-color:#f2f2f2;border-radius:2px;text-align:left;border:0px;'>" . $label;
		echo "</div></td>";
		echo "<td style='font-weight:bold;padding:2px;border:0px;' width='40%'><div style='padding:5px;background-color:#f2f2f2;border-radius:2px;text-align:left;border:0px;'>" . $data;
		echo "</div></td>";
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

	echo "<table width='1000px' border='0' style='border:0px;' class='databox data' cellpadding='4' cellspacing='4'>";
	echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("Pandora status info")."</th></tr>";
}

render_row ($build_version, "Pandora FMS Build");
render_row ($pandora_version, "Pandora FMS Version");
render_info_data ("SELECT value FROM tconfig where token ='MR'","Minor Release");
render_row ($config["homedir"], "Homedir");
render_row ($config["homeurl"], "HomeUrl");
render_info_data ("SELECT `value`
	FROM tconfig
	WHERE `token` = 'enterprise_installed'", "Enterprise installed");
	
	$full_key = db_get_sql("SELECT value
		FROM tupdate_settings
		WHERE `key` = 'customer_key'");
		
	$compressed_key = substr($full_key, 0,5).'...'.substr($full_key, -5);
		
	render_row ($compressed_key,"Update Key");
	
	render_info_data ("SELECT value
		FROM tupdate_settings
		WHERE `key` = 'updating_code_path'", "Updating code path");
		
	render_info_data ("SELECT value
		FROM tupdate_settings
		WHERE `key` = 'current_update'", "Current Update #");


echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("PHP setup")."</th></tr>";


render_row (phpversion(), "PHP Version");

render_row (ini_get('max_execution_time'), "PHP Max ejecution time");

render_row (ini_get('max_input_time'), "PHP Max input time");

render_row (ini_get('memory_limit'), "PHP Memory limit");

render_row (ini_get('session.cookie_lifetime'), "Session cookie lifetime");

echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("Database size stats")."</th></tr>";

render_info_data ("SELECT COUNT(*) FROM tagente","Total agents");
render_info_data ("SELECT COUNT(*) FROM tagente_modulo","Total modules");
render_info_data ("SELECT COUNT(*) FROM tgrupo","Total groups");
render_info_data ("SELECT COUNT(*) FROM tagente_datos","Total module data records");
// render_info_data ("SELECT COUNT(*) FROM tagente_datos_string","Total module string data records");
// render_info_data ("SELECT COUNT(*) FROM tagente_datos_log4x","Total module log4x data records");
render_info_data ("SELECT COUNT(*) FROM tagent_access","Total agent access record");
// render_info ("tagente_estado");
// render_info ("talert_template_modules");
render_info_data ("SELECT COUNT(*) FROM tevento","Total events");

if($config['enterprise_installed'])
render_info_data ("SELECT COUNT(*) FROM ttrap","Total traps");
render_info_data ("SELECT COUNT(*) FROM tusuario","Total users");
render_info_data ("SELECT COUNT(*) FROM tsesion","Total sessions");

echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("Database sanity")."</th></tr>";

render_info_data ("SELECT COUNT( DISTINCT tagente.id_agente)
	FROM tagente_estado, tagente, tagente_modulo
	WHERE tagente.disabled = 0
		AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
		AND tagente_modulo.disabled = 0
		AND tagente_estado.id_agente = tagente.id_agente
		AND tagente_estado.estado = 3","Total unknown agents");
		
render_info_data ("SELECT COUNT( DISTINCT tagente.id_agente)
	FROM tagente_estado, tagente, tagente_modulo
	WHERE tagente.disabled = 0
		AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
		AND tagente_modulo.disabled = 0
		AND tagente_estado.id_agente = tagente.id_agente
		AND tagente_estado.estado = 4","Total not-init modules");


$last_run_difference = '';

$diferencia = time() - date (
	db_get_sql ("SELECT `value`
		FROM tconfig
		WHERE `token` = 'db_maintance'"));

$last_run_difference_months = 0;
$last_run_difference_weeks = 0;
$last_run_difference_days = 0;
$last_run_difference_minutos = 0;
$last_run_difference_seconds = 0;

while($diferencia >= 2419200){
	$diferencia -= 2419200;
	$last_run_difference_months++;
}

while($diferencia >= 604800){
	$diferencia -= 604800;
	$last_run_difference_weeks++;
}

while($diferencia >= 86400){
	$diferencia -= 86400;
	$last_run_difference_days++;
}

while($diferencia >= 3600){
	$diferencia -= 3600;
	$last_run_difference_hours++;
}

while($diferencia >= 60){
	$diferencia -= 60;
	$last_run_difference_minutes++;
}

$last_run_difference_seconds = $diferencia;

if($last_run_difference_months > 0){
	$last_run_difference .= $last_run_difference_months.'month/s ';
}

if ($last_run_difference_weeks > 0) {
	$last_run_difference .= $last_run_difference_weeks.' week/s ';
}

if ($last_run_difference_days > 0) {
	$last_run_difference .= $last_run_difference_days.' day/s ';
}

if ($last_run_difference_hours > 0) {
	$last_run_difference .= $last_run_difference_hours.' hour/s ';
}

if ($last_run_difference_minutes > 0) {
	$last_run_difference .= $last_run_difference_minutes.' minute/s ';
}

$last_run_difference .= $last_run_difference_seconds.' second/s ago';
									
render_row ( date ("Y/m/d H:i:s",
db_get_sql ("SELECT `value`
	FROM tconfig
	WHERE `token` = 'db_maintance'")).' ('.$last_run_difference.')'.' *', "PandoraDB Last run");

echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("Database status info")."</th></tr>";

switch ($config["dbtype"]) {
	case "mysql":
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_first_version'", "DB Schema Version (first installed)");
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_version'", "DB Schema Version (actual)");
		render_info_data ("SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_build'", "DB Schema Build");
				
		if(strpos($_SERVER['HTTP_USER_AGENT'],'Windows') == false){
		
		echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__("System info")."</th></tr>";
				
		$output = 'cat /proc/cpuinfo  | grep "model name" | tail -1 | cut -f 2 -d ":"';
		$output2 = 'cat /proc/cpuinfo  | grep "processor" | wc -l';
		
		render_row(exec($output).' x '.exec($output2),'CPU');
		
		$output = 'cat /proc/meminfo  | grep "MemTotal"';
		
		render_row(exec($output),'RAM');
		
		}
		
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

		render_info_data ("SELECT " . db_escape_key_identifier('value') .
			" FROM tupdate_settings
			WHERE \"key\" = 'customer_key'", "Update Key");
		render_info_data ("SELECT " . db_escape_key_identifier('value') .
			" FROM tupdate_settings
			WHERE \"key\" = 'updating_code_path'", "Updating code path");
		render_info_data ("SELECT " . db_escape_key_identifier('value') .
			" FROM tupdate_settings
			WHERE \"key\" = 'current_update'", "Current Update #");
		break;
}

if ($console_mode == 0) {
	echo "</table>";
}

echo "<hr color='#b1b1b1' size=1 width=1000 align=left>";

echo "<span>".__('(*) Please check your Pandora Server setup and be sure that database maintenance daemon is running. It\' very important to 
keep up-to-date database to get the best performance and results in Pandora')."</span><br><br><br>";



?>