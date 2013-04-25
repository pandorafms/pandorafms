<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

function view_logfile ($file_name) {
	global $config;
	
	$memory_limit = ini_get("memory_limit");
	if (strstr("M", $memory_limit) !== false) {
		$memory_limit = str_replace("M", "", $memory_limit);
		$memory_limit = $memory_limit * 1024 * 1024;
		
		//Arbitrary size for the PHP program
		$memory_limit = $memory_limit - (8 * 1024 * 1024);
	}
	
	
	
	if (!file_exists($file_name)) {
		echo "<h2 class='error'>".__("Cannot find file"). "(".$file_name;
		echo ")</h2>";
	}
	else {
		$file_size = filesize($file_name);
		
		if ($memory_limit < $file_size) {
			echo "<h2>$file_name (" . __("File is too large than PHP memory allocated in the system.") . ")</h2>";
			echo "<h2>" . __("The preview file is imposible.") . "</h2>";
		}
		else if ($file_size > 512000) {
			$data = file_get_contents ($file_name, false, NULL, $file_size - 512000);
			echo "<h2>$file_name (".__("File is too large (> 500KB)").")</h2>";
			
			echo "<textarea style='width: 98%; float:right; height: 200px; margin-bottom:20px;' name='$file_name'>";
			echo "... ";
			echo $data;
			echo "</textarea><br><br>";
		}
		else {
			$data = file_get_contents ($file_name);
			echo "<h2>$file_name (".format_numeric(filesize ($file_name)/1024)." KB) </h2>";
			echo "<textarea style='width: 98%; float:right; height: 200px; margin-bottom:20px;' name='$file_name'>";
			echo $data;
			echo "</textarea><br><br>";
		}
	}
}


function pandoralogs_extension_main () {
	global $config;
	
	if (! check_acl ($config['id_user'], 0, "PM") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	
	ui_print_page_header (__("System logfile viewer"), "images/extensions.png", false, "", true, "" );
	
	echo "<p>" . __('This tool is used just to view your Pandora FMS system logfiles directly from console') . "</p>";
	
	view_logfile ($config["homedir"]."/pandora_console.log");
	view_logfile ("/var/log/pandora/pandora_server.log");
	view_logfile ("/var/log/pandora/pandora_server.error");
}

extensions_add_godmode_menu_option (__('System logfiles'), 'PM','glog', null, "v1r1");
extensions_add_godmode_function('pandoralogs_extension_main');

?>
